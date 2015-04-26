<?

class Parser extends Handler implements IStatus
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_WIDTH = 120;      // максимальное количество столбцов, формирующих таблицу
    
    private $PHPExcel;
    

    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    
    private function cleanupTable($sheet, $rx, &$w, &$h)
    {
        // избавляемся от пустых столбцов
        for ( $c = 0; $c < $w; $c++ )
        {
            if ( ! $sheet->getColumnDimensionByColumn($c)->getVisible() ) {
                $sheet->removeColumnByIndex($c);
                $w--;
            }
        }
        
        // избавляемся от пустых строк
        for ( $r = $rx; $r < $rx + $h; $r++ )
            if ( ! $sheet->getRowDimension($r)->getVisible() ) {
                $sheet->removeRow($r);
                $h--;
            }
        
        // сносим плашки первой и второй недель
        for ( $r = $rx + 1; $r < $rx + $h; $r++ )
        {
            $cellColor = $sheet->getCellByColumnAndRow(1, $r)->getStyle()->getFill()->getStartColor()->getRGB();            
            $currentCellIsNotWhite = $cellColor !== "FFFFFF";
            $currentCellIsNotTransparent = $cellColor !== "000000";
            if ( $currentCellIsNotWhite && $currentCellIsNotTransparent ) {       
                $sheet->removeRow($r, 1);                
                $h--;
            }
        }
    }

    // === Зондировать лист на предмет наличия таблицы
    // если найдена граница в верхней части листа, возвращает номер строки
    
    private function probeTable($sheet)
    {
        for ( $r = 1; $r < self::MAX_PROBE_DEPTH; $r++ )
        {
            $reachedBottomBorder = $this->hasBottomBorder($sheet, 0, $r);
            if ( $reachedBottomBorder ) return $r + 1;
        }
        return false;
    }
    
    // === Определить тип таблицы
    private function getTableType($sheet, $bottomRow)
    {        
        $caption = '';
        for ( $r = 1; $r < $bottomRow; $r++ )
            for ( $c = 0; $c < self::MAX_WIDTH; $c++)
                $caption .= $sheet->getCellByColumnAndRow($c, $r);
        
        $caption = preg_replace('/\s/u', '', $caption);
        $caption = mb_strtolower($caption);
        
        $matches = array();
        $pattern = '/расписание(занятий|консультаций|сессии).*(инженерно|автомеханического|вечернего|заочного)/u';
        
        if ( preg_match($pattern, $caption, $matches) )
        {
            switch ( $matches[1] )
            {
                case "занятий":
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                        case 'вечернего':
                            return 'Basic';
                        
                        default:
                            return false;
                    }
                
                case "консультаций":
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                        case 'вечернего':
                            return 'BasicTutorials';
                        
                        case 'заочного':
                            return 'PostalTutorials';
                        
                        default:
                            return false;
                    }
                
                case "сессии":
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                        case 'вечернего':
                            return 'BasicSession';
                        
                        case 'заочного':
                            return 'PostalSession';
                        
                        default:
                            return false;
                    } 
                
                default: return false;
            }
        }
        return false;
    }

    // === Проверить, есть ли правая граница
    
    private function hasRightBorder($sheet, $cx, $rx)
    {
        $currentCellHasRightBorder = $sheet->getCellByColumnAndRow($cx, $rx)
            ->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";
        
        $nextCellHasLeftBorder = $sheet->getCellByColumnAndRow($cx + 1, $rx)
            ->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
        
        return ( $currentCellHasRightBorder || $nextCellHasLeftBorder );
    }
    
    // === Проверить, есть ли нижняя граница
    
    private function hasBottomBorder($sheet, $cx, $rx)
    {
        $currentCellHasBottomBorder = $sheet->getCellByColumnAndRow($cx, $rx)
            ->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none";
        
        $nextCellHasTopBorder = $sheet->getCellByColumnAndRow($cx, $rx + 1)
            ->getStyle()->getBorders()->getTop()->getBorderStyle() !== "none";
        
        return ( $currentCellHasBottomBorder || $nextCellHasTopBorder );
    }
    
    // === Замерить локацию (клетку с описанием занятия)
    // ищем границы локации: упираемся в правую и находим ширину, затем в нижнюю и находим высоту
    // пока ищем высоту, выискиваем "дырки" в правой границе
    // если обнаружена "дырка" - ныряем до упора: граница типа B
    // если нет - проходимся по строкам локации в поисках внутренней границы типа А
    // факт существования границы говорит о том, что у локации нарушена целостность
    
    private function inspectLocation($sheet, $cx, $rx)
    {        
        $summary = array(               // выходные данные локации:
            'width' => '',              // ширина (в ячейках)
            'height' => '',             // высота (в ячейках)
            'offset' => 0               // смещение в столбцах до внутренней границы
        );
        $row = $rx; // начальная строка
        $col = $cx; // начальный столбец
        
        // находим правую границу локации        
        while ( ! $this->hasRightBorder($sheet, $col, $rx) ) $col++;
        $summary['width'] = $col - $cx + 1;
        // ищем нижнюю границу локации начиная со второй строки
        $row++;
        while ( ! $this->hasBottomBorder($sheet, $col, $row - 1) ) {
            // в это же время поглядываем на правую границу
            if ( ! $this->hasRightBorder($sheet, $col, $row) ) {
                // если справа "дырка", то фиксируем это в протокол,
                // смещаемся до правой границы, корректируем ширину и падаем на дно 
                $summary['offset'] = $col - $cx + 1;     
                while ( ! $this->hasRightBorder($sheet, $col, $row) ) $col++;
                $summary['width'] = $col - $cx + 1;
                while ( ! $this->hasBottomBorder($sheet, $col, $row) ) $row++;             
                $summary['height'] = $row - $rx; 
            }     
            $row++;
        }  
        $summary['height'] = $row - $rx;
        if ( ! empty($summary['offset']) ) return $summary;
        if ( $summary['height'] === 1 ) throw new Exception("Некорректная локация близ ячейки $col:$row");
        
        // ищем внутренние границы
        for ( $r = $rx + 1; $r <= $row; $r++ ) {
            for ( $c = $cx; $c < $col; $c++ ) {                
                if ( $this->hasRightBorder($sheet, $c, $r) ) {
                    $summary['offset'] = $c - $cx + 1;                
                    return $summary;
                }
            }
        }
        return $summary;
    }
    
    // === Прочесать локацию
    // извлекает данные из локации и возвращает массивом 
    
    private function extractLocation($sheet, $cx, $w, $rx, $h)
    {
        // начальный столбец
        $row = $rx;
        // начальная строка
        $col = $cx;
        $width = 0;
        
        // массив результатов
        $result = array(
            'discipline' => '',
            'type' => '',
            'room' => '',
            'lecturer' => '',
            'dates' => '',
            'time' => '',
            'comment' => ''
        );
        
        
        for ( $r = $rx; $r < $rx + $h; $r++ )
        {
            for ( $c = $cx; $c < $cx + $w; $c++ )
            {
                $str = trim($sheet->getCellByColumnAndRow($c, $r));
                if ( ! empty($str) )
                {
                    $matches = array();
                    
                    // если текст помечен жирным, то это название дисциплины
                    if ( $sheet->getCellByColumnAndRow($c, $r)->getStyle()->getFont()->getBold() == 1 )
                    {
                        // кроме названия дисциплины в строке могут находиться и другие сведения
                        if ( preg_match('/((?:[А-яA-Z]+[\s.,\/-]{0,3})+)(?:\((?1)\))?/u', $str, $matches) !== false )
                        {   
                            // конкатенация для тех случаев, когда название дисциплины
                            // продолжается в следующей ячейке
                            $result['discipline'] .= $matches[0] . ' ';
                            $str = str_replace($matches[0], '', $str);
                            //$str = mb_substr($str, mb_strlen($matches[0]));
                            //$result['comment'] .= ' ' . $str;                 
                        }                        
                    }
                    
                    // поиск типа занятия                        
                    if ( preg_match("/(?:^|\s)((?:лаб|лек|пр)\s*\.?)/u", $str, $matches) )
                    {
                        $result['type'] = $matches[1];
                        $str = str_replace($matches[1], '', $str);
                    }

                    // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)

                    /*
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= ' ' . $matches[0];
                            $str = str_replace($matches[0], '', $str);                           
                        }
                        */

                    if ( preg_match('/(?:[СCсc]\s*)?([012]?\d:[0-5]0)(?:\s*-\s*(?-1))?/u', $str, $matches) )
                    {
                        $result['time'] = $matches[1];
                        $result['comment'] .= ' ' . $matches[0];
                        $str = str_replace($matches[0], '', $str);
                        //if ( $matches[1] == "11:50" ) throw new Exception($matches[0] . '/' . $str);
                    }

                    // поиск аудитории                        
                    if ( preg_match("/((?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|гараж\s№3)(?:\s|$|,)/u", $str, $matches) )
                    {   
                        $result['room'] = $matches[1];
                        $str = str_replace($matches[0], '', $str);
                    }

                    // поиск эксплицитных дат
                    //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                    if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                    {   
                        $result['dates'] = preg_replace('/\s/u', '', $matches[0]);
                        $str = str_replace($matches[0], "", $str);
                    }
                    
                    /*
                        // вырезаем подстроку с датами из комментария
                        $posFrom = $mc[0][1];
                        $posTo = $posFrom + mb_strlen($mc[0][0]);
                        $data['comment'] = mb_substr($data['comment'], 0, $posFrom) . mb_substr($data['comment'], $posTo);
                        // вырезаем все пробелы из строки с датами                                    
                        $data['dates'] = preg_replace('/\s/u', '', $mc[0][0]);
                    */

                    // поиск преподавателя                        
                    if ( preg_match('/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u', $str, $matches) )
                    {                            
                        $result['lecturer'] = $matches[0];
                        $str= str_replace($matches[0], '', $str);
                    }

                    // оставшийся кусок строки цепляем к комментарию
                    $str = trim($str);
                    if ( !empty($str) ) $result['comment'] .= $str . " ";                    
                }
            }        
        }
        $result['comment'] = trim($result['comment']);
        $result['discipline'] = trim($result['discipline']);
        return $result;
    }
    
    
    // === Получить время начала занятия
    
    private function lookupTimeByRow($rx, $r, $dayLimitRowIndexes)
    {
        array_unshift($dayLimitRowIndexes, $rx + 1);
        for ( $i = 1; $r >= $dayLimitRowIndexes[$i]; $i++ );
        $offset = ( $r - $dayLimitRowIndexes[$i-1] ) / 2;
        $timetable = array('8:00', '9:40', '11:20', '13:00', '14:40', '16:20', '18:00', '19:30');
        $time = $timetable[$offset];
        
        return $time;
    }
    
    // === Получить смещение занятия относительно 8:00 в минутах
    
    private function lookupOffsetByRow($rx, $r, $dayLimitRowIndexes)
    {
        array_unshift($dayLimitRowIndexes, $rx + 1);
        for ( $i = 1; $r >= $dayLimitRowIndexes[$i]; $i++ );
        return ( $r - $dayLimitRowIndexes[$i-1] ) / 2 * 100;        
    }
    
    // === преобразовать смещение в строку времени формата "HH:MM"
    
    private function convertOffsetToTime($offset)
    {
        $h = floor($offset / 60) + 8;
        $m = sprintf('%02d', $offset % 60);
        return "$h:$m";
    }
    
    // === преобразовать строку времени формата "HH:MM" в смещение (в минутах)
    
    private function convertTimeToOffset($time)
    {
        list ( $h, $m ) = explode(':', $time);        
        return ($h - 8) * 60 + $m;
    }
    
    // === Получить время текущего занятия на основе предыдущего
    
    //private function lookupMeetingTimeBasedOnPrevMeeting($prevTime, $rx, $r)
    
    
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    private function inspectTableGeometry($sheet, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $w, $rx)      || $this->hasBottomBorder($sheet, $w, $rx) 
             || $this->hasRightBorder($sheet, $w-1, $rx)    || $this->hasBottomBorder($sheet, $w, $rx-1) )
        {
            $w++;    
        }
        $w--;
        
        $c = $w - 1;
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)   || $this->hasBottomBorder($sheet, $c, $rx+$h)
             || $this->hasRightBorder($sheet, $c-1, $rx+$h) || $this->hasBottomBorder($sheet, $c, $rx+$h-1))
        {
            $h++;
        }
        $h--;
        return array('width' => $w, 'height' => $h);
    }
    
    // === Проверить целостность границ таблицы

    private function validateTableBorders($sheet, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $h; $r++ )  
            if ( ! $this->hasRightBorder($sheet, $w - 1, $r) )            
                throw new Exception("Нарушена целостность правой границы на строке $r");   
                //return false;
            
        // проверяем нижнюю границу
        for ( $c = 0; $c < $w; $c++ )
            if ( ! $this->hasBottomBorder($sheet, $c, $rx + $h - 1) )
                throw new Exception("Нарушена целостность нижней границы в столбце $c");  
                //return false;            
        
        return true;
    }
    
    // === Определить основные параметры таблицы
    
    private function establishTableParams(&$sheet, $rx)
    {   
        list ( $w, $h ) = array_values($this->inspectTableGeometry($sheet, $rx));
        $valid = $this->validateTableBorders($sheet, $rx, $w, $h);
        //if ( ! $valid ) throw new Exception('Table is not valid');
        $this->cleanupTable($sheet, $rx, $w, $h);
        
        // определяем ширину матрицы дат
        $cdm = 1; // dates matrix first column
        $dmw = $cdm; // dates matrix width
        while ( trim($sheet->getCellByColumnAndRow($dmw + 1, $rx)) !== 'Часы' ) $dmw++;
        
        if ( $dmw > 5 ) throw new Exception("Некорректное количество столбцов в календаре. Удалите все скрытые столбцы");
        
        $cd = $cdm + $dmw + 1; // first data column
        
        // рассчитываем ширину на группу по первой ячейке для группы
        $gw = 1;
        $c = $cd;
        while ( empty(trim($sheet->getCellByColumnAndRow($c + 1, $rx))) ) $c++;
        $gw = $c - $cd + 1;
        if ( ($w - $cd) % $gw !== 0 ) throw new Exception('Ширина групп должна быть равной');
               
        return array(
            'width' => $w,
            'height' => $h,
            'datesMatrixFirstColumn' => $cdm,
            'datesMatrixWidth' => $dmw,
            'firstDataColumn' => $cd,
            'groupWidth' => $gw
        );
    }
             
    
    // === Распознать группы
    
    private function exploreGroups($sheet, $cx, $tableWidth, $rx, $groupWidth)
    {
        $groups = array();
        
        for ( $c = $cx; $c < $tableWidth; $c += $groupWidth )
        {   
            $groupName = trim($sheet->getCellByColumnAndRow($c, $rx));            
            
            if ( !preg_match('/В[А-Я]{1,3}-(\d|\d{3})/u', $groupName) )
                throw new Exception('Неверное название группы: "' . $groupName . '"');
            
            $groups[] = $groupName;   
        }        
        return $groups;
    }
    
    // === Определить индексы разделителей дней недели
    
    private function lookupDayLimitRowIndexes($sheet, $iDatesMatrixFirstRow, $iFinalRow)
    {        
        $dayLimitRowIndexes = array();
        $k = 0;
        for ( $i = $iDatesMatrixFirstRow; $i < $iFinalRow; $i++ )
        {   
            // если наткнулись на границу
            if ( $this->hasBottomBorder($sheet, 0, $i) ) {    
                $dayLimitRowIndexes[$k] = $i + 1;
                $k++;
            }
        }
        return $dayLimitRowIndexes;
    }
    
    // === Заполнить массив с датами
    // массив проиндексирован по каждому дню из таблицы
    
    private function gatherDates($sheet, $rx, $datesMatrixFirstColumn, $datesMatrixWidth, $dayLimitRowIndexes)
    {        
        $months = array(
            'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
        );
        
        $nDays = count($dayLimitRowIndexes);
        $dates = array_fill(0, $nDays, '');
        
        for ( $m = $datesMatrixFirstColumn; $m < $datesMatrixFirstColumn + $datesMatrixWidth; $m++ )
        {
            // вытащим название месяца строкой
            $monthName = mb_strtolower(trim($sheet->getCellByColumnAndRow($m, $rx)));
            // найдём числовое соответствие месяцу и запишем его в формате "ММ"
            $month = sprintf('%02d', array_search($monthName, $months) + 1);

            $r = $rx + 1; // счётчик индекса строки
            
            // для каждого дня недели заполняем соответствующий индекс массива dates
            for ( $wd = 0; $wd < $nDays; $wd++ )
            {
                for (; $r < $dayLimitRowIndexes[$wd]; $r++)
                {
                    $dateCellData = trim($sheet->getCellByColumnAndRow($m, $r));
                    if ( empty($dateCellData) ) continue; // пустые ячейки пропускаем
                    $dates[$wd] .= "$dateCellData.$month,";
                }
                $r = $dayLimitRowIndexes[$wd];
            }
        }
        // отрезаем запятые в конце каждой строки
        foreach ( $dates as &$d ) $d = rtrim($d, ',');
        return $dates;
    }
    
    // Анализирует дневное распсиание
    private function get_day_raspisanie()
    {        
        $storage = array();
        $sheetsTotal = $this->PHPExcel->getSheetCount();
        for ( $s = 0; $s < $sheetsTotal; $s++ )
        {
            $sheet = $this->PHPExcel->getSheet($s);
 
            $rx = $this->probeTable($sheet);            
            if ( ! $rx ) break;
            
            
            //$tableType = $this->getTableType($sheet, $tableStartsAtRow);
            $params = $this->establishTableParams($sheet, $rx);
            
            list ( $width, $height, $datesMatrixFirstColumn, $datesMatrixWidth, $firstDataColumn, $groupWidth ) = array_values($params);
            $groups = $this->exploreGroups($sheet, $firstDataColumn, $width, $rx, $groupWidth);       
            $dayLimitRowIndexes = $this->lookupDayLimitRowIndexes($sheet, $rx + 1, $rx + $height);            
            $dates = $this->gatherDates($sheet, $rx, $datesMatrixFirstColumn, $datesMatrixWidth, $dayLimitRowIndexes);
            
            // проходим по дням недели
            // индекс первой строки $i инициализируется здесь на основании первой строки таблицы данных
            // здесь же он инкрементируется по таблице индексов $dayLimitRowIndexes в конце каждого цикла
            for ( $i = $rx + 1, $d = 0; $d < count($dayLimitRowIndexes); $i = $dayLimitRowIndexes[$d], $d++ )
            {
                // регистр эксплицитных сеточных и внесеточных смещений времени
                $timeshift = array_fill(0, count($groups), 0); // сбрасывается в начале каждого дня недели
                for (; $i < $dayLimitRowIndexes[$d]; $i++ )
                {
                    for( $k = $firstDataColumn; $k < $firstDataColumn + $width; $k++ )
                    {
                        $cellData = $sheet->getCellByColumnAndRow($k, $i);
                        $bLeft = $this->hasRightBorder($sheet, $k - 1, $i);
                        $bTop = $this->hasBottomBorder($sheet, $k, $i - 1);
                        // эксплорим занятие (спускаемся в клетку)
                        // если в текущей ячейке точно есть левая и верхняя границы
                        if ( $bLeft && $bTop )
                        {
                            if ( empty($cellData) ) continue;
                            $layout = $this->inspectLocation($sheet, $k, $i);
                            $meetings = array();
                            if ( $layout['offset'] ) {
                                $w1 = $layout['offset'];
                                $w2 = $layout['width'] - $w1;
                                $res1 = $this->extractLocation($sheet, $k, $w1, $i, $layout['height']);
                                $res2 = $this->extractLocation($sheet, $k + $w1, $w2, $i, $layout['height']);
                                $basis = array('discipline', 'type', 'room', 'lecturer');
                                $areEqual = true;
                                foreach ( $basis as $el )
                                    $areEqual &= empty($res1[$el]) ^ empty($res2[$el]);
                                if ( $areEqual ) {
                                    foreach ( $basis as $el )
                                        if ( empty($res1[$el]) ) $res1[$el] = $res2[$el];
                                    $res1['comment'] = trim($res1['comment'] . ' ' . $res2['comment']);
                                    if ( empty($res1['dates']) )
                                        $res1['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
                                    $meetings[] = new Meeting();
                                    $meetings[0]->initFromArray($res1);
                                }
                                else {
                                    foreach ( array($res1, $res2) as $res )
                                        if ( empty($res['dates']) )
                                            $res['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
                                    $meetings[] = new Meeting();
                                    $meetings[] = new Meeting();
                                    $meetings[0]->initFromArray($res1);
                                    $meetings[1]->initFromArray($res2);
                                    $this->crossFillItems($meetings[0], $meetings[1]);
                                }
                            }
                            else {
                                $res = $this->extractLocation($sheet, $k, $layout['width'], $i, $layout['height']);
                                if ( empty($res['dates']) )
                                        $res['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res);
                            }

                            if ( empty($meetings[0]->discipline) ) {
                                $k += $layout['width'] - 1;
                                continue;
                            }

                            // индекс текущей группы в массиве Group
                            $gid = floor(($k - $firstDataColumn) / $groupWidth);

                            // количество групп, задействованных в занятии
                            $groupsCount = ceil($layout['width'] / $groupWidth);

                            if ( ! $groupsCount  ) throw new Exception(var_dump($meetings[0]));

                            // количество занятий
                            $meetingsCount = floor($layout['height'] / 2);

                            // обнаруживаем эксплицитное время и фиксируем его в регистре
                            foreach ( $meetings as $meeting ) {
                                if ( !empty($meeting->time) ) {
                                    $shift = $this->convertTimeToOffset($meeting->time);                          
                                    for ( $g = 0; $g < $groupsCount; $g++ )
                                        $timeshift[$gid + $g] = $shift;
                                    break;
                                }
                            }
                            
                            // сохраняем базовое смещение времени для всех групп
                            // это нужно, когда встречается деление по подгруппам
                            // потому что в этом случае каждое занятие подгруппы инкрементирует $timeshift[$g]
                            for ( $g = 0; $g < $groupsCount; $g++ )
                                $basetimeshift[$gid + $g] = $timeshift[$gid + $g];
                            
                            foreach ( $meetings as $meeting ) {
                                // множим занятия (по группам и по академическим часам)
                                for ( $g = $gid; $g < $groupsCount + $gid; $g++ ) {
                                    // восстанавливаем базовое смещение в начале каждого цикла
                                    $timeshift[$g] = $basetimeshift[$g]; 
                                    for ( $z = 0; $z < $meetingsCount; $z++ ) {
                                        $m = new Meeting();
                                        $m->copyFrom($meeting);                                      
                                        if ( empty($timeshift[$g]) )
                                            $m->time = $this->lookupTimeByRow($rx, $i + $z * 2, $dayLimitRowIndexes);
                                        else {
                                            if ( $z > 0 || empty($meeting->time) ) {
                                                $gridOffset = $this->lookupOffsetByRow($rx, $i + $z * 2, $dayLimitRowIndexes);
                                                if ( $gridOffset >= $timeshift[$g] ) { // всё ок, идём по сетке
                                                    $m->time = $this->convertOffsetToTime($gridOffset);
                                                    $timeshift[$g] = $gridOffset + 100;
                                                }
                                                else { // смещение подпирает, отталкиваемся от него и инкрементируем до сеточного значения
                                                    $m->time = $this->convertOffsetToTime($timeshift[$g]);
                                                    $timeshift[$g] += 100 - ($timeshift[$g] % 100);
                                                }
                                            }
                                            else // для первой встречи просто инкрементируем значение в регистре
                                                $timeshift[$g] += 100 - ($timeshift[$g] % 100);                         
                                        }
                                        $m->group = $groups[$g];
                                        $storage[] = $m;
                                    }
                                }
                            }
                            // двигаем указатель столбца на ширину текущей локации
                            $k += $layout['width'] - 1;                        
                        }
                        else { // ищем указатели смещения времени
                            if ( preg_match('/[СCсc]\s(1?\d:[0-5]0)/u', $cellData, $matches) ) {
                                // индекс текущей группы в массиве Group
                                $gid = floor(($k - $firstDataColumn) / $groupWidth);
                                $shift = $this->convertTimeToOffset($matches[1]);
                                if ( $timeshift[$gid] < $shift ) {
                                    // фиксируем смещение в регистре, если оно больше уже установленного
                                    $timeshift[$gid] = $shift;
                                }
                            }                            
                        }
                    }
                }
            }       
        }
        return $storage;
    }
    
    // Пост-обработка данных, полученных из локации
    private function getDatesByRow($rx, $dayLimitRowIndexes, $dates)
    {
        $wd = 0; // week day

        // находим индекс текущего дня в таблице дат
        while ( $rx >= $dayLimitRowIndexes[$wd] ) $wd++;

        return $dates[$wd];                
    }
    
    // Взаимодополнить поля двух занятий
    private function crossFillItems($m1, $m2) {
        $basis = array('discipline', 'type', 'room', 'lecturer', 'dates', 'time');
        foreach ( $basis as $el ) {
            if ( empty($m1->$el) ) $m1->$el = $m2->$el;
            else if ( empty($m2->$el) ) $m2->$el = $m1->$el;
        }
        $comment = trim($m1->comment . ' ' . $m2->comment);
        $m1->comment = $comment;
        $m2->comment = $comment;      
    }
    
    public function parse($file_name)
    {
        $this->PHPExcel = PHPExcel_IOFactory::load($file_name);
        return $this->get_day_raspisanie();
        /*
        try {
            $this->PHPExcel = PHPExcel_IOFactory::load($file_name);
            return $this->get_day_raspisanie();
        }
        catch (Exception $e)
        {
            $this->setStatus('error', $e->getLine(), $e);
            return false;
        }
        */
    }
    
}