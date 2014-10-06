<?
/*
 * Немного о коде - функцции с постфиксом _d подходят для работы с дневным и вечерним расписанием.
 * Многие функции используют глобальные переменные. Есл ифункция инициализирует глобальную переменную - 
 * то ряддом с её объявлением стоит соответсвующий коментарий.
 * 
 */

class parserBase extends Handler implements IStatus
       {
            
    //---------------------------------------------------------------------переменные общего назначения // В общий
    protected $objPHPExcel;                                           
    protected $Sheat;//Текущий лист
    protected $Coll_Start;//начало таблицы (непосредственно данных)   
    protected $Coll_End;//за концом таблицы
    protected $Row_Start;//начало таблицы
    protected $Row_End;//за концом таблицы
    protected $Row_Start_Date;//начало данных
    protected $Group;//массив с данными.
    protected $Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
    protected $gani; //массив хранит границы дней недели
    protected $date_massiv; // сохраняет названия месяцев и соответсвующие им дни
    public $Type_stady; //форма обучения. 0 - дневная, 1 - вечерняя, 2-заочная.
    //-----------------------------------------------------------------------Перемнные заочного распсиания
    public function load($fileName)
    {
        try {
             $this->objPHPExcel = PHPExcel_IOFactory::load($fileName);
             $this->setStatus("OK", "Успешно открыли файл $fileName");
             return true;
            } catch (Exception $exc) 
            {
                $this->setStatus("ERROR", "Не удалось открыть файл $fileName");
                return false;
            }

       
    }
    protected function Order_66($Sheat)// сносит все невидемые  // В общий
    {
        $this->objPHPExcel;
        $name_max_col = $this->objPHPExcel->getSheet($Sheat)->getHighestColumn();
        $coll_max=0;//максимальный заюзанный столбец.
        do
        {
            $coll_max++;
        }
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn()!=$name_max_col);
        $coll_max++;
        $killed=0;
        $i=0;
        while($i<$coll_max-$killed)
        {
            if($this->objPHPExcel->getSheet()->getColumnDimensionByColumn($i)->getVisible()!="")
            {
                $i++;
            }
            else
            {
                $this->objPHPExcel->getSheet()->removeColumnByIndex($i);
                $killed++;
            }
        }
    }

    protected function get_typ_raspisania($Sheat) //Выясняет тип расписания  // В фабрику
    {// Здесь начинается лютый, беспросветный полярный лис. Функция перевода имени столбца в индекс не найдена, получить индекс максимального столбца тоже невозможно. Я не виноват!!!!
        $this->objPHPExcel;
        $name_max_col = $this->objPHPExcel->getSheet($Sheat)->getHighestColumn();
        // print ( $name_max_col."  ");
        $coll_max=0;//максимальный заюзанный столбец.
        // print(objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn());

        do
        {
            $coll_max++;
        }
        while($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn()!=$name_max_col);
        $coll_max++;
        $Row_Max=1;
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&  $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
            $Row_Max++;
        }
        $Row_Max++;
        $matches[0] = false;
        for($i=1;$i<$Row_Max;$i++)
        {
            for($k=0;$k<$coll_max;$k++)
            {
                preg_match("/Заочного|Вечернего|Второго|Инженерно|Автомеханического/iu", $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i), $matches);
                if($matches)
                {
                    switch($matches[0])
                    {
                        case "Заочного":{return 2; ;break;}
                        case "Вечернего":{return 1; ;break;}
                        case "Второго":{return 3; ;break;}
                        case "Инженерно":{return 0; ;break;}
                        case "Автомеханического":{return 0; ;break;}
                        default :{break;}
                    }
                }
            }
        }
    }

    protected  function read_cell($Staret_Row,$Start_Coll,$Sheet)//Читает ячейку  // В общий
    {
        $this->objPHPExcel;
        $row=$Staret_Row;
        $result = array();
        $result[0]="";//предмет
        $result[1]="";
        $result[2]="";
        $result[3]="";
        $result[4]="";// препод
        $result[5]="";//комент
        $result[6]=0;
        $result[7]=0;
        $coll=$Start_Coll;
        $shirina=0;

        while(!($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getBorders()->getRight()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll+1, $row)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"))
        {
            $coll++;
            $shirina++;
        }
        $row=$Staret_Row-1;
        do//цикл по строкам
        {
            $row++;
            $coll=$Start_Coll-1;
            do//цикл по столбцам
            {
                $coll++;
                if(trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row))!="")
                {
                    $str=trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row));
                    /**/
                    if($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getBold()==1 &&($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getColor()->getRGB()==="FFFFFF"||$this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getColor()->getRGB()==="000000"))
                    {
                        if(preg_match("/[с]( )+\d{1,2}[-:\.]\d\d/iu", $str, $matches)!=0)
                        {
                            //  print("Время:".$matches[0]."<BR>");
                            $str=  str_replace($matches[0], "", $str);
                            $result[5].=" ".$matches[0];
                        }
                        $result[0].=$str;
                        //print("Предмет:".$str."<BR>");
                        ;

                    }
                    else
                    {
                        if(preg_match("/(^| )(лаб(( )*\.)?|лек(( )*\.)?|пр(( )*\.)?|[З|з]ач[ё|е]т)|[Э|э]кзамен( |$)/ui", $str,$maches))
                        {
                            $result[1] =  $maches[0];
                            // print("Тип занятия:".$result[1]."<BR>");
                            $str=  str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                        if(preg_match("/(с|c)?( )*\d{1,2}\.\d\d-\d{1,2}\.\d\d/", $str,$maches))
                        {
                            $result[5].=" ".$maches[0];
                            $str=  str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                        if(preg_match_all("/[А-я]+( )*-+( )*\d+/", $str,$maches,PREG_PATTERN_ORDER))
                        {

                            // print("Аудитории:");
                            if(count($maches[0])>1)
                            {   
                                
                                $result[2]=$maches[0][0];
                                $str=str_replace($maches[0][0], "", $str);
                                $result[2]=str_replace(" ","",$result[2]);
                                preg_match("/-+/", $str,$mac);
                                $result[2]= str_replace($mac[0], "-", $result[2]);
                                for($i=1;$i<count($maches[0]);$i++)
                                {
                                    $result[5].=$maches[0][$i];
                                    //var_dump($maches);
                                    // print($maches[0][$i].",");
                                    $str=str_replace($maches[0][$i], "", $str);
                                }
                            }
                            else
                            {
                                $result[2]=$maches[0][0];
                                $result[2]=str_replace(" ","",$result[2]);
                                preg_match("/-+/", $str,$mac);
                                $result[2]= str_replace($mac[0], "-", $result[2]);
                                // print($maches[0][0]);
                                $str=  str_replace($maches[0][0], "", $str);
                            }
                            $str=trim($str);
                            // print("<BR>");
                        }
                        if(preg_match("/(\d{1,2}.\d{1,2}(,)?( )*){2,}/", $str,$maches))
                        {
                            // print("Даты:");
                            // print($maches[0]."<BR>");
                            $result[3]=$maches[0];
                            $str=  str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                        if(preg_match("/(([А-Я](\.)?( )*){0,2}[А-Я][а-я]+)( )*(([А-Я](\.)?( )*){0,2})/ui", $str,$maches))
                        {
                            //print("Препод:".$maches[0]."<BR>");
                            $result[4]=$maches[0];
                            $str= str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                        if(trim($str)!="")
                        {
                            // Print("Комент: ".$str."<BR>");
                            $result[5].=$str." ";

                        }
                    }
                }
                //print($row.":".$coll.":".trim(objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row))."<BR>");
            }
            while($coll<$Start_Coll+$shirina);
            // print(objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle().":".objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()."<BR>");
        }
        while(!($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none"));
        $result[6]=$row-$Staret_Row+1;
        $result[7]=$coll-$Start_Coll+1;
        // print();
        return $result;
    }

    protected function Mesac_to_chislo( $str)// определяет, что за месяц передан в строке и возвращает его номер   // В общий
    {   //Да, это костыль.
        str_replace("a", "а", $str);
        str_replace("A", "А", $str);
        str_replace("c", "с", $str);
        str_replace("C", "с", $str);
        str_replace("e", "е", $str);
        str_replace("E", "Е", $str);
        str_replace("o", "о", $str);
        str_replace("O", "О", $str);

        $messs="00";
        switch (trim($str))
        {
            case "Январь": {$messs=1; break;}
            case "январь": {$messs=1; break;}
            case "ЯНВАРЬ": {$messs=1; break;}
            case "Февраль": {$messs=2; break;}
            case "февраль": {$messs=2; break;}
            case "ФЕВРАЛЬ": {$messs=2; break;}
            case "Март": {$messs=3; break;}
            case "март": {$messs=3; break;}
            case "МАРТ": {$messs=3; break;}
            case "Апрель": {$messs=4; break;}
            case "апрель": {$messs=4; break;}
            case "АПРЕЛЬ": {$messs=4; break;}
            case "Май": {$messs=5; break;}
            case "май": {$messs=5; break;}
            case "МАЙ": {$messs=5; break;}
            case "Июнь": {$messs=6; break;}
            case "июнь": {$messs=6; break;}
            case "ИЮНЬ": {$messs=6; break;}
            case "Июль": {$messs=7; break;}
            case "июль": {$messs=7; break;}
            case "ИЮЛЬ": {$messs=7; break;}
            case "Август": {$messs=8; break;}
            case "август": {$messs=8; break;}
            case "АВГУСТ": {$messs=8; break;}
            case "Сентябрь": { $messs=9;break;}
            case "сентябрь": {$messs=9; break;}
            case "СЕНТЯБРЬ": {$messs=9; break;}
            case "Октябрь": {$messs=10; break;}
            case "октябрь": {$messs=10; break;}
            case "ОКТЯБРЬ": {$messs=10; break;}
            case "Ноябрь": {$messs=11; break;}
            case "ноябрь": {$messs=11; break;}
            case "НОЯБРЬ": {$messs=11; break;}
            case "Декабрь": {$messs=12; break;}
            case "декабрь": {$messs=12; break;}
            case "ДЕКАБРЬ": {$messs=12; break;}
        }

        return $messs;
    }

    protected function get_par_number($rows,$Coll_Start,$Sheat, &$NewPar)//получить номер пары        // В общий.
    {
        $this->objPHPExcel;
        $k=0;
        do
        {
            $str=$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start-1, $rows+$k);
            $str=trim($str);
            str_replace(" ", "", $str);
            $k++;
        }
        while($str=="");
        $matches[0]=false;
        preg_match("/-+/iu", $str, $matches);
        if(count($matches)>0)
        {
            $str = str_replace($matches[0], "-", $str);
        }
        switch ($str)
        {
            case "1-2": {$NewPar->ParNumber=1;break;}
            case "3-4": {$NewPar->ParNumber=2;break;}
            case "5-6": {$NewPar->ParNumber=3;break;}
            case "7-8": {$NewPar->ParNumber=4;break;}
            case "9-10": {$NewPar->ParNumber=5;break;}
            case "11-12": {$NewPar->ParNumber=6;break;}
            case "13-14": {$NewPar->ParNumber=7;break;}
            case "15-16": {$NewPar->ParNumber=8;break;}
            case "8-00": {$NewPar->ParNumber=1;$NewPar->Comment.=$str;break;}
            case "9-40": {$NewPar->ParNumber=2;$NewPar->Comment.=$str;break;}
            case "11-20": {$NewPar->ParNumber=3;$NewPar->Comment.=$str;break;}
            case "13-00": {$NewPar->ParNumber=4;$NewPar->Comment.=$str;break;}
            case "14-40": {$NewPar->ParNumber=5;$NewPar->Comment.=$str;break;}
            case "16-20": {$NewPar->ParNumber=6;$NewPar->Comment.=$str;break;}
            case "18-00": {$NewPar->ParNumber=7;$NewPar->Comment.=$str;break;}
            case "19-30": {$NewPar->ParNumber=8;$NewPar->Comment.=$str;break;}
            default : {$NewPar->ParNumber=-1;break;}

        }
        //print("<BR>Было: ".$str.". Номер пары:".$rez."<BR>");
    }

    protected function group_init($Coll_Start,$Coll_End,$Row_Start,$Sheat,$Shirina_na_gruppu)//распознавание групп. Объявляет глобальные переменные  // В общий
    {
        $this->Group;//инициализирует
        $this->objPHPExcel;
        $gr_cl=0;
        for($i=$Coll_Start;$i<$Coll_End;$i+=$Shirina_na_gruppu)
        {
            $this->Group[$gr_cl]["NameGroup"]=trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));
            $this->Group[$gr_cl]["NameGroup"]= str_replace(" ", "",$this->Group[$gr_cl]["NameGroup"]);
            preg_match("/-+/ui", $this->Group[$gr_cl]["NameGroup"], $matches);
            if(count($matches)>0)
            {
              $this->Group[$gr_cl]["NameGroup"]=  str_replace($matches[0], "-", $this->Group[$gr_cl]["NameGroup"]); 
            }            
            $this->Group[$gr_cl]["Para"]= array();
            $gr_cl++;
        }
    }

    protected  function dey_gran($Row_Start_Date,$Row_End,$Sheat)//устанавливает грани между днями недели.   // В общий
    {
        $this->objPHPExcel;
        $this->gani;//инициализирует
        $k=0;
        for($i=$Row_Start_Date;$i<$Row_End;$i++)
        {
            if($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle()!=="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i+1)->getStyle()->getBorders()->getTop()->getBorderStyle()!=="none")
            {
                if($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB()==="FFFFFF"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB()==="000000")
                {
                    $this->gani[$k]=$i+1;
                    $k++;
                }
            }
        }
    }

    protected  function get_mounday($Coll_Start,$Row_Start,$Sheat,$Row_Start_Date)// заполняет массив с датами. // В общий
    {
        $this->gani;
        $this->objPHPExcel;
        $this->date_massiv; //инициализирует
        for($k=1;$k<$Coll_Start-1;$k++)
        {
            $this->date_massiv[$k-1]["month"]=trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $Row_Start));
            $this->date_massiv[$k-1]["date"]=array();
            for($p=0;$p<count($this->gani);$p++)
            {
                $this->date_massiv[$k-1]["date"][$p]="";
                //date_massiv[$k]["gran"]=gani[$p];
                if($p==0)
                {
                    $i=$Row_Start_Date;
                }
                else
                {
                    $i=$this->gani[$p-1];
                }
                for($i;$i<$this->gani[$p];$i++)
                {
                    if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))!="")
                    {
                        $this->date_massiv[$k-1]["date"][$p].=trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))."|";
                    }
                }
            }
        }
    }

    protected function exchangePrev($nau,&$NewPar) // Копирует недостающие данные в альтернативную пару.
    {
        //print($NewPar->Predmet." ".$NewPar->Type." ".$NewPar->Auditoria." ".$NewPar->Prepod."<BR>");
       for($i=count($this->Group[$nau]["Para"])-1;$this->Group[$nau]["Para"][$i]->Predmet==$NewPar->Predmet&&$this->Group[$nau]["Para"][$i]->Date==$NewPar->Date&&$i>-1;$i--)
       {
           
                                if($NewPar->Auditoria=="")
                                {
                                    $NewPar->Auditoria= $this->Group[$nau]["Para"][$i]->Auditoria;
                                }
                                if($NewPar->Prepod=="")
                                {
                                    $NewPar->Prepod= $this->Group[$nau]["Para"][$i]->Prepod;
                                }
                                if($NewPar->Comment=="")
                                {//print("Много мыши!!".$this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Predmet."!");
                                    $NewPar->Comment= $this->Group[$nau]["Para"][$i]->Comment;
                                }
                                if($NewPar->Type=="")
                                {
                                    $NewPar->Type= $this->Group[$nau]["Para"][$i]->Type;
                                }
                                //_____________________________________________
                                if($this->Group[$nau]["Para"][$i]->Auditoria=="")
                                {
                                    $this->Group[$nau]["Para"][$i]->Auditoria= $NewPar->Auditoria;
                                }
                                if($this->Group[$nau]["Para"][$i]->Prepod=="")
                                {
                                    $this->Group[$nau]["Para"][$i]->Prepod= $NewPar->Prepod;
                                }
                                if($this->Group[$nau]["Para"][$i]->Comment=="")
                                {//print("Много мыши!!".$this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Predmet."!");
                                    $this->Group[$nau]["Para"][$i]->Comment= $NewPar->Comment;
                                }
                                if($this->Group[$nau]["Para"][$i]->Type=="")
                                {
                                    $this->Group[$nau]["Para"][$i]->Type;
                                }
                                //_______________________________________
       }
                                 
    }
    //----------------------------------------------------------------------//Функции для заочного распсиания
  
  ///////////////////////////////////////////
   
    public function getParseData()      // В общее
    { 
        try {
        $par_date=array();
        $this->Group;
        //print("<br><br><br>");
        for($i=0;$i<count($this->Group);$i++)
        {
            $par_date = array_merge($par_date,$this->Group[$i]["Para"]);
        }
        $this->setStatus("OK", "Парсинг прошёл успешно.");
        return $par_date;
        }
        catch(Exception $e)
        {
            // print($e);
            $this->setStatus("Error", "Парсинг провалился: что-то пошло не так.");
            return false;
        }
    }

    /*public function getStatusCode() // В общий
            {
            	return json_encode($this->status['Code']);
            }
	
    public function getStatusDescription() // В общий
            {
		return json_encode($this->status['Description']);
            }
	
    public function getStatusDetails() // В общий
            {
		return json_encode($this->status['Details']);
            }
       */      
            
             //var_dump($this->Group);
            /** /
            if($this->Type_stady==-1)// этод код никогда не отработает. Не паниковать!
            {//будущая функция
            $Coll_Start=1;//начало таблицы (непосредственно данных)
            $Coll_End=1;//за концом таблицы
            $Row_Start=0;//начало таблицы
            $Row_End=0;//за концом таблицы
            $Row_Start_Date=0;//начало данных
            $Group=array();//массив с данными.
            $Shirina_na_gruppu=1;//Число ячеек, отведённых на одну группу.
            $gani=false; //массив хранит границы дней недели
            $date_massiv=false;
            $Section_Start=15;
            $Sheat=0;

            $this->Order_66($Sheat);
            $this->get_orientirs_z($Sheat);
            $this->get_section_end($Sheat,$Section_Start);
            $this->group_init_d($Section_date_start,$Section_end,$Row_Start,$Sheat,$Shirina_na_gruppu);
            $this->dey_gran_d($Row_Start_Date,$Row_End,$Sheat);
            $this->get_mounday_z($Row_Start_Date,$Section_Start,$Row_End,$Sheat);

            var_dump($date_massiv);
            print ("<BR>".$Row_Start."|".$Row_Start_Date."|".$Row_End."<Br>".$Coll_Start."|".$Coll_End."|".$Section_Start."|".$Section_end);
            }/**/
}
?>