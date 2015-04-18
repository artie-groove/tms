<?
/*
 * Немного о коде - функцции с постфиксом _d подходят для работы с дневным и вечерним расписанием.
 * Многие функции используют глобальные переменные. Есл ифункция инициализирует глобальную переменную - 
 * то ряддом с её объявлением стоит соответсвующий коментарий.
 * 
 */

class Parser extends Handler implements IStatus
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_WIDTH = 120;      // максимальное количество столбцов, формирующих таблицу
    
    //---------------------------------------------------------------------переменные общего назначения
    private $PHPExcel;
    
    // Массив с данными
    private $Group;
   
    /*
    //-----------------------------------------------------------------------Перемнные заочного распсиания
    private $Section_Start;// ширина текущей секции
    private $Section_end;// конец текущей секции
    private $Section_date_start;//начало данных для текущей секции
    */

    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    private function cleanupTable($sheet, $rx, &$w, &$h)
    {
        // избавляемся от пустых столбцов
        for ( $c = 0; $c < $w; $c++ )
            if ( ! $sheet->getColumnDimensionByColumn($c)->getVisible() ) {
                $sheet->removeColumnByIndex($c);
                $w--;
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
    private function hasRightBorder($sheet, $cx, $rx) {
        $currentCellHasRightBorder = $sheet->getCellByColumnAndRow($cx, $rx)->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";
        $nextCellHasLeftBorder = $sheet->getCellByColumnAndRow($cx + 1, $rx)->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
        return ( $currentCellHasRightBorder || $nextCellHasLeftBorder );
    }
    
    // === Проверить, есть ли нижняя граница
    private function hasBottomBorder($sheet, $cx, $rx) {
        $currentCellHasBottomBorder = $sheet->getCellByColumnAndRow($cx, $rx)->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none";
        $nextCellHasTopBorder = $sheet->getCellByColumnAndRow($cx, $rx + 1)->getStyle()->getBorders()->getTop()->getBorderStyle() !== "none";
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
                //$strSummary = var_dump($summary);
                //throw new Exception( $strSummary . '===' . $rx . ':' . $cx );
            }     
            $row++;
        }  
        $summary['height'] = $row - $rx;
        if ( ! empty($summary['offset']) ) return $summary;
        
        // ищем внутренние границы
        for ( $r = $rx; $r <= $row; $r++ ) {
            for ( $c = $cx; $c < $col; $c++ ) {
                if ( $this->hasRightBorder($sheet, $c, $r) ) {
                    $summary['offset'] = $c - $cx + 1;                
                    return $summary;
                }
            }
        }
        return $summary;
    }
    
    // === Читает ячейку
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
        
        //throw new Exception("$rx:$cx:$w:$h");
                
        //$row = $rx - 1;
        // Цикл по строкам
        for ( $r = $rx; $r < $rx + $h; $r++ )
        {
            //$row++;
            //$col = $cx - 1;
            // Цикл по столбцам
            for ( $c = $cx; $c < $cx + $w; $c++ )
            {
                //$col++;
                // Если ячейка не пуста
                if ( trim($sheet->getCellByColumnAndRow($c, $r)) != "" )
                {
                    // Записываем значение ячейки
                    $str = trim($sheet->getCellByColumnAndRow($c, $r));
                    
                    // если текст помечен жирным, то это название дисциплины
                    if ( $sheet->getCellByColumnAndRow($c, $r)->getStyle()->getFont()->getBold() == 1)
                    {
                        // кроме названия дисциплины в строке могут находиться и другие сведения
                        $matches = array();
                        //if ( preg_match('@[А-я\s.,/()-]+@u', $str, $matches) !== false )
                        if ( preg_match('/((?:[А-я]+[\s.,\/-]{0,3})+)(?:\((?1)\))?/u', $str, $matches) !== false )
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
                            $str = str_replace($matches[1], "", $str);
                            $str = trim($str);
                        }
                        
                        // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты
                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= " " . $matches[0];
                            $str = str_replace($matches[0], "", $str);
                            $str = trim($str);
                        }
                        
                        // поиск аудитории                        
                        if ( preg_match_all("/(?:(?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|Гараж\s№\s?3)/u", $str, $matches, PREG_PATTERN_ORDER) )
                        {
                            // Если совпадений больше 1
                            if(count($matches[0]) > 1)
                            {
                                $result['room'] = $matches[0][0];
                                $str = str_replace($matches[0][0], "", $str);
                                $result['room'] = str_replace(" ", "", $result['room']);
                                /* если больше одного дефиса
                                preg_match("/-+/", $str, $mac);
                                $result['room'] = str_replace($mac[0], "-", $result['room']);
                                */
                                                                
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
                                $result['room'] = str_replace(" ", "", $result['room']);
                                /* если больше одного дефиса 
                                preg_match("/-+/", $str, $mac);
                                $result['room'] = str_replace($mac[0], "-", $result['room']);
                                */
                                $str = str_replace($matches[0][0], "", $str);
                            }
                            $str = trim($str);
                        }
                        
                        // поиск эксплицитных дат
                        //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                        if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                        {
                            $result['dates'] = $matches[0];
                            $str = str_replace($matches[0], "", $str);
                            $str = trim($str);
                        }                                               
                        
                        // поиск преподавателя                        
                        if ( preg_match("/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u", $str, $matches) )
                        {                            
                            $result['lecturer'] = $matches[0];
                            $str= str_replace($matches[0], '', $str);
                            $str=trim($str);
                        }
                        if(trim($str) != "")
                            $result['comment'] .= $str . " ";
                    }
                }
            }
            //while ($col < $cx + $width);
        }
        //while( !($sheet->getCellByColumnAndRow($col, $row)->getStyle()->getBorders()->getBottom()->getBorderStyle() != "none"
            // || $sheet->getCellByColumnAndRow($col, $row + 1)->getStyle()->getBorders()->getTop()->getBorderStyle()  != "none"));
        
        //$result[6] = $row - $rx + 1;
        //$result[7] = $col - $cx + 1;
        //throw new Exception(var_dump($result));
        $result['comment'] = trim($result['comment']);
        $result['discipline'] = trim($result['discipline']);
        return $result;
    }
    
    // Определяет, что за месяц передан в строке и возвращает его номер
    private function Mesac_to_chislo($str)
    {
        $str = strtr($str, array("a" => "а",
                                 "A" => "А",
                                 "c" => "с",
                                 "C" => "С",
                                 "e" => "е",
                                 "E" => "Е",
                                 "o" => "о",
                                 "O" => "О"));

        $str = trim($str);
        $str = mb_strtolower($str);

        switch ($str)
        {
            case "январь":   return "1";
            case "февраль":  return "2";
            case "март":     return "3";
            case "апрель":   return "4";
            case "май":      return "5";
            case "июнь":     return "6";
            case "июль":     return "7";
            case "август":   return "8";
            case "сентябрь": return "9";
            case "октябрь":  return "10";
            case "ноябрь":   return "11";
            case "декабрь":  return "12";
        }
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
    
    private function inspectTableGeometry($sheet, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $w, $rx)
             || $this->hasBottomBorder($sheet, $w, $rx)
             || $this->hasRightBorder($sheet, $w-1, $rx)
             || $this->hasBottomBorder($sheet, $w, $rx-1))
        {
            $w++;    
        }
        $w--;
        
        $c = $w - 1;
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)
             || $this->hasBottomBorder($sheet, $c, $rx+$h)
             || $this->hasRightBorder($sheet, $c-1, $rx+$h)
             || $this->hasBottomBorder($sheet, $c, $rx+$h-1))
        {
            $h++;
        }
        $h--;
        return array('width' => $w, 'height' => $h);
    }
    
    // === Проверить целостность границ таблицы
    private function validateTable($sheet, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $w; $r++ )
        {
            $hasBorder = $this->hasRightBorder($sheet, $w - 1, $r);
            if ( ! $hasBorder ) return false;
        }
        
        // проверяем нижнюю границу
        for ( $c = 0; $c < $w; $c++ )
        {
            $hasBorder = $this->hasBottomBorder($sheet, $c, $rx + $h - 1);
            if ( ! $hasBorder ) return false;
        }
        
        return true;
    }
    
    // Определяет границы таблицы, а так же ширину колонки для группы. Устанавливает глобальные переменные
    private function establishTableParams(&$sheet, $rx)
    {   
        list ( $w, $h ) = array_values($this->inspectTableGeometry($sheet, $rx));
        $valid = $this->validateTable($sheet, $rx, $w, $h);
        //if ( ! $valid ) throw new Exception('Table is not valid');
        $this->cleanupTable($sheet, $rx, $w, $h);
        //throw new Exception('_');
        // определяем ширину матрицы дат
        $cdm = 1; // dates matrix first column
        $dmw = $cdm; // dates matrix width
        while ( trim($sheet->getCellByColumnAndRow($dmw + 1, $rx)) !== 'Часы' ) $dmw++;
        
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
             
    
    // Распознавание групп. Объявляет глобальные переменные
    private function group_init_d($sheet, $Coll_Start, $Coll_End, $Row_Start, $Shirina_na_gruppu)
    {
        $gr_cl = 0;

        for ( $i = $Coll_Start; $i < $Coll_End; $i += $Shirina_na_gruppu )
        {
            
            $groupName = trim($sheet->getCellByColumnAndRow($i, $Row_Start));            
            $groupTemplate = '/В[А-Я]{1,3}-(\d|\d{3})/u';
            if ( !preg_match($groupTemplate, $groupName) )
                throw new Exception('Incorrect group name: "' . $groupName . '"' . $i . ' ' . $gr_cl . ' ' . $Coll_End);
            
            $this->Group[$gr_cl]["NameGroup"] = $groupName;
            /*
            $this->Group[$gr_cl]["NameGroup"] = str_replace(" ", "", $this->Group[$gr_cl]["NameGroup"]);
            preg_match("/-+/ui", $this->Group[$gr_cl]["NameGroup"], $matches);

            if(count($matches) > 0)
                $this->Group[$gr_cl]["NameGroup"] =  str_replace($matches[0], "-", $this->Group[$gr_cl]["NameGroup"]);
            */
            $this->Group[$gr_cl]["Para"] = array();
            $gr_cl++;
        }
    }
    
    // Устанавливает грани между днями недели.
    private function lookupDayLimitRowIndexes($sheet, $iDatesMatrixFirstRow, $iFinalRow)
    {        
        $dayLimitRowIndexes = array();
        $k = 0;
        for ( $i = $iDatesMatrixFirstRow; $i < $iFinalRow; $i++ )
        {
            $currentCellHasBottomBorder = $sheet->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none";
            $nextCellHasTopBorder = $sheet->getCellByColumnAndRow(0, $i + 1)->getStyle()->getBorders()->getTop()->getBorderStyle() !== "none";
            
            // если наткнулись на границу
            if ( $currentCellHasBottomBorder || $nextCellHasTopBorder )
            {    
                $dayLimitRowIndexes[$k] = $i + 1;
                $k++;
            }
        }
        return $dayLimitRowIndexes;
        //throw new Exception(implode(',', $this->dayLimitRowIndexes) . ' last row: ' . $this->iFinalRow);
    }
    
    // === Заполняет массив с датами
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
        $sheetsTotal = $this->PHPExcel->getSheetCount();
        for ( $s = 0; $s < $sheetsTotal; $s++ )
        {
            $sheet = $this->PHPExcel->getSheet($s);
            $this->iDataFirstCol = 1;//начало таблицы (непосредственно данных)
            $this->iTableLastCol = 1;//за концом таблицы
            $this->iTableFirstRow = 0;//начало таблицы
            $this->iFinalRow = 0;//за концом таблицы
            $this->iDatesMatrixFirstRow = 0;//начало данных
            
            $this->iGroupWidth = 1;//Число ячеек, отведённых на одну группу.
            $this->dayLimitRowIndexes = false; //массив хранит границы дней недели
            $this->dates_massiv = false;
            
 
            $rx = $this->probeTable($sheet);
            if ( ! $rx ) break;
            
            $this->Group = array();//массив с данными.
            
            //$tableType = $this->getTableType($sheet, $tableStartsAtRow);
            $params = $this->establishTableParams($sheet, $rx);
            //print_r($params);
            list ( $width, $height, $datesMatrixFirstColumn, $datesMatrixWidth, $firstDataColumn, $groupWidth ) = array_values($params);
            $this->Group_init_d($sheet, $firstDataColumn, $width, $rx, $groupWidth);
            
            
            $dayLimitRowIndexes = $this->lookupDayLimitRowIndexes($sheet, $rx + 1, $rx + $height);
            
            $dates = $this->gatherDates($sheet, $rx, $datesMatrixFirstColumn, $datesMatrixWidth, $dayLimitRowIndexes);

            
            //throw new Exception('.');
            
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
                                /*
                                echo var_dump($res1);
                                echo var_dump($res2);
                                throw new Exception();
                                */
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
                                    $m->group = $this->Group[$gid + $g]["NameGroup"];
                                    array_push($this->Group[$gid + $g]["Para"], $m);
                                }
                        }                        
                     
                        $k += $layout['width'] - 1;                        
                    }
                }
            }
        }
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
    
    /*
    //----------------------------------------------------------------------
    ////Функции для заочного распсиания
    private   function get_orientirs_z($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные.
    {
        $this->PHPExcel;
        $this->iDataFirstCol;//начало таблицы (непосредственно данных)//инициализирует
        $this->iTableLastCol;//за концом таблицы//инициализирует
        $this->iTableFirstRow;//начало таблицы//инициализирует
        $this->iFinalRow;//за концом таблицы//инициализирует
        $this->iDatesMatrixFirstRow;//начало данных//инициализирует
        $this->iGroupWidth;//инициализирует

        while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->iTableFirstRow)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none"
           && $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->iTableFirstRow+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  === "none") {
            $this->iTableFirstRow++;
        }

        $this->iTableFirstRow++;
        $this->iDatesMatrixFirstRow = $this->iTableFirstRow + 1;

        while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->iDatesMatrixFirstRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->iDatesMatrixFirstRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->iDatesMatrixFirstRow++;
        }

        $this->iFinalRow = $this->iDatesMatrixFirstRow;

        while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->iFinalRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->iFinalRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->iFinalRow++;
        }

        while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/", trim($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->iDataFirstCol, $this->iTableFirstRow)))) {
            $this->iDataFirstCol++;
        }

        $count_z = 0;
        $coll = $this->iDataFirstCol;

        while($count_z < 1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $this->iGroupWidth++;
            if(trim($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $this->iTableFirstRow)) != "")
                $count_z++;
        }

        $this->iTableLastCol = $this->iDataFirstCol;
        while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->iTableLastCol, $this->iTableFirstRow+1)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->iTableLastCol, $this->iTableFirstRow+1)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->iTableLastCol++;
        }
    }

    private   function get_section_end($Sheat, $old_end)// находит границу секции. Принимает последнюю обнаруженную границу
    {
        $this->PHPExcel;
        $this->iTableFirstRow;
        $this->iTableLastCol;
        $this->Section_Start;//Утсанавливает значение
        $this->Section_end;//устанавливает значение
        $this->Section_date_start;//устанавливается значение
        $this->Section_Start = $old_end;
        $this->Section_end = $old_end + 1;

        while(preg_match("/дни/iu", $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_end, $this->iTableFirstRow)) == 0
            &&($this->Section_end<$this->iTableLastCol)) {
            $this->Section_end++;
        }

        $this->Section_date_start = $this->Section_Start;

        while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/", trim($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_date_start, $this->iTableFirstRow)))) {
            $this->Section_date_start++;
        }
    }
    
    private   function get_mounday_z($Row_Start_Date, $Section_Start, $Row_End, $Sheet)
    {
        $this->dates_massiv;//инициализируется, предварительно обнуляется.
        $this->dayLimitRowIndexes;
        $this->PHPExcel;
        $this->dates_massiv = false;
        $this->dates_massiv[0]["month"] = false;
        for($i = $Row_Start_Date; $i < $Row_End; $i++)
        {
            if(trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i)) != "")
            {
                $k=-1;
                for($k = 0; $k < count($this->dayLimitRowIndexes); $k++)
                {
                    if($this->dayLimitRowIndexes[$k] > $i)
                        break;
                }

                if(isset($this->dates_massiv[0]["month"][$k])) {
                    $this->dates_massiv[0]["month"][$k] .= trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                } else {
                    $this->dates_massiv[0]["date"][$k] = trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                }
            }
        }
    }
    */
    ///////////////////////////////////////////
    public function parsing($file_name)
    {
        try {
            $this->PHPExcel = PHPExcel_IOFactory::load($file_name);
            $this->type_stady = 0;

            switch ( $this->type_stady )
            {
                case 0:  $this->get_day_raspisanie(); break;//расписание дневное - фас!
                case 1:  $this->get_day_raspisanie(); break;//распсиание вечернее - фас!
                default: return false;
            }

            return true;
        }
        catch (Exception $e)
        {
            $this->setStatus('error', $e->getLine(), $e->getMessage());
            return false;
        }

    }
    
    public function getParseData()
    {
        try {
            $par_date = array();

            for ($i = 0; $i < count($this->Group); $i++) {
                //foreach ( $this->Group[$i]["Para"] as $m ) if ( ($m->lecturer === "Хаирова") && ($m->offset === 1) ) throw new Exception(implode('&bull;', (array)$m));
              
                
                $par_date = array_merge($par_date, $this->Group[$i]["Para"]);
            }

            $this->setStatus("OK", "Парсинг прошёл успешно.");
            return $par_date;
        }
        catch (Exception $e)
        {
            throw $e;
            $this->setStatus("Error", "Парсинг провалился: что-то пошло не так.");
            return false;
        }
    }
}
?>