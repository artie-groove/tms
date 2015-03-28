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
        $name_max_col = $this->objPHPExcel->getSheet($Sheat)->getHighestColumn();
        // Максимальный заюзанный столбец.
        $coll_max = 0;

        do {
            $coll_max++;
        } while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn() != $name_max_col);

        $coll_max++;
        $killed = 0;
        $i = 0;

        while($i < $coll_max - $killed)
        {
            if($this->objPHPExcel->getSheet()->getColumnDimensionByColumn($i)->getVisible() != "") {
                $i++;
            } else {
                $this->objPHPExcel->getSheet()->removeColumnByIndex($i);
                $killed++;
            }
        }
    }

    private function get_typ_raspisania($Sheat)
    {
        // Здесь начинается лютый, беспросветный полярный лис. Функция перевода имени столбца в индекс не найдена, получить индекс максимального столбца тоже невозможно. Я не виноват!!!!
        $name_max_col = $this->objPHPExcel->getSheet($Sheat)->getHighestColumn();
        $coll_max = 0;//максимальный заюзанный столбец.

        do {
            $coll_max++;
        } while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn() != $name_max_col);

        $coll_max++;
        $Row_Max = 1;

        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  === "none") {
            $Row_Max++;
        }

        $Row_Max++;
        $matches[0] = false;

        for($i = 1; $i < $Row_Max; $i++)
        {
            for($k = 0; $k < $coll_max; $k++)
            {
                preg_match("/Заочного|Вечернего|Второго|Инженерно|Автомеханического/iu", $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i), $matches);

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
        while(!($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getBorders()->getRight()->getBorderStyle()  != "none"
             || $this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll+1, $row)->getStyle()->getBorders()->getLeft()->getBorderStyle() != "none")) {
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
                if(trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)) != "")
                {
                    // Записываем значение ячейки
                    $str = trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row));
                    // Если стиль "жирный"
                    if($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getBold() == 1)
                    {
                        // Поиск комментария (с 18:00)
                        if(preg_match("/(с|c)( )+\d{1,2}[-:\.]\d{2}/iu", $str, $matches) != 0)
                        {
                            $str = str_replace($matches[0], "", $str);
                            $result[5] .= " " . $matches[0];
                        }
                        // Название занятия
                        $result[0] .= " " . $str;
                    }
                    else
                    {
                        // Поиск типа занятия
						///(^| )(лаб(( )*\.)?|лек(( )*\.)?|пр(( )*\.)?)( |$)/ui
                        if(preg_match("/(?:^|\s)(лаб|лек|пр)\s*\.?/u", $str, $maches))
                        {
                            $result[1] = $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        // Поиск даты занятия
                        if(preg_match("/(с|c)?( )*\d{1,2}\.\d\d-\d{1,2}\.\d\d/", $str,$maches))
                        {
                            $result[5] .= " " . $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        // Поиск аудитории
                        // /[А-я]+( )*-+( )*\d+/
                        if(preg_match_all("/[А-я]+\s*-*\s*\d+/", $str, $maches, PREG_PATTERN_ORDER))
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
                                /* то же самое 
                                preg_match("/-+/", $str, $mac);
                                $result[2] = str_replace($mac[0], "-", $result[2]);
                                */
                                $str = str_replace($maches[0][0], "", $str);
                            }
                            $str = trim($str);
                        }
                        // Поиск даты подгрупп
                        if(preg_match("/(\d{1,2}.\d{1,2}(,)?( )*){2,}/", $str, $maches))
                        {
                            $result[3] = $maches[0];
                            $str = str_replace($maches[0], "", $str);
                            $str = trim($str);
                        }
                        // Поиск преподавателя
                        // /(([А-Я](\.)?( )*){0,2}[А-Я][а-я]+)( )*(([А-Я](\.)?( )*){0,2})/ui
                        if(preg_match("/[А-Я][а-я]+\s*([А-Я]\.\s*){0,2}/u", $str, $maches))
                        {
                            $result[4]=$maches[0];
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
        while(!($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle() != "none"
             || $this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  != "none"));
        $result[6] = $row  - $Staret_Row + 1;
        $result[7] = $coll - $Start_Coll + 1;
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
    private function get_par_number($rows, $Coll_Start, $Sheat, &$NewPar)
    {
        $k = 0;

        do
        {
            $str = $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start - 1, $rows + $k);
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
            case "1-2":   $NewPar->offset = 1;                           break;
            case "3-4":   $NewPar->offset = 2;                           break;
            case "5-6":   $NewPar->offset = 3;                           break;
            case "7-8":   $NewPar->offset = 4;                           break;
            case "9-10":  $NewPar->offset = 5;                           break;
            case "11-12": $NewPar->offset = 6;                           break;
            case "13-14": $NewPar->offset = 7;                           break;
            case "15-16": $NewPar->offset = 8;                           break;
            case "8-00":  $NewPar->offset = 1;  $NewPar->comment.= $str; break;
            case "9-40":  $NewPar->offset = 2;  $NewPar->comment.= $str; break;
            case "11-20": $NewPar->offset = 3;  $NewPar->comment.= $str; break;
            case "13-00": $NewPar->offset = 4;  $NewPar->comment.= $str; break;
            case "14-40": $NewPar->offset = 5;  $NewPar->comment.= $str; break;
            case "16-20": $NewPar->offset = 6;  $NewPar->comment.= $str; break;
            case "18-00": $NewPar->offset = 7;  $NewPar->comment.= $str; break;
            case "19-30": $NewPar->offset = 8;  $NewPar->comment.= $str; break;
            default:      $NewPar->offset = -1;                          break;
        }
    }
    
    // Определяет границы таблицы, а так же ширину колонки для группы. Устанавливает глобальные переменные
    private function get_orientirs_d($Sheat)
    {        
        // находим начало таблицы (обнаруживаем верхнюю границу)
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  === "none") {
            $this->Row_Start++;
        }

        $this->Row_Start++; // шагаем внутрь таблицы
        
        // ищем строку с датами (должна располагаться начиная со второго столбца)
        // поиск производится по цвету: белый или бесцветный - это оно
        $this->Row_Start_Date = $this->Row_Start + 1;
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->Row_Start_Date++;
        }

        // ищем последнюю строку таблицы (обнаруживаем нижнюю границу)
        // граница ловится на смене цвета фона в первом столбце на белый #FFFFFF или бесцветный #000000
        $this->Row_End = $this->Row_Start_Date;
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->Row_End++;
        }
        
        // Поиск названия группы в ячейке
        while (!preg_match("/[А-Яа-я]+( )*-+( )*\d\d\d/", trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_Start, $this->Row_Start)))) {
            $this->Coll_Start++;
        }

        $count_z = 0;
        $coll = $this->Coll_Start;
        // Рассчитываем ширину на группу по первой ячейке для группы.
        // пиздец какой-то.
        while($count_z < 1)
        {
            $coll++;
            $this->Shirina_na_gruppu++;
            if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll + 1, $this->Row_Start)) != "")
                $count_z++;
        }

        $this->Coll_End = $this->Coll_Start;

        while(($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle()    !== "none"
            || $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End-1, $this->Row_Start)->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none")) {
            $this->Coll_End += $this->Shirina_na_gruppu;
        }

        $this->Coll_End -= $this->Shirina_na_gruppu;
    }
    
    // Распознавание групп. Объявляет глобальные переменные
    private function group_init_d($Coll_Start, $Coll_End, $Row_Start, $Sheat, $Shirina_na_gruppu)
    {
        $gr_cl = 0;

        for($i = $Coll_Start; $i < $Coll_End; $i += $Shirina_na_gruppu)
        {
            $this->Group[$gr_cl]["NameGroup"] = trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));
            $this->Group[$gr_cl]["NameGroup"] = str_replace(" ", "", $this->Group[$gr_cl]["NameGroup"]);
            preg_match("/-+/ui", $this->Group[$gr_cl]["NameGroup"], $matches);

            if(count($matches) > 0)
                $this->Group[$gr_cl]["NameGroup"] =  str_replace($matches[0], "-", $this->Group[$gr_cl]["NameGroup"]);

            $this->Group[$gr_cl]["Para"] = array();
            $gr_cl++;
        }
    }
    
    // Устанавливает грани между днями недели.
    private  function dey_gran_d($Row_Start_Date, $Row_End, $Sheat)
    {
        $this->objPHPExcel;
        $this->gani;//инициализирует
        $k = 0;
        //$this->gani[$k++] = $Row_Start_Date;
        for($i = $Row_Start_Date; $i < $Row_End; $i++)
        {
            if($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none"
            || $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  !== "none")
            {
                // определяет, что в группе ячеек записан день недели по наличию соседства с белой ячейкой справа
                if($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB() === "FFFFFF"
                || $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB() === "000000") {
                    $this->gani[$k] = $i + 1;
                    $k++;
                }
            }
        }
    }
    
    // Заполняет массив с датами.
    private  function get_mounday_d($Coll_Start,$Row_Start,$Sheat,$Row_Start_Date)
    {
        for($k = 1; $k < $Coll_Start - 1; $k++)
        {
            $this->date_massiv[$k-1]["month"] = trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $Row_Start));
            $this->date_massiv[$k-1]["date"] = array();

            $i = $Row_Start_Date;
            for($p = 0; $p < count($this->gani); $p++)
            {
                $this->date_massiv[$k-1]["date"][$p] = "";

                //$i = ( $p === 0 ? $Row_Start_Date : $this->gani[$p-1] );
                //throw new Exception($Row_Start_Date . ' vs. ' . $this->gani[$p]);
                
                
                for (; $i < $this->gani[$p]; $i++)
                {
                    if (trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)) != "")
                        $this->date_massiv[$k-1]["date"][$p] .= trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))."|";
                }
                $i = $this->gani[$p];                
            }
        }
    }
    
    // Анализирует дневное распсиание.
    private  function get_day_raspisanie()
    {        
        $sheetsTotal = $this->objPHPExcel->getSheetCount();
        for($s = 0; $s < $sheetsTotal; $s++)
        {
            $sheet = $this->objPHPExcel->getSheet($s);
            $this->Coll_Start = 1;//начало таблицы (непосредственно данных)
            $this->Coll_End = 1;//за концом таблицы
            $this->Row_Start = 0;//начало таблицы
            $this->Row_End = 0;//за концом таблицы
            $this->Row_Start_Date = 0;//начало данных
            $this->Group = array();//массив с данными.
            $this->Shirina_na_gruppu = 1;//Число ячеек, отведённых на одну группу.
            $this->gani = false; //массив хранит границы дней недели
            $this->date_massiv = false;
            
            $this->Order_66($s);
            $this->get_orientirs_d($s);
            $this->Group_init_d($this->Coll_Start, $this->Coll_End, $this->Row_Start, $s, $this->Shirina_na_gruppu);
            $this->dey_gran_d($this->Row_Start_Date, $this->Row_End, $s);
            $this->get_mounday_d($this->Coll_Start, $this->Row_Start, $s, $this->Row_Start_Date);

            for ( $i = $this->Row_Start_Date; $i < $this->Row_End; $i++ )
            {
                // пропускаем скрытые строки
                if ( !$sheet->getRowDimension($i)->getVisible() )
                    continue;

                for( $k = $this->Coll_Start; $k < $this->Coll_End; $k++ )
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
                        $nau = floor(($k - $this->Coll_Start) / $this->Shirina_na_gruppu);
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
                                    $NewPar = array_pop($this->Group[$nau]["Para"]);
                                } else {
                                    // Иначе создаём новую пару.
                                    $NewPar = new Pair();
                                }
                            }
                            else // если у нас первая пара
                            {
                                $Prev_par = $nau_par_count;
                                $NewPar = new Pair();
                            }
                            */
                            
                            $NewPar = new Pair();
                            
                            //если у нас нет дат в ячейке
                            if($res[3] == "")
                            {
                                for($d = 0; $d < count($this->date_massiv); $d++)
                                {
                                    $moun = $this->Mesac_to_chislo($this->date_massiv[$d]["month"]);
                                    $f = 0;

                                    // пододвигаем указатель к текущему дню
                                    while($i > $this->gani[$f])
                                        $f++;

                                    // даты для текущего дня
                                    $dart = $this->date_massiv[$d]["date"][$f];
                                    $dart = explode("|", $dart);

                                    for($l = 0; $l < count($dart) - 1; $l++)
                                        $NewPar->date .= $dart[$l] . "." . $moun . ",";
                                }
                            }
                            else {
                                // Если даты в ячейке есть
                                $NewPar->date = $res[3];
                            }

                            $NewPar->discipline    = trim($res[0]);
                            $NewPar->type       = trim($res[1]);
                            $NewPar->room  = trim($res[2]);
                            $NewPar->lecturer     = trim($res[4]);
                            $NewPar->comment    = trim($res[5]);
                            
                            
                            $this->get_par_number($i, $this->Coll_Start, $s, $NewPar);

                            // количество групп, задействованных в занятии
                            $group_count = floor($res[7] / $this->Shirina_na_gruppu);
                            
                            // если это вторая подгруппа
                            if($group_count == 0 && !is_int(($k-$this->Coll_Start + $this->Shirina_na_gruppu) / $this->Shirina_na_gruppu))
                            {
                                //$tmp = ($k-$this->Coll_Start);
                                //if ( $i > 39 ) throw new Exception("Got here: '" . implode('|', $res) . "' @ {R\$$i:C\$$k} ($tmp)");
                                
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
                                $prevLesson = $this->Group[$nau]["Para"][$n];
                                
                                // переливаем в текущую из предыдущей
                                if ( empty($NewPar->room)  ) $NewPar->room = $prevLesson->room;
                                if ( empty($NewPar->lecturer)     ) $NewPar->lecturer = $prevLesson->lecturer;
                                if ( empty($NewPar->comment)    ) $NewPar->comment = $prevLesson->comment;
                                if ( empty($NewPar->type)       ) $NewPar->type = $prevLesson->type;

                                // из текущей в предыдущую
                                if ( empty($prevLesson->room)  ) $prevLesson->room = $NewPar->room;
                                if ( empty($prevLesson->lecturer)     ) $prevLesson->lecturer = $NewPar->lecturer;
                                if ( empty($prevLesson->comment)    ) $prevLesson->comment = $NewPar->comment;
                                if ( empty($prevLesson->type)       ) $prevLesson->type = $NewPar->type;

                                //_______________________________________
                                
                                $par_count = floor($res[6] / 2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.
                                for($d = 0; $d < $par_count; $d++)
                                {
                                    $par_temp = new Pair();
                                    $par_temp->copyFrom($NewPar);
                                    $par_temp->offset += $d;
                                    $par_temp->group = $this->Group[$nau]["NameGroup"];
                                    array_push($this->Group[$nau]["Para"], $par_temp);
                                }
                            }
                            else // если одна и более групп
                            {
                                $par_count = floor($res[6] / 2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.

                                if ( $group_count == 0 )
                                    $group_count = 1;
 
                                for ( $l = 0; $l < $group_count; $l++ )
                                {
                                    /*
                                    // если уже есть хоть одно занятие у текущей группы
                                    if ( count($this->Group[$nau+$l]["Para"]) >= 1 )
                                    {
                                        $n = count($this->Group[$nau+$l]["Para"]) - 1;
                                        $prevLesson = $this->Group[$nau+$l]["Para"][$n];
                                        if ( empty($prevLesson->discipline) )
                                            array_pop($this->Group[$nau+$l]["Para"]);
                                    }
                                    */

                                    for ( $z = 0; $z < $par_count; $z++ )
                                    {
                                        $par_temp = new Pair();
                                        $par_temp->copyFrom($NewPar);
                                        $par_temp->offset += $z;
                                        $par_temp->group = $this->Group[$nau + $l]["NameGroup"];
                                        array_push($this->Group[$nau + $l]["Para"], $par_temp);
                                    }
                                }
                            }
                        }
                        /*
                        else // Названия предмета нет
                        {  
                            // если в клетке что-то содержится
                            if(trim($res[5]) != "")
                            {
                                //if ( $i > 65 ) throw new Exception("No discipline name found: '" . implode('|', $res) . "' @ {R\$$i:C\$$k}");
                                $NewPar = new Pair();
                                $NewPar->discipline = false;
                                $NewPar->comment = $res[5];
                                $NewPar->group = $this->Group[$nau]["NameGroup"];
                                array_push($this->Group[$nau]["Para"], $NewPar);
                            }
                        }
                        */
                    }
                }
            }
        }
    }
    //----------------------------------------------------------------------//Функции для заочного распсиания
    private   function get_orientirs_z($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные.
    {
        $this->objPHPExcel;
        $this->Coll_Start;//начало таблицы (непосредственно данных)//инициализирует
        $this->Coll_End;//за концом таблицы//инициализирует
        $this->Row_Start;//начало таблицы//инициализирует
        $this->Row_End;//за концом таблицы//инициализирует
        $this->Row_Start_Date;//начало данных//инициализирует
        $this->Shirina_na_gruppu;//инициализирует

        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle() === "none"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()  === "none") {
            $this->Row_Start++;
        }

        $this->Row_Start++;
        $this->Row_Start_Date = $this->Row_Start + 1;

        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->Row_Start_Date++;
        }

        $this->Row_End = $this->Row_Start_Date;

        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->Row_End++;
        }

        while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/", trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_Start, $this->Row_Start)))) {
            $this->Coll_Start++;
        }

        $count_z = 0;
        $coll = $this->Coll_Start;

        while($count_z < 1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $this->Shirina_na_gruppu++;
            if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $this->Row_Start)) != "")
                $count_z++;
        }

        $this->Coll_End = $this->Coll_Start;
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB() !== "FFFFFF"
           && $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB() !== "000000") {
            $this->Coll_End++;
        }
    }

    private   function get_section_end($Sheat, $old_end)// находит границу секции. Принимает последнюю обнаруженную границу
    {
        $this->objPHPExcel;
        $this->Row_Start;
        $this->Coll_End;
        $this->Section_Start;//Утсанавливает значение
        $this->Section_end;//устанавливает значение
        $this->Section_date_start;//устанавливается значение
        $this->Section_Start = $old_end;
        $this->Section_end = $old_end + 1;

        while(preg_match("/дни/iu", $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_end, $this->Row_Start)) == 0
            &&($this->Section_end<$this->Coll_End)) {
            $this->Section_end++;
        }

        $this->Section_date_start = $this->Section_Start;

        while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/", trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_date_start, $this->Row_Start)))) {
            $this->Section_date_start++;
        }
    }
    
    private   function get_mounday_z($Row_Start_Date, $Section_Start, $Row_End, $Sheet)
    {
        $this->date_massiv;//инициализируется, предварительно обнуляется.
        $this->gani;
        $this->objPHPExcel;
        $this->date_massiv = false;
        $this->date_massiv[0]["month"] = false;
        for($i = $Row_Start_Date; $i < $Row_End; $i++)
        {
            if(trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i)) != "")
            {
                $k=-1;
                for($k = 0; $k < count($this->gani); $k++)
                {
                    if($this->gani[$k] > $i)
                        break;
                }

                if(isset($this->date_massiv[0]["month"][$k])) {
                    $this->date_massiv[0]["month"][$k] .= trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                } else {
                    $this->date_massiv[0]["date"][$k] = trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                }
            }
        }
    }
    
    ///////////////////////////////////////////
    public function parsing($file_name)
    {
        try {
            $this->objPHPExcel = PHPExcel_IOFactory::load($file_name);
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

            for ($i = 0; $i < count($this->Group); $i++)
                $par_date = array_merge($par_date, $this->Group[$i]["Para"]);

            $this->setStatus("OK", "Парсинг прошёл успешно.");
            return $par_date;
        }
        catch(Exception $e)
        {
            $this->setStatus("Error", "Парсинг провалился: что-то пошло не так.");
            return false;
        }
    }
}
?>