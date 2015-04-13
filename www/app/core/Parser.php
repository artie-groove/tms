<?
/*
 * Немного о коде - функцции с постфиксом _d подходят для работы с дневным и вечерним расписанием.
 * Многие функции используют глобальные переменные. Есл ифункция инициализирует глобальную переменную - 
 * то ряддом с её объявлением стоит соответсвующий коментарий.
 * 
 */

class Parser extends Handler implements IStatus
{

    //---------------------------------------------------------------------переменные общего назначения
    private $objPHPExcel;
    // Текущий лист
    private $Sheat;
    // Начало таблицы (непосредственно данных)
    private $Coll_Start;
    // За концом таблицы
    private $Coll_End;
    // Начало таблицы
    private $Row_Start;
    // За концом таблицы
    private $Row_End;
    // Начало данных
    private $Row_Start_Date;
    // Массив с данными
    private $Group;
    // Число ячеек, отведённых на одну группу
    private $Shirina_na_gruppu;
    // Массив хранит границы дней недели
    private $gani;
    // Сохраняет названия месяцев и соответсвующие им дни
    private $date_massiv;
    // Форма обучения. 0 - дневная, 1 - вечерняя, 2-заочная.
    public $Type_stady;
    //-----------------------------------------------------------------------Перемнные заочного распсиания
    private $Section_Start;// ширина текущей секции
    private $Section_end;// конец текущей секции
    private $Section_date_start;//начало данных для текущей секции

    // Сносит все невидемые
    private function Order_66($Sheat)
    {
        /*
        $name_max_col = $this->PHPExcel->getSheet($Sheat)->getHighestColumn();
        
        // Максимальный заюзанный столбец.
        $coll_max = 0;

        do {
            $coll_max++;
        } while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn() != $name_max_col);
        
        
        $coll_max++;
        */
        
        $killed = 0;
        $i = 0;
        $COL_MAX = 120;

        while($i < $COL_MAX - $killed)
        {
            if($this->PHPExcel->getSheet()->getColumnDimensionByColumn($i)->getVisible() != "") {
                $i++;
            } else {
                $this->PHPExcel->getSheet()->removeColumnByIndex($i);
                $killed++;                
            }
        }
        //throw new Exception('removed ' . $killed);
    }

    private function get_typ_raspisania($Sheat)
    {
        // Здесь начинается лютый, беспросветный полярный лис. Функция перевода имени столбца в индекс не найдена, получить индекс максимального столбца тоже невозможно. Я не виноват!!!!
        $name_max_col = $this->PHPExcel->getSheet($Sheat)->getHighestColumn();
        $coll_max = 0;//максимальный заюзанный столбец.

        do {
            $coll_max++;
        } while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn() != $name_max_col);

        $coll_max++;
        $Row_Max = 1;

        while($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none"
           && $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  === "none") {
            $Row_Max++;
        }

        $Row_Max++;
        $matches[0] = false;

        for($i = 1; $i < $Row_Max; $i++)
        {
            for($k = 0; $k < $coll_max; $k++)
            {
                preg_match("/Заочного|Вечернего|Второго|Инженерно|Автомеханического/iu", $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i), $matches);

                if($matches)
                {
                    switch($matches[0])
                    {
                        case "Заочного":          return 2;
                        case "Вечернего":         return 1;
                        case "Второго":           return 3;
                        case "Инженерно":         return 0;
                        case "Автомеханического": return 0;
                        default : break;
                    }
                }
            }
        }
    }

    // === Проверить, есть ли правая граница
    private function hasRightBorder($sheet, $rx, $cx) {
        $currentCellHasRightBorder = $sheet->getCellByColumnAndRow($cx, $rx)->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";
        $nextCellHasLeftBorder = $sheet->getCellByColumnAndRow($cx + 1, $rx)->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
        return ( $currentCellHasRightBorder || $nextCellHasLeftBorder );
    }
    
    // === Проверить, есть ли нижняя граница
    private function hasBottomBorder($sheet, $rx, $cx) {
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
    private function inspectLocation($sheet, $rx, $cx)
    {        
        $summary = array(               // выходные данные локации:
            'width' => '',              // ширина (в ячейках)
            'height' => '',             // высота (в ячейках)
            'offset' => 0               // смещение в столбцах до внутренней границы
        );
        $row = $rx; // начальная строка
        $col = $cx; // начальный столбец
        
        // находим правую границу локации        
        while ( ! $this->hasRightBorder($sheet, $rx, $col) ) $col++;
        $summary['width'] = $col - $cx + 1;
        // ищем нижнюю границу локации начиная со второй строки
        $row++;
        while ( ! $this->hasBottomBorder($sheet, $row - 1, $col) ) {
            // в это же время поглядываем на правую границу
            if ( ! $this->hasRightBorder($sheet, $row, $col) ) {
                // если справа "дырка", то фиксируем это в протокол,
                // смещаемся до правой границы, корректируем ширину и падаем на дно 
                $summary['offset'] = $col - $cx + 1;     
                while ( ! $this->hasRightBorder($sheet, $row, $col) ) $col++;
                $summary['width'] = $col - $cx + 1;
                while ( ! $this->hasBottomBorder($sheet, $row, $col) ) $row++;             
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
                if ( $this->hasRightBorder($sheet, $r, $c) ) {
                    $summary['offset'] = $c - $cx + 1;                
                    return $summary;
                }
            }
        }
        return $summary;
    }
    
    // Читает ячейку
    private function extractLocation($sheet, $rx, $h, $cx, $w)
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
    
    // Определяет границы таблицы, а так же ширину колонки для группы. Устанавливает глобальные переменные
    private function lookupTableGeometry(&$sheet)
    {        
        $this->iTableFirstRow = 0;
        // находим начало таблицы (обнаруживаем верхнюю границу)
        do {
            $currentCellHasNoBottomBorder = $sheet->getCellByColumnAndRow(0, $this->iTableFirstRow)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none";
            $nextCellHasNoTopBorder = $sheet->getCellByColumnAndRow(0, $this->iTableFirstRow + 1)->getStyle()->getBorders()->getTop()->getBorderStyle() === "none";
            $this->iTableFirstRow++;
        }        
        while ( $currentCellHasNoBottomBorder && $nextCellHasNoTopBorder );

        //$this->iTableFirstRow++; // шагаем внутрь таблицы
        
        // ищем строку с датами (должна располагаться начиная со второго столбца)
        // поиск производится по цвету: белый или бесцветный - это оно
        $this->iDatesMatrixFirstRow = $this->iTableFirstRow + 1;
        do { 
            $currentCellIsNotWhite = $sheet->getCellByColumnAndRow(1, $this->iDatesMatrixFirstRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF";
            $currentCellIsNotTransparent = $sheet->getCellByColumnAndRow(1, $this->iDatesMatrixFirstRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000";
            $this->iDatesMatrixFirstRow++;
        }
        while ( $currentCellIsNotWhite && $currentCellIsNotTransparent );
        $this->iDatesMatrixFirstRow--;

        // ищем последнюю строку таблицы (обнаруживаем нижнюю границу)
        // граница ловится на смене цвета фона в первом столбце на белый #FFFFFF или бесцветный #000000
        $this->iFinalRow = $this->iDatesMatrixFirstRow;
        do {
            $currentCellIsNotWhite = $sheet->getCellByColumnAndRow(0, $this->iFinalRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF";
            $currentCellIsNotTransparent = $sheet->getCellByColumnAndRow(0, $this->iFinalRow)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000";
                        
            // находим серую границу между первой и второй неделями и рубим эту строку
            $nextRightCellIsNotWhite = $sheet->getCellByColumnAndRow(1, $this->iFinalRow + 1)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF";
            $nextRightCellIsNotTransparent = $sheet->getCellByColumnAndRow(1, $this->iFinalRow + 1)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000";
            
            if ( $nextRightCellIsNotWhite && $nextRightCellIsNotTransparent ) {
                //throw new Exception("Removed: " . ($this->iFinalRow + 1));
                $sheet->removeRow($this->iFinalRow + 1, 1);                
            }
            else {
                $this->iFinalRow++;
            }
            
            //$this->iFinalRow++;
        }
        while ( $currentCellIsNotWhite && $currentCellIsNotTransparent );
        $this->iFinalRow--;
        
        // ищем название группы в первой попавшейся ячейке
        $groupTemplate = '/В[А-Я]{1,3}-(\d|\d{3})/u';
        do {
            $cellContent = trim($sheet->getCellByColumnAndRow($this->iDataFirstCol, $this->iTableFirstRow));
            $this->iDataFirstCol++;
        }
        while ( !preg_match($groupTemplate, $cellContent) );
        $this->iDataFirstCol--;

        // рассчитываем ширину на группу по первой ячейке для группы
        $c = $this->iDataFirstCol;
        while ( true )
        {
            $c++;
            $this->iGroupWidth++;
            $cellContent = trim($sheet->getCellByColumnAndRow($c + 1, $this->iTableFirstRow));
            if ( !empty($cellContent) )
                break;
        }

        // узнаём правую границу таблицы (последний столбец)
        $this->iTableLastCol = $this->iDataFirstCol;
        do {
            /*
            $currentCellHasLeftBorder = $sheet->getCellByColumnAndRow($this->iTableLastCol, $this->iTableFirstRow)->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
            $prevCellHasRightBorder = $sheet->getCellByColumnAndRow($this->iTableLastCol + 1, $this->iTableFirstRow)->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";            
            */            
            $currentCellHasRightBorder = $sheet->getCellByColumnAndRow($this->iTableLastCol + $this->iGroupWidth - 1, $this->iTableFirstRow)->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";            
            $nextCellHasLeftBorder = $sheet->getCellByColumnAndRow($this->iTableLastCol + $this->iGroupWidth, $this->iTableFirstRow)->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
            $this->iTableLastCol += $this->iGroupWidth;
        }
        while ( $currentCellHasRightBorder || $nextCellHasLeftBorder );
        $this->iTableLastCol -= $this->iGroupWidth;
    }
    
    // Распознавание групп. Объявляет глобальные переменные
    private function group_init_d($Coll_Start, $Coll_End, $Row_Start, $Sheat, $Shirina_na_gruppu)
    {
        $gr_cl = 0;

        for ( $i = $Coll_Start; $i < $Coll_End; $i += $Shirina_na_gruppu )
        {
            
            $groupName = trim($this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));            
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
        $k = 0;        
        for ( $i = $iDatesMatrixFirstRow; $i < $iFinalRow; $i++ )
        {
            $currentCellHasBottomBorder = $sheet->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none";
            $nextCellHasTopBorder = $sheet->getCellByColumnAndRow(0, $i + 1)->getStyle()->getBorders()->getTop()->getBorderStyle() !== "none";
            
            // если наткнулись на границу
            if ( $currentCellHasBottomBorder || $nextCellHasTopBorder )
            {    
                $this->dayLimitRowIndexes[$k] = $i + 1;
                $k++;
            }
        }
        //throw new Exception(implode(',', $this->dayLimitRowIndexes) . ' last row: ' . $this->iFinalRow);
    }
    
    // Заполняет массив с датами
    private function gatherDates($sheet, $iDataFirstColumn, $iTableFirstRow, $iDateMatrixFirstRow)
    {
        for ( $k = 1; $k < $iDataFirstColumn - 1; $k++ )
        {
            $this->dates_massiv[$k-1]["month"] = trim($sheet->getCellByColumnAndRow($k, $iTableFirstRow));
            $this->dates_massiv[$k-1]["date"] = array();

            $i = $iDateMatrixFirstRow;
            $n = count($this->dayLimitRowIndexes);
            for ( $p = 0; $p < $n; $p++ )
            {
                $this->dates_massiv[$k-1]["date"][$p] = "";

                for (; $i < $this->dayLimitRowIndexes[$p]; $i++)
                {
                    if (trim($sheet->getCellByColumnAndRow($k, $i)) != "")
                        $this->dates_massiv[$k-1]["date"][$p] .= trim($sheet->getCellByColumnAndRow($k, $i))."|";
                }
                $i = $this->dayLimitRowIndexes[$p];                
            }
        }
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
            $this->Group = array();//массив с данными.
            $this->iGroupWidth = 1;//Число ячеек, отведённых на одну группу.
            $this->dayLimitRowIndexes = false; //массив хранит границы дней недели
            $this->dates_massiv = false;
            
            $this->Order_66($s);
            $this->lookupTableGeometry($sheet);
            $this->Group_init_d($this->iDataFirstCol, $this->iTableLastCol, $this->iTableFirstRow, $s, $this->iGroupWidth);
            $this->lookupDayLimitRowIndexes($sheet, $this->iDatesMatrixFirstRow, $this->iFinalRow);
            $this->gatherDates($sheet, $this->iDataFirstCol, $this->iTableFirstRow, $this->iDatesMatrixFirstRow);

            for ( $i = $this->iDatesMatrixFirstRow; $i < $this->iFinalRow; $i++ )
            {
                // пропускаем скрытые строки
                if ( !$sheet->getRowDimension($i)->getVisible() )
                    continue;

                for( $k = $this->iDataFirstCol; $k < $this->iTableLastCol; $k++ )
                {                        
                    // пропускаем скрытые столбцы
                    if ( !$sheet->getColumnDimension($k)->getVisible() )
                        continue;

                    $bLeft = $sheet->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle() != "none";
                    $bRight = $sheet->getCellByColumnAndRow($k - 1, $i)->getStyle()->getBorders()->getRight()->getBorderStyle()!= "none";
                    $bTop = $sheet->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle() != "none";
                    $bBottom = $sheet->getCellByColumnAndRow($k, $i - 1)->getStyle()->getBorders()->getBottom()->getBorderStyle() != "none";
                    // эксплорим занятие (спускаемся в клетку)
                    // если в текущей ячейке точно есть левая и верхняя границы
                    if ( ( $bLeft || $bRight ) && ( $bTop || $bBottom ) )
                    {
                        if ( $sheet->getCellByColumnAndRow($k, $i) == '' ) continue;
                        $layout = $this->inspectLocation($sheet, $i, $k);
                        $meetings = array();
                        if ( $layout['offset'] ) {
                            $w1 = $layout['offset'];
                            $w2 = $layout['width'] - $w1;
                            $res1 = $this->extractLocation($sheet, $i, $layout['height'], $k, $w1);
                            $res2 = $this->extractLocation($sheet, $i, $layout['height'], $k + $w1, $w2);
                            $basis = array('discipline', 'type', 'room', 'lecturer');
                            $areEqual = true;
                            foreach ( $basis as $el )
                                $areEqual &= empty($res1[$el]) ^ empty($res2[$el]);
                            if ( $areEqual ) {
                                foreach ( $basis as $el )
                                    if ( empty($res1[$el]) ) $res1[$el] = $res2[$el];
                                $res1['comment'] = trim($res1['comment'] . ' ' . $res2['comment']);
                                $this->postProcessLocationData($res1, $i);
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res1);
                            }
                            else {
                                /*
                                echo var_dump($res1);
                                echo var_dump($res2);
                                throw new Exception();
                                */
                                $this->postProcessLocationData($res1, $i);
                                $this->postProcessLocationData($res2, $i);
                                $meetings[] = new Meeting();
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res1);
                                $meetings[1]->initFromArray($res2);
                                $this->crossFillItems($meetings[0], $meetings[1]);
                            }
                        }
                        else {
                            $res = $this->extractLocation($sheet, $i, $layout['height'], $k, $layout['width']);
                            $this->postProcessLocationData($res, $i);
                            $meetings[] = new Meeting();
                            $meetings[0]->initFromArray($res);
                        }
                        
                        
                        if ( empty($meetings[0]->discipline) ) {
                            $k += $layout['width'] - 1;
                            continue;
                        }
                        
                        // индекс текущей группы в массиве Group
                        $gid = floor(($k - $this->iDataFirstCol) / $this->iGroupWidth);
                                       
                        // номер пары
                        $offset = $this->lookupLocationOffset($sheet, $this->iDataFirstCol, $i);

                        // количество групп, задействованных в занятии
                        $groupsCount = ceil($layout['width'] / $this->iGroupWidth);
                        
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
                        
                        /*
                            // если это вторая подгруппа
                            if ( $groupsCount == 0
                                && !is_int( ($k - $this->iDataFirstCol + $this->iGroupWidth) / $this->iGroupWidth) )
                            {
                                // Иногда случается так, что преподаватель, аудитория и тип занятия одинаковы
                                // для двух подгрупп и указаны в самом низу половинчатой клетки в строке.
                                // Эта строка занимает клетку по ширине полностью. Поэтому, вначале разбирается
                                // одна часть клетки: дисциплина, даты + преподаватель и аудитория. Затем
                                // другая: дисциплина, даты + тип занятия. После чего недостающие данные
                                // взаимно переливаются из смежных занятий
                                // 
                                // Пример:
                                // Математика        | Физика
                                // 25.03, 14.04     | 18.03, 3.04
                                //                  |
                                // Габдулхакова        Б-401    пр.
                                // 
                                $n = count($this->Group[$gid]["Para"]) - 1;
                                $prevMeeting = $this->Group[$gid]["Para"][$n];                           
                                if ( $prevMeeting->offset >= $meeting->offset )
                                    $this->crossFillItems($meeting, $prevMeeting);
                                // но, опять же, если в предыдущем записана физ-ра (что тоже маловероятно, то функция захуярит туда тип занятия и аудиторию)       
                            }
                            if ( $groupsCount == 0 )
                                    $groupsCount = 1;
                            */

                        
                    }
                }
            }
        }
    }
    
    // Пост-обработка данных, полученных из локации
    private function postProcessLocationData(&$data, $rx) {
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
                for ( $d = 0; $d < count($this->dates_massiv); $d++ )
                {
                    $moun = $this->Mesac_to_chislo($this->dates_massiv[$d]["month"]);
                    $f = 0;

                    // находим индекс текущего дня в таблице дат
                    while ( $rx >= $this->dayLimitRowIndexes[$f] )
                        $f++;

                    // даты для текущего дня
                    $dart = $this->dates_massiv[$d]["date"][$f];
                    $dart = explode("|", $dart);

                    for ( $l = 0; $l < count($dart) - 1; $l++ )
                        $data['dates'] .= $dart[$l] . "." . $moun . ",";
                }    
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
    
    //----------------------------------------------------------------------//Функции для заочного распсиания
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
    
    ///////////////////////////////////////////
    public function parsing($file_name)
    {
        try {
            $this->PHPExcel = PHPExcel_IOFactory::load($file_name);
            $this->type_stady = 0;

            switch ($this->type_stady)
            {
                case 0:  $this->get_day_raspisanie(); break;//расписание дневное - фас!
                case 1:  $this->get_day_raspisanie(); break;//распсиание вечернее - фас!
                default: return false;
            }

            return true;
        }
        catch(Exception $e)
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