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

    // Читает ячейку
    private function read_cell($Staret_Row, $Start_Coll, $Sheet)
    {
        // Начальный столбец
        $row = $Staret_Row;
        // Массив результатов
        $result = array("", "", "", "", "", "", 0, 0);
        // Начальная строка
        $coll = $Start_Coll;
        $shirina = 0;
        // Находим границы ячейки
        while(!($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getBorders()->getRight()->getBorderStyle()  != "none"
             || $this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll+1, $row)->getStyle()->getBorders()->getLeft()->getBorderStyle() != "none")) {
            $coll++;
            $shirina++;
        }
        $row = $Staret_Row - 1;
        // Цикл по строкам
        do
        {
            $row++;
            $coll = $Start_Coll - 1;
            // Цикл по столбцам
            do
            {
                $coll++;
                // Если ячейка не пуста
                if(trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)) != "")
                {
                    // Записываем значение ячейки
                    $str = trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row));
                    // если текст помечен жирным, то это название дисциплины
                    
                    if($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getBold() == 1)
                    {                        
                        // конкатенация для тех случаев, когда название дисциплины
                        // продолжается в следующей ячейке
                        $result[0] .= " " . $str;
                    }
                    else
                    {
                        // Поиск типа занятия
                        // 
                        if(preg_match("/(?:^|\s)(лаб|лек|пр)\s*\.?/u", $str, $maches))
                        {
                            $result[1] = $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        
                        // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты
                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $maches) )
                        {
                            $result[5] .= " " . $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        
                        // Поиск аудитории
                        
                        if ( preg_match_all("/(?:(?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|Гараж\s№\s?3)/u", $str, $maches, PREG_PATTERN_ORDER) )
                        {
                            // Если совпадений больше 1
                            if(count($maches[0]) > 1)
                            {
                                $result[2] = $maches[0][0];
                                $str = str_replace($maches[0][0], "", $str);
                                $result[2] = str_replace(" ", "", $result[2]);
                                /* если больше одного дефиса
                                preg_match("/-+/", $str, $mac);
                                $result[2] = str_replace($mac[0], "-", $result[2]);
                                */
                                                                
                                // всё остальное - в комментарий
                                for($i = 1; $i < count($maches[0]); $i++)
                                {
                                    $result[5] .= $maches[0][$i];
                                    $str = str_replace($maches[0][$i], "", $str);
                                }
                            }
                            else
                            {
                                $result[2] = $maches[0][0];
                                $result[2] = str_replace(" ", "", $result[2]);
                                /* если больше одного дефиса 
                                preg_match("/-+/", $str, $mac);
                                $result[2] = str_replace($mac[0], "-", $result[2]);
                                */
                                $str = str_replace($maches[0][0], "", $str);
                            }
                            $str = trim($str);
                        }
                        // Поиск даты подгрупп
                        //if(preg_match("/(\d{1,2}.\d{1,2}(,)?( )*){2,}/", $str, $maches))
                        if ( preg_match('/(\d{1,2}\.\d{2}(,\s?|$))+/', $str, $maches) )
                        {
                            $result[3] = $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        
                        //$str = 'Мозговая 1 п/г';
                        //if ( strpos($str, 'Мозговая') !== false ) throw new Exception("|" . $str . "|");
                        //if ( (strpos($str, 'Мозговая') !== false) && (strpos($result[2], 'А-18') !== false) ) throw new Exception("|" . $str . "|");
                        //if ( (strpos($str, 'Мозговая') !== false) ) throw new Exception("|" . $str . "|");
                        
                        // Поиск преподавателя
                        // /(([А-Я](\.)?( )*){0,2}[А-Я][а-я]+)( )*(([А-Я](\.)?( )*){0,2})/ui
                        if ( preg_match("/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u", $str, $maches) )
                        {
                            //if ( (strpos($maches[0], 'Мозговая') !== false) && ($result[2]==="Д-207") ) throw new Exception("|" . $str . "|");
                            $result[4] = $maches[0];
                            $str= str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                        if(trim($str) != "")
                            $result[5] .= $str . " ";
                    }
                }
            }
            while($coll < $Start_Coll + $shirina);
        }
        while(!($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle() != "none"
             || $this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  != "none"));
        $result[6] = $row  - $Staret_Row + 1;
        $result[7] = $coll - $Start_Coll + 1;
        //if ( ($result[4] == "Хаирова") && ( strpos($result[5], '10.00-')  !== FALSE ) ) throw new Exception(implode('|', $result));
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
    
    // Получить номер пары
    private function get_par_number($rows, $Coll_Start, $Sheat, &$meeting)
    {
        $k = 0;

        do
        {
            $str = $this->PHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start - 1, $rows + $k);
            $str = trim($str);
            str_replace(" ", "", $str);
            $k++;
        }
        while($str == "");

        $matches[0] = false;
        preg_match("/-+/iu", $str, $matches);
        
        if(count($matches) > 0)
            $str = str_replace($matches[0], "-", $str);

        switch ($str)
        {
            case "1-2":   $meeting->offset = 0;                           break;
            case "3-4":   $meeting->offset = 1;                           break;
            case "5-6":   $meeting->offset = 2;                           break;
            case "7-8":   $meeting->offset = 3;                           break;
            case "9-10":  $meeting->offset = 4;                           break;
            case "11-12": $meeting->offset = 5;                           break;
            case "13-14": $meeting->offset = 6;                           break;
            case "15-16": $meeting->offset = 7;                           break;
            case "8-00":  $meeting->offset = 0;  $meeting->comment.= $str; break;
            case "9-40":  $meeting->offset = 1;  $meeting->comment.= $str; break;
            case "11-20": $meeting->offset = 2;  $meeting->comment.= $str; break;
            case "13-00": $meeting->offset = 3;  $meeting->comment.= $str; break;
            case "14-40": $meeting->offset = 4;  $meeting->comment.= $str; break;
            case "16-20": $meeting->offset = 5;  $meeting->comment.= $str; break;
            case "18-00": $meeting->offset = 6;  $meeting->comment.= $str; break;
            case "19-30": $meeting->offset = 7;  $meeting->comment.= $str; break;
            default:      $meeting->offset = -1;                          break;
        }
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
    private  function lookupDayLimitRowIndexes($sheet, $iDatesMatrixFirstRow, $iFinalRow)
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
    private  function gatherDates($sheet, $iDataFirstColumn, $iTableFirstRow, $iDateMatrixFirstRow)
    {
        for ( $k = 1; $k < $iDataFirstColumn - 1; $k++ )
        {
            $this->date_massiv[$k-1]["month"] = trim($sheet->getCellByColumnAndRow($k, $iTableFirstRow));
            $this->date_massiv[$k-1]["date"] = array();

            $i = $iDateMatrixFirstRow;
            $n = count($this->dayLimitRowIndexes);
            for ( $p = 0; $p < $n; $p++ )
            {
                $this->date_massiv[$k-1]["date"][$p] = "";

                for (; $i < $this->dayLimitRowIndexes[$p]; $i++)
                {
                    if (trim($sheet->getCellByColumnAndRow($k, $i)) != "")
                        $this->date_massiv[$k-1]["date"][$p] .= trim($sheet->getCellByColumnAndRow($k, $i))."|";
                }
                $i = $this->dayLimitRowIndexes[$p];                
            }
        }
    }
    
    // Анализирует дневное распсиание
    private  function get_day_raspisanie()
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
            $this->date_massiv = false;
            
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
                        $res = $this->read_cell($i, $k, $s);
                        // индекс текущей группы в массиве Group
                        $nau = floor(($k - $this->iDataFirstCol) / $this->iGroupWidth);
                        //Если есть название предмета
                        if($res[0] != "")
                        {
                            /*
                            $nau_par_count = count($this->Group[$nau]["Para"]);
                            //Проверяем, если у нас пара не первая
                            if($nau_par_count > 0)
                            {
                                $Prev_par = $nau_par_count - 1;
                                //если у предыдущей пары нет предмета
                                if($this->Group[$nau]["Para"][$Prev_par]->discipline == false) {
                                    //Вытягиваем предыдущую пару на заполнение.
                                    $meeting = array_pop($this->Group[$nau]["Para"]);
                                } else {
                                    // Иначе создаём новую пару.
                                    $meeting = new Meeting();
                                }
                            }
                            else // если у нас первая пара
                            {
                                $Prev_par = $nau_par_count;
                                $meeting = new Meeting();
                            }
                            */
                            
                            $meeting = new Meeting();
                            
                            //если у нас нет дат в ячейке
                            if ( $res[3] == "" )
                            {
                                for ( $d = 0; $d < count($this->date_massiv); $d++ )
                                {
                                    $moun = $this->Mesac_to_chislo($this->date_massiv[$d]["month"]);
                                    $f = 0;

                                    // находим индекс текущего дня в таблице дат
                                    while ( $i >= $this->dayLimitRowIndexes[$f] )
                                        $f++;

                                    // даты для текущего дня
                                    $dart = $this->date_massiv[$d]["date"][$f];
                                    $dart = explode("|", $dart);

                                    for ( $l = 0; $l < count($dart) - 1; $l++ )
                                        $meeting->date .= $dart[$l] . "." . $moun . ",";
                                }
                            }
                            else { // если даты в ячейке есть                                
                                $meeting->date = $res[3];
                            }

                            $meeting->discipline    = trim($res[0]);
                            $meeting->type          = trim($res[1]);
                            $meeting->room          = trim($res[2]);
                            $meeting->lecturer      = trim($res[4]);
                            $meeting->comment       = trim($res[5]);                            
                            
                            //if ( ($meeting->lecturer == "Хаирова") && ($meeting->offset == 0) ) throw new Exception("type: " . $meeting->type);
                            
                            $this->get_par_number($i, $this->iDataFirstCol, $s, $meeting);

                            // количество групп, задействованных в занятии
                            $groupsCount = floor($res[7] / $this->iGroupWidth);
                            
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
                                $n = count($this->Group[$nau]["Para"]) - 1;
                                $prevMeeting = $this->Group[$nau]["Para"][$n];                           
                                if ( ($prevMeeting->offset + $i) >= $i )
                                    $this->crossFillItems($meeting, $prevMeeting);
                                // но, опять же, если в предыдущем записана физ-ра (что тоже маловероятно, то функция захуярит туда тип занятия и аудиторию)
                                //if ( ($meeting->lecturer == "Хаирова") && ($meeting->offset == 0) ) throw new Exception("type: " . $meeting->type);
                            }
                            if ( $groupsCount == 0 )
                                    $groupsCount = 1;
                            
                            // количество занятий
                            $meetingsCount = floor($res[6] / 2); // Todo: на основе размера ячейки с указанием номера пары (то есть вместо "двойки" определить количество строк, фактически занимаемых парой)
                            
                            for ( $l = 0; $l < $groupsCount; $l++ )
                            {      
                                for ( $z = 0; $z < $meetingsCount; $z++ )
                                {
                                    $m = new Meeting();
                                    $m->copyFrom($meeting);
                                    $m->offset += $z;
                                    $m->group = $this->Group[$nau + $l]["NameGroup"];
                                    array_push($this->Group[$nau + $l]["Para"], $m);
                                    //if ( ($m->lecturer === "Хаирова") && ($m->offset === 1) ) throw new Exception(implode('&bull;', (array)$m) . ' and ' . implode('&bull;', (array)$meeting));
                                }
                            }
                        }       
                    }
                }
            }
        }
    }
    
    // Взаимодополнить поля двух занятий
    private function crossFillItems($m1, $m2) {
        // переливаем в текущую из предыдущей
        if ( empty($m1->room)       ) $m1->room = $m2->room;
        if ( empty($m1->lecturer)   ) $m1->lecturer = $m2->lecturer;
        if ( empty($m1->comment)    ) $m1->comment = $m2->comment;
        if ( empty($m1->type)       ) $m1->type = $m2->type;

        // из текущей в предыдущую
        if ( empty($m2->room)       ) $m2->room = $m1->room;
        if ( empty($m2->lecturer)   ) $m2->lecturer = $m1->lecturer;
        if ( empty($m2->comment)    ) $m2->comment = $m1->comment;
        if ( empty($m2->type)       ) $m2->type = $m1->type;      
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
        $this->date_massiv;//инициализируется, предварительно обнуляется.
        $this->dayLimitRowIndexes;
        $this->PHPExcel;
        $this->date_massiv = false;
        $this->date_massiv[0]["month"] = false;
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

                if(isset($this->date_massiv[0]["month"][$k])) {
                    $this->date_massiv[0]["month"][$k] .= trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                } else {
                    $this->date_massiv[0]["date"][$k] = trim($this->PHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
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