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
            'comment' => ''
        );
        
        
        for ( $r = $rx; $r < $rx + $h; $r++ )
        {
            for ( $c = $cx; $c < $cx + $w; $c++ )
            {
                $str = trim($sheet->getCellByColumnAndRow($c, $r));
           
                if ( ! empty($str) ) {
                    
                    // если текст помечен жирным, то это название дисциплины
                    if ( $sheet->getCellByColumnAndRow($c, $r)->getStyle()->getFont()->getBold() == 1 )
                    {
                        // кроме названия дисциплины в строке могут находиться и другие сведения
                        $matches = array();
                        if ( preg_match('/((?:[А-яA-Z]+[\s.,\/-]{0,3})+)(?:\((?1)\))?/u', $str, $matches) !== false )
                        {   
                            // конкатенация для тех случаев, когда название дисциплины
                            // продолжается в следующей ячейке
                            $result['discipline'] .= $matches[0] . ' ';
                            $str = mb_substr($str, mb_strlen($matches[0]));
                            $result['comment'] .= ' ' . $str;                 
                        }                        
                    }
                    else
                    {
                        // поиск типа занятия                        
                        if ( preg_match("/(?:^|\s)((?:лаб|лек|пр)\s*\.?)/u", $str, $matches) )
                        {
                            $result['type'] = $matches[1];
                            $str = str_replace($matches[1], '', $str);
                        }
                        
                        // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= ' ' . $matches[0];
                            $str = str_replace($matches[0], '', $str);                           
                        }
                        
                        /*
                        // поиск аудитории                        
                        if ( preg_match_all("/(?:(?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|Гараж\s№\s?3)/u", $str, $matches, PREG_PATTERN_ORDER) )
                        {
                            // если совпадений больше единицы
                            if ( count($matches[0]) > 1 )
                            {
                                $result['room'] = $matches[0][0];
                                $str = str_replace($matches[0][0], "", $str);
                                $result['room'] = str_replace(" ", "", $result['room']);
                                                                
                                // всё остальное - в комментарий
                                for($i = 1; $i < count($matches[0]); $i++)
                                {
                                    $result['comment'] .= $matches[0][$i];
                                    $str = str_replace($matches[0][$i], "", $str);
                                }
                            }
                            else
                            {
                                $result['room'] = $matches[0][0];
                                $result['room'] = str_replace(" ", '', $result['room']);                                
                                $str = str_replace($matches[0][0], '', $str);
                            }
                            $str = trim($str);
                        }
                        */
                        
                        // поиск аудитории                        
                        if ( preg_match("/((?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|Гараж\s№3)(?:\s|$|,)/u", $str, $matches) )
                        {   
                            $result['room'] = $matches[1];
                            $str = str_replace($matches[0], '', $str);
                        }
                        
                        // поиск эксплицитных дат
                        //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                        if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                        {   
                            $result['dates'] = preg_replace('/\s/i', '', $matches[0]);
                            $str = str_replace($matches[0], "", $str);
                        }                                               
                        
                        // поиск преподавателя                        
                        if ( preg_match("/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u", $str, $matches) )
                        {                            
                            $result['lecturer'] = $matches[0];
                            $str= str_replace($matches[0], '', $str);
                        }
                        
                        $str = trim($str);
                        if ( !empty($str) ) $result['comment'] .= $str . " ";
                    }
                }
            }        
        }
        $result['comment'] = trim($result['comment']);
        $result['discipline'] = trim($result['discipline']);
        return $result;
    }
    
    // === Получить номер пары
    
    private function lookupLocationOffset($sheet, $cx, $rx)
    {
        $k = 0;

        do
        {
            $str = $sheet->getCellByColumnAndRow($cx - 1, $rx + $k);
            $str = trim($str);
            str_replace(" ", "", $str);
            $k++;
        }
        while ($str == "");

        $matches[0] = false;
        preg_match("/-+/iu", $str, $matches);
        
        if(count($matches) > 0)
            $str = str_replace($matches[0], "-", $str);

        $offset = -2;
        
        switch ($str)
        {
            case "1-2":   $offset = 0;                           break;
            case "3-4":   $offset = 1;                           break;
            case "5-6":   $offset = 2;                           break;
            case "7-8":   $offset = 3;                           break;
            case "9-10":  $offset = 4;                           break;
            case "11-12": $offset = 5;                           break;
            case "13-14": $offset = 6;                           break;
            case "15-16": $offset = 7;                           break;
            case "8-00":  $offset = 0;  break;
            case "9-40":  $offset = 1;   break;
            case "11-20": $offset = 2;  break;
            case "13-00": $offset = 3;  break;
            case "14-40": $offset = 4;   break;
            case "16-20": $offset = 5;   break;
            case "18-00": $offset = 6;   break;
            case "19-30": $offset = 7;   break;
            default:      $offset = -1;                          break;
        }
        
        return $offset;
    }
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    private function inspectTableGeometry($sheet, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $w, $rx)      || $this->hasBottomBorder($sheet, $w, $rx) 
             || $this->hasRightBorder($sheet, $w-1, $rx)    || $this->hasBottomBorder($sheet, $w, $rx-1))
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
        for ( $r = $rx; $r < $rx + $w; $r++ )  
            if ( ! $this->hasRightBorder($sheet, $w - 1, $r) )            
                //throw new Exception('Нарушена целостность правой границы');   
                return false;
            
        // проверяем нижнюю границу
        for ( $c = 0; $c < $w; $c++ )
            if ( ! $this->hasBottomBorder($sheet, $c, $rx + $h - 1) )
                //throw new Exception('Нарушена целостность нижней границы');   
                return false;
        
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
            
            for ( $i = $rx + 1; $i < $rx + $height; $i++ )
            {
                for( $k = $firstDataColumn; $k < $firstDataColumn + $width; $k++ )
                {
                    $bLeft = $sheet->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle() != "none";
                    $bRight = $sheet->getCellByColumnAndRow($k - 1, $i)->getStyle()->getBorders()->getRight()->getBorderStyle()!= "none";
                    $bTop = $sheet->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle() != "none";
                    $bBottom = $sheet->getCellByColumnAndRow($k, $i - 1)->getStyle()->getBorders()->getBottom()->getBorderStyle() != "none";
                    // эксплорим занятие (спускаемся в клетку)
                    // если в текущей ячейке точно есть левая и верхняя границы
                    if ( ( $bLeft || $bRight ) && ( $bTop || $bBottom ) )
                    {
                        if ( $sheet->getCellByColumnAndRow($k, $i) == '' ) continue;
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
                                $this->postProcessLocationData($res1, $i, $dayLimitRowIndexes, $dates);
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res1);
                            }
                            else {                                
                                $this->postProcessLocationData($res1, $i, $dayLimitRowIndexes, $dates);
                                $this->postProcessLocationData($res2, $i, $dayLimitRowIndexes, $dates);
                                $meetings[] = new Meeting();
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res1);
                                $meetings[1]->initFromArray($res2);
                                $this->crossFillItems($meetings[0], $meetings[1]);
                            }
                        }
                        else {
                            $res = $this->extractLocation($sheet, $k, $layout['width'], $i, $layout['height']);
                            $this->postProcessLocationData($res, $i, $dayLimitRowIndexes, $dates);
                            $meetings[] = new Meeting();
                            $meetings[0]->initFromArray($res);
                        }
                        
                        if ( empty($meetings[0]->discipline) ) {
                            $k += $layout['width'] - 1;
                            continue;
                        }
                        
                        // индекс текущей группы в массиве Group
                        $gid = floor(($k - $firstDataColumn) / $groupWidth);
                                       
                        // номер пары
                        $offset = $this->lookupLocationOffset($sheet, $firstDataColumn, $i);

                        // количество групп, задействованных в занятии
                        $groupsCount = ceil($layout['width'] / $groupWidth);
                        
                        if ( ! $groupsCount  ) throw new Exception(var_dump($meetings[0]));

                        // количество занятий
                        $meetingsCount = floor($layout['height'] / 2); // Todo: на основе размера ячейки с указанием номера пары (то есть вместо "двойки" определить количество строк, фактически занимаемых парой)

                        foreach ( $meetings as $meeting ) {
                            $meeting->offset = $offset;
                            // множим занятия (по группам и по академическим часам)
                            for ( $g = 0; $g < $groupsCount; $g++ )
                                for ( $z = 0; $z < $meetingsCount; $z++ )
                                {
                                    $m = new Meeting();
                                    $m->copyFrom($meeting);
                                    $m->offset += $z;
                                    $m->group = $groups[$gid + $g];
                                    $storage[] = $m;
                                }
                        }                        
                     
                        $k += $layout['width'] - 1;                        
                    }
                }
            }
        }
        return $storage;
    }
    
    // Пост-обработка данных, полученных из локации
    private function postProcessLocationData(&$data, $rx, $dayLimitRowIndexes, $dates)
    {
        // вырезаем двусмысленные эксплицитные указания времени занятия
        empty($data['comment']) ?: $data['comment'] = preg_replace('/(?:[Сс]\s*)?([012]?\d[.:][0-5]0)(?:\s*-\s*(?-1))?/u', '', $data['comment']);
        // ToDo: распознавать время и учитывать его в позиционировании занятия,
        // а также реструктурировать базу данных

        // проверяем даты
        if ( empty($data['dates']) )
        {
            // анализируем даты, попавшие в комментарий
            $mc = array();                                
            // определяем цепочку с датами, если встречаются цифры, разделённые запятыми,
            // возможно, с пробелами до и после запятых
            if ( !empty($data['comment']) && preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $data['comment'], $mc, PREG_OFFSET_CAPTURE) )
            {
                // вырезаем подстроку с датами из коментария
                $posFrom = $mc[0][1];
                $posTo = $posFrom + mb_strlen($mc[0][0]);
                $data['comment'] = mb_substr($data['comment'], 0, $posFrom) . mb_substr($data['comment'], $posTo);
                // вырезаем все пробелы из строки с датами                                    
                $data['dates'] = preg_replace('/\s/u', '', $mc[0][0]);
            }
            else // берём их из массива дат в самой таблице
            {         
                $wd = 0;
                
                // находим индекс текущего дня в таблице дат
                while ( $rx >= $dayLimitRowIndexes[$wd] )
                    $wd++;
                
                $data['dates'] = $dates[$wd];                
            }
        }
    }
    
    // Взаимодополнить поля двух занятий
    private function crossFillItems($m1, $m2) {
        $basis = array('discipline', 'type', 'room', 'lecturer', 'dates');
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