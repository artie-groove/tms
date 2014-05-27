<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>
</head>
<body>
<?php
       /* 
        class Pair  //Pair
        {
            public $Predmet;
            public $Prepod;
            public $Type;
            public $Auditoria;
            public $ParNumber;
            public $Date;
            public $Comment;
            public $Group;
            
            public function copy($old)
            {
                $this->Predmet = $old->Predmet;
                $this->Prepod = $old->Prepod;
                $this->Type= $old->Type;
                $this->Auditoria= $old->Auditoria;
                $this->ParNumber= $old->ParNumber;
                $this->Date= $old->Date;
                $this->Comment= $old->Comment;
                $this->Group= $old->Group;
            }
        }
        
        class Parser
        {
             //---------------------------------------------------------------------переменные общего назначения
        private $objPHPExcel;
        private $Sheat;//Текущий лист
        private $Coll_Start;//начало таблицы (непосредственно данных)
        private $Coll_End;//за концом таблицы
        private $Row_Start;//начало таблицы
        private $Row_End;//за концом таблицы
        private $Row_Start_Date;//начало данных
        private $Group;//массив с данными.
        private $Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
        private $gani; //массив хранит границы дней недели
        private $date_massiv; // сохраняет названия месяцев и соответсвующие им дни
        private $Type_stady; //форма обучения. 0 - дневная, 1 - вечерняя, 2-заочная.
      //-----------------------------------------------------------------------Перемнные заочного распсиания
        private $Section_Start;// ширина текущей секции
        private $Section_end;// конец текущей секции
        private $Section_date_start;//начало данных для текущей секции
        
    private function Order_66($Sheat)// сносит все невидемые 
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
       
    private function get_typ_raspisania($Sheat)
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
      
    private   function read_cell($Staret_Row,$Start_Coll,$Sheet)//Читает ячейку
      {
          $this->objPHPExcel;
          $row=$Staret_Row;
          $result = array();
          $result[0]="";
          $result[1]="";
          $result[2]="";
          $result[3]="";
          $result[4]="";
          $result[5]="";
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
               /** /
                if($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getBold()==1)
                {                     
                   if(preg_match("/[с]( )+\d{1,2}[-:\.]\d\d/iu", $str, $matches)!=0)
                    { 
                  //  print("Время:".$matches[0]."<BR>");
                    $str=  str_replace($matches[0], "", $str);
                    $result[5].=" ".$matches[0];
                    }
                    $result[0].=$str;
                    //print("Предмет:".$str."<BR>");
                    
                }
                else
                { 
                    if(preg_match("/лаб(( )*\.)?|лек(( )*\.)?|пр(( )*\.)?/", $str,$maches))
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
                          $result[2]=array();
                          for($i=0;$i<count($maches[0]);$i++)
                          {
                            $result[2][$i]=$maches[0][$i];
                            //var_dump($maches);
                           // print($maches[0][$i].",");
                            $str=  str_replace($maches[0][$i], "", $str);
                          }
                         }
                          else
                              {
                              $result[2]=$maches[0];
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
                      if(preg_match("/(([а-я](\.)?( )*){0,2}[а-я][а-я]+)( )*(([а-я](\.)?( )*){0,2})/ui", $str,$maches))
                        {
                            //print("Препод:".$maches[0]."<BR>");
                             $result[4]=$maches[0]; 
                             $str=  str_replace($maches[0], "", $str);
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

    private function Mesac_to_chislo( $str)// определяет, что за месяц передан в строке и возвращает его номер
      {  
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
        
    private function get_par_number($rows,$Coll_Start,$Sheat, &$NewPar)//получить номер пары
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
         if($matches[0]!=false)
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
        
    private    function get_orientirs_d($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные. 
      {
         $this->objPHPExcel;
         $this->Coll_Start;//начало таблицы (непосредственно данных)//инициализирует
         $this->Coll_End;//за концом таблицы//инициализирует
         $this->Row_Start;//начало таблицы//инициализирует
         $this->Row_End;//за концом таблицы//инициализирует
         $this->Row_Start_Date;//начало данных//инициализирует
         $this->Shirina_na_gruppu;//инициализирует
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
          $this->Row_Start++; 
        }
        $this->Row_Start++;
        //Print $Row_Start;
        $this->Row_Start_Date =  $this->Row_Start+1;
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
          $this->Row_Start_Date++;
        }
        $this->Row_End=$this->Row_Start_Date;
         While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
         {
                 $this->Row_End++;
         }
         while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_Start, $this->Row_Start))))
         {
             $this->Coll_Start++;
         }
        $count_z=0;
        $coll=$this->Coll_Start;
        while($count_z<1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $this->Shirina_na_gruppu++;
            if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $this->Row_Start))!="")
            {
                $count_z++;
            }
        }
        //print($Shirina_na_gruppu);
          $this->Coll_End=$this->Coll_Start;
          While(($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle()!=="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End-1, $this->Row_Start)->getStyle()->getBorders()->getRight()->getBorderStyle()!=="none"))
          {
             $this->Coll_End+=$this->Shirina_na_gruppu; 
          }
         $this->Coll_End-=$this->Shirina_na_gruppu; 
      }
      
    private function group_init_d($Coll_Start,$Coll_End,$Row_Start,$Sheat,$Shirina_na_gruppu)//распознавание групп. Объявляет глобальные переменные
      {
          $this->Group;//инициализирует
          $this->objPHPExcel;
          $gr_cl=0;
          for($i=$Coll_Start;$i<$Coll_End;$i+=$Shirina_na_gruppu)
          {
           $this->Group[$gr_cl]["NameGroup"]=trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));  
           $this->Group[$gr_cl]["Para"]= array();
           $gr_cl++;
          } 
      }
      
    private  function dey_gran_d($Row_Start_Date,$Row_End,$Sheat)//устанавливает грани между днями недели.
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
      
    private  function get_mounday_d($Coll_Start,$Row_Start,$Sheat,$Row_Start_Date)// заполняет массив с датами.
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
      
      
    private  function get_day_raspisanie ()// анализирует дневное распсиание.
      {
            $this->objPHPExcel;
            $this->Coll_Start;//начало таблицы (непосредственно данных)
            $this->Coll_End;//за концом таблицы
            $this->Row_Start;//начало таблицы
            $this->Row_End;//за концом таблицы
            $this->Row_Start_Date;//начало данных
            $this->Group;//массив с данными.
            $this->Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
            $this->gani; //массив хранит границы дней недели
            $this->date_massiv;
            $this->Type_stady;
          for($Sheat=0;$Sheat<$this->objPHPExcel->getSheetCount();$Sheat++)
        { 
            $this->Coll_Start=1;//начало таблицы (непосредственно данных)
            $this->Coll_End=1;//за концом таблицы
            $this->Row_Start=0;//начало таблицы
            $this->Row_End=0;//за концом таблицы
            $this->Row_Start_Date=0;//начало данных
            $this->Group=array();//массив с данными.
            $this->Shirina_na_gruppu=1;//Число ячеек, отведённых на одну группу.
            $this->gani=false; //массив хранит границы дней недели
            $this->date_massiv=false;
         $this->Order_66($Sheat);   
         $this->get_orientirs_d($Sheat);
         $this->group_init_d($this->Coll_Start,$this->Coll_End,$this->Row_Start,$Sheat,$this->Shirina_na_gruppu);
         $this->dey_gran_d($this->Row_Start_Date,$this->Row_End,$Sheat);
         $this->get_mounday_d($this->Coll_Start,$this->Row_Start,$Sheat,$this->Row_Start_Date);          
          for($i=$this->Row_Start_Date;$i<$this->Row_End;$i++)
        {
            for($k=$this->Coll_Start;$k<$this->Coll_End;$k++)
            {
                if(($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k-1, $i)->getStyle()->getBorders()->getRight()->getBorderStyle()!="none")&&($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i-1)->getStyle()->getBorders()->getBottom()->getBorderStyle()!="none"))
                {  
                   
                   $res= $this->read_cell($i,$k,$Sheat);
                   $nau= floor(($k-$this->Coll_Start)/$this->Shirina_na_gruppu);
                   if($res[0]!="")//Если есть название предмета
                   {                       //var_dump($this->Group[$nau]);
                       $nau_par_count =count($this->Group[$nau]["Para"]); 
                       if($nau_par_count>0)//Проверяем, если у нас пара не первая
                       {   //["Para"]
                           
                           $Prev_par=$nau_par_count-1;
                           if($this->Group[$nau]["Para"][$Prev_par]->Predmet==false)//если у предыдущей пары нет предмета
                           {
                               $NewPar=array_pop($this->Group[$nau]["Para"]);//Вытягиваем предыдущую пару на заполнение.
                           }
                            else
                           {
                               $NewPar=new Pair();  // Иначе создаём новую пару.
                           }
                       }
                       else// если у нас первая пара
                       {
                           $Prev_par=$nau_par_count;
                           $NewPar=new Pair();
                       }
                               
                                 if($res[3]=="")//если у нас нет дат в ячейке
                                     {  
                                    for($d=0;$d<count($this->date_massiv);$d++)
                                         {
                                           $moun=$this->Mesac_to_chislo ($this->date_massiv[$d]["month"]);
                                             $f=0;
                                             while($i>$this->gani[$f])
                                             {
                                                 $f++;
                                             }
                                             $dart=$this->date_massiv[$d]["date"][$f];
                                            $dart= explode("|",$dart);
                                            for($l=0;$l<count($dart)-1;$l++)
                                             {
                                               $NewPar->Date.=$dart[$l].".".$moun.",";
                                             }
                                            }
                                     }
                                     else// если даты в ячейке есть
                                    {
                                      $NewPar->Date=$res[3];
                                    }
                           $NewPar->Predmet=$res[0];
                           $NewPar->Type=$res[1];
                           $NewPar->Auditoria=$res[2];
                           $NewPar->Prepod=$res[4];
                           $NewPar->Comment.=trim($res[5]);
                           $group_count= floor($res[7]/$this->Shirina_na_gruppu);
                           $this->get_par_number($i,$this->Coll_Start,$Sheat,&$NewPar);
                           
                           if($group_count==0&& !is_int(($k-$this->Coll_Start+$this->Shirina_na_gruppu)/$this->Shirina_na_gruppu))
                           { 
                               //print($NewPar->Predmet." ".$NewPar->Type." ".$NewPar->Auditoria." ".$NewPar->Prepod."<BR>");
                             if($NewPar->Auditoria=="")
                             {
                              $NewPar->Auditoria= $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Auditoria;  
                             }
                             if($NewPar->Prepod=="")
                             {
                               $NewPar->Prepod= $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Prepod;     
                             }
                             if($NewPar->Comment=="")
                             {//print("Много мыши!!".$this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Predmet."!");
                              $NewPar->Comment= $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Comment;    
                             }
                             if($NewPar->Type=="")
                             {
                                $NewPar->Type= $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Type;  
                             }
                             //_____________________________________________
                             if($this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Auditoria=="")
                             {
                             $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Auditoria= $NewPar->Auditoria;  
                             }
                             if($this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Prepod=="")
                             {
                              $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Prepod= $NewPar->Prepod;     
                             }
                             if($this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Comment=="")
                             {//print("Много мыши!!".$this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Predmet."!");
                             $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Comment= $NewPar->Comment;    
                             }
                             if($this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Type=="")
                             {
                               $this->Group[$nau]["Para"][count($this->Group[$nau]["Para"])-1]->Type;  
                             }
                             //_______________________________________
                             $par_count=floor($res[6]/2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.
                             for($d=0;$d<$par_count;$d++)
                               {    
                                       $par_temp= new Pair();
                                       $par_temp->copy($NewPar);
                                       $par_temp->ParNumber+=$d;
                                       $par_temp->Group=$this->Group[$nau]["NameGroup"];
                                       array_push( $this->Group[$nau]["Para"],$par_temp);
                                   
                               }
                           }
                           else
                           {
                                $par_count=floor($res[6]/2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.
                               
                                if($group_count==0)
                                {
                                    $group_count=1;
                                }
                                for($l=0;$l<$group_count;$l++)
                               {//$par_temp= new Pair();
                               if(count($this->Group[$nau+$l]["Para"])>0)
                                {
                                   if($this->Group[$nau+$l]["Para"][count($this->Group[$nau+$l]["Para"])-1]->Predmet=="")
                                   {
                                     array_pop($this->Group[$nau+$l]["Para"]);  
                                   }
                                       
                                }
                                for($z=0;$z<$par_count;$z++)
                                    {
                                       $par_temp= new Pair();
                                       $par_temp->copy($NewPar);
                                       $par_temp->ParNumber+=$z;
                                       $par_temp->Group=$this->Group[$nau+$l]["NameGroup"];
                                       array_push( $this->Group[$nau+$l]["Para"],$par_temp);
                                    }  
                               }
                           }
                     }
                else // названия предммета нет.
                    {
                     if(trim($res[5])!="")
                        {
                         $NewPar= new Pair();
                         $NewPar->Predmet=false;
                         $NewPar->Comment=$res[5];  
                         $NewPar->Group=$this->Group[$nau]["NameGroup"];
                         array_push( $this->Group[$nau]["Para"],$NewPar); 
                        } 
                    }
                }
             
            }
        }
     // writ_to_bd_d();  
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
        
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
          $this->Row_Start++; 
        }
        $this->Row_Start++;
        //Print $this->Row_Start;
        $this->Row_Start_Date =  $this->Row_Start+1;
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
          $this->Row_Start_Date++;
        }
        $this->Row_End=$this->Row_Start_Date;
         While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
         {
          $this->Row_End++;
         }
         while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_Start, $this->Row_Start))))
         {
             $this->Coll_Start++;
         }
        $count_z=0;
        $coll=$this->Coll_Start;
        while($count_z<1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $this->Shirina_na_gruppu++;
            if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $this->Row_Start))!="")
            {
                $count_z++;
            }
        }
        //print($this->Shirina_na_gruppu);
          $this->Coll_End=$this->Coll_Start;
          While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
          {
             $this->Coll_End++; 
          }
       }
         
    private   function get_section_end($Sheat,$old_end)// находит границу секции. Принимает последнюю обнаруженную границу
       {
           $this->objPHPExcel;
           $this->Row_Start;
           $this->Coll_End;
           $this->Section_Start;//Утсанавливает значение
           $this->Section_end;//устанавливает значение
           $this->Section_date_start;//устанавливается значение
           $this->Section_Start=$old_end;
           $this->Section_end=$old_end+1;
            while(preg_match("/дни/iu", $this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_end, $this->Row_Start))==0&&($this->Section_end<$this->Coll_End))
          {   
              $this->Section_end++;
          }
          $this->Section_date_start=$this->Section_Start;
          while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Section_date_start, $this->Row_Start))))
         {
             $this->Section_date_start++;
         }
       }
    private   function get_mounday_z($Row_Start_Date,$Section_Start,$Row_End,$Sheet)
       {
           $this->date_massiv;//инициализируется, предварительно обнуляется.
           $this->gani;
           $this->objPHPExcel;
           $this->date_massiv=false;
           $this->date_massiv[0]["month"]=false;
           for($i=$Row_Start_Date;$i<$Row_End;$i++)
           {
               if(trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i))!="")
               {
                   $k=-1;
                   for($k=0;$k<count($this->gani);$k++)
                   {
                       if($this->gani[$k]>$i)
                       {
                           break;
                       }
                   }
                   //print($k."<BR>");
                  if(isset($this->date_massiv[0]["month"][$k]))
                  {
                      $this->date_massiv[0]["month"][$k].=trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                  }
                  else
                  {
                       $this->date_massiv[0]["date"][$k]=trim($this->objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($Section_Start+1, $i));
                       //print($i."<BR>");
                  }
               }  
           } 
         // var_dump($this->date_massiv); 
       }     
       /////////////////////////////////////////// 
    public function parsing($file_name) 
        {
        try {
            $this->objPHPExcel = PHPExcel_IOFactory::load($file_name);
            $this->Type_stady;// Тип распознаваемого расписания
      // Type_stady =$this->get_typ_raspisania(0);
       $this->Type_stady=0;
      switch ($this->Type_stady)
      {
      case 0:{$this->get_day_raspisanie();break;}//расписание дневное - фас!
      case 1:{$this->get_day_raspisanie();break;}//распсиание вечернее - фас!
      default :{return false;}
      }
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
      }/** /
      return true;
        }
        catch(Exception $e)
                {
               // print($e);
                return false;
                }
     
        }
       public function getParseData()
      {    $par_date=array();
           $this->Group;
          
           //print("<br><br><br>");
           for($i=0;$i<count($this->Group);$i++)
           {
               $par_date = array_merge($par_date,$this->Group[$i]["Para"]);
           }
           
           return $par_date;
      }  
        
}
       
/*
 *          public $Predmet;
            public $Prepod;
            public $Type;
            public $Auditoria;
            public $ParNumber;
            public $Date;
            public $Comment;
            public $Group;
 */ /*      
class BD_Pusher
       {
           public function push($par_mass)//Запись в базу данных массива GROUP
              {
                $link = mysql_connect('localhost', 'root', '') or die('Не удалось соединиться: ' .mysql_error());
                mysql_select_db('raspisanie') or die('Не удалось выбрать базу данных');
                for($i=0;$i<count($par_mass);$i++)
                {
                  $group_id=0;  
                  if(trim($par_mass[$i]->Group)!="")
                  {
                     $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
                     $res_SQL = mysql_query($query)or die('Провал запроса на группу');
                     $row = mysql_fetch_assoc($res_SQL);
                     if($row)
                    {
                      $group_id= $row['id']; 
                    }
                  }
                  
                  $prepod_id=0;
                  $par_mass[$i]->Prepod=trim($par_mass[$i]->Prepod);
                  if($par_mass[$i]->Prepod!="")
                  {
                       $inicial=array();  
                       if(preg_match_all("/[А-Я]\./ui", $par_mass[$i]->Prepod, $matches,PREG_PATTERN_ORDER)>0)
                       {
                          for($l=0;$l<count($matches[0]);$l++)
                            {
                               $par_mass[$i]->Prepod=trim(str_replace($matches[0][$l],"", $par_mass[$i]->Prepod));
                               $inicial[$l]=trim(rtrim($matches[0][$l],'.'));
                            }
                            $query = "SELECT id,surname,name,patronymic FROM lecturers Where surname='".$par_mass[$i]->Prepod."' AND name LIKE '".$inicial[0]."%' AND patronymic LIKE '".$inicial[1]."%'";
                           //echo $query;
                           $res_SQL = mysql_query($query)or die('Поиск преподавателя не получился: ' . mysql_error());;
                            if(mysql_affected_rows()==1)
                            {
                                 $row = mysql_fetch_assoc($res_SQL);
                                 $prepod_id=$row['id'];
                            }
                       }
                       else
                       {
                           $query = "SELECT id,surname,name,patronymic FROM lecturers Where surname='".$par_mass[$i]->Prepod."'";
                           $res_SQL = mysql_query($query)or die('Поиск преподавателя не получился: ' . mysql_error());
                           if(mysql_affected_rows()==1)
                            {
                                 $row = mysql_fetch_assoc($res_SQL);
                                 $prepod_id=$row['id'];
                            }
                       }
                  }
                  
                  
                }
               
               
               /*
                            
                            }/** /
                          
                          }
                         
                         
                        }
                        
                        
                       
                       // print("<br>");
                      /** / 
                       
                       $res_SQL = mysql_query($query);
                       $temp=mysql_fetch_array($res_SQL);
                       
                       if($temp)
                       {                   
                        $prepod_id= $temp['ID_Lecturer'];  
                       }
                     /** /   else
                        {
                            $query="INSERT INTO lecturer (Family,Department_ID) VALUES ('".$Group[$i]["Para"][$k]->Prepod."',1)"; 
                            mysql_query($query) or die('Не удалось добавить преподавателя ' .mysql_error());
                            $query = "SELECT * FROM lecturer Where Family='".$Group[$i]["Para"][$k]->Prepod."'";
                            $res_SQL = mysql_query($query);
                            $temp=mysql_fetch_array($res_SQL);
                       if($temp)
                       { 
                           $prepod_id=$temp['ID_Lecturer'];
                       }
                       else
                       {
                          die('Не удалось найти добавленную преподавателя');
                       }   
                    }/** /
                       //проверяем, есть ли такой кабинет
                        if($Group[$i]["Para"][$k]->Auditoria=="")
                        {
                           $Group[$i]["Para"][$k]->Auditoria[0]=-1; 
                           //print("!!!НЕТ АУДИТОРИИ!!!");
                        }
                        else
                        {
                            for($ts=1;$ts<count($Group[$i]["Para"][$k]->Auditoria);$ts++)
                            {
                               $Group[$i]["Para"][$k]->Comment.=" ".$Group[$i]["Para"][$k]->Auditoria[$ts]; 
                            }

                        }
                         $query = "SELECT * FROM classroom Where Number_classroom='".$Group[$i]["Para"][$k]->Auditoria[0]."'";
                         $res_SQL = mysql_query($query);
                         $temp=mysql_fetch_array($res_SQL);
                         $Auditoria_id=false;
                        if($temp)
                        {                   
                        $Auditoria_id= $temp['ID_Classroom'];  
                        }
                        else
                        {
                            $query="INSERT INTO classroom (Number_classroom,Building) VALUES ('".$Group[$i]["Para"][$k]->Auditoria[0]."',0)"; 
                            mysql_query($query) or die('Не удалось добавить аудиторию ' .mysql_error());
                            $query = "SELECT * FROM classroom Where Number_classroom='".$Group[$i]["Para"][$k]->Auditoria[0]."'";
                            $res_SQL = mysql_query($query);
                            $temp=mysql_fetch_array($res_SQL);
                       if($temp)
                       { 
                           $Auditoria_id=$temp['ID_Classroom'];
                       }
                       else
                       {
                          die('Не удалось найти добавленную аудиторию');
                       }   
                    }


                    //!!!!!!!!

                       //проверяем, есть ли такой предмет
                       $query = "SELECT * FROM subject Where Subject_Name='".$Group[$i]["Para"][$k]->Predmet."'";
                       $res_SQL = mysql_query($query);
                       $temp=mysql_fetch_array($res_SQL);
                       $Subject_id=false;
                       if($temp)
                       {                   
                        $Subject_id= $temp['ID_Subject'];  
                       }
                        else
                        {
                            $query="INSERT INTO subject (Subject_Name) VALUES ('".$Group[$i]["Para"][$k]->Predmet."')"; 
                            mysql_query($query) or die('Не удалось добавить предмет ' .mysql_error());
                            $query = "SELECT * FROM subject Where Subject_Name='".$Group[$i]["Para"][$k]->Predmet."'";
                            $res_SQL = mysql_query($query);
                            $temp=mysql_fetch_array($res_SQL);
                       if($temp)
                       { 
                           $Subject_id= $temp['ID_Subject']; 
                       }
                       else
                       {
                          die('Не удалось найти добавленный предмет');
                       }   
                    }
                    //!!!!!!!!


                    $type=0;
                    //Тип занятия : 1 - лаба, 2 - лекция, 4 - практика.
                    if(preg_match("/лаб(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
                    {
                     $type=1;   
                    }elseif(preg_match("/лек(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
                    {
                      $type=2;   
                    }elseif(preg_match("/пр(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
                    {
                      $type=3;  
                    }
                    //заносим в расписание.
                     $date_m = explode(",",$Group[$i]["Para"][$k]->Date);
                     $nau_ear = date("Y");
                     $correct=0;
                     if(trim($date_m[count($date_m)-1])=="")
                     {
                         $correct=1;
                     }
                     for($in=0;$in<count($date_m)-$correct;$in++)
                     {
                       $d_a_m = explode(".",$date_m[$in]);
                      $query="INSERT INTO timetable 
(ID_Grup,ID_Lecturer,ID_classroom,Time,Date,Type,ID_Subject,Comment) VALUES (".$group_id.",".$prepod_id.",".$Auditoria_id.",".$Group[$i]["Para"][$k]->ParNumber.",'".$nau_ear."-".$d_a_m[1]."-".$d_a_m[0]."',".$type.",". $Subject_id.",'".$Group[$i]["Para"][$k]->Comment."')"; 
                      mysql_query($query) or die('Не удалось добавить пару ' . mysql_error());
                     }
                     print("<BR>");
                   /** /
                       
                   }
                   
                }
               * /** /
              }
        
       }
       
       
      */ 
       
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
        include $_SERVER['DOCUMENT_ROOT'].'/lib/Classes/PHPExcel.php';
        include $_SERVER['DOCUMENT_ROOT']."/app/helpers/Pair.php";
        include $_SERVER['DOCUMENT_ROOT']."/app/helpers/Parser.php";
        include $_SERVER['DOCUMENT_ROOT']."/app/helpers/Parser.php";
        $test = new Parser();
        $test2= new BD_Pusher();
        if($test->parsing("fei5.xlsx"))
        {
            //var_dump($test->getParseData()); 
            $push= new BD_Pusher();
            $push->push($test->getParseData());
        }
        
        
        
       //getParseData;
        //$test2->push();
        //$s= new Pair();
        
       // error_reporting(E_ALL);
       // ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
      //  require_once dirname(__FILE__) . '/lib/Classes/PHPExcel.php';
      //  $test = new Parser();
      //  var_dump($test->parsing("fei5.xlsx"));
        
       // objPHPExcel = PHPExcel_IOFactory::load("fei5.xlsx");
       //fei4_140213
       //vf5_140213.xlsx
       //objPHPExcel = PHPExcel_IOFactory::load("vf5_140213.xlsx");//postal_3course_140506.xlsx
       //objPHPExcel = PHPExcel_IOFactory::load("postal_3course_140506.xlsx");
      //-----------------------------------------------------------------------  
      // Функциональная зона. Возвращает: 0)Предмет. 1) Тип занятия. 2)Аудитории (массив) 3) даты 4) преподаватель 5) комментарий. 6)Число строк 7) число столбцов
      
      
      
      //----------------------------------------------------------------------- функции общего назначения
       
      
        
      //--------------------------------------------------------------------- функции для дневного и вечернего отделения.
      
        
      
       //----------------------------------------------------------------------
       


       
     //echo $objPHPExcel->getSheet(0)->getColumnDimensionByColumn(0)->getWidth();
     //$objPHPExcel->getSheet()->removeRow($Section_width)
      /*
     echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ";
      echo $objPHPExcel->getSheet()->getCellByColumnAndRow(15, 11);
     $objPHPExcel->getSheet()->removeColumnByIndex(15);     
     echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ";
     echo $objPHPExcel->getSheet()->getCellByColumnAndRow(15, 11);
       */
      //echo $this->get_typ_raspisania(0);
       //echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ".$objPHPExcel->getSheet(0)->getColumnDimensionByColumn(15)->getVisible();
      // $objPHPExcel->getSheet(0)->getColumnDimensionByColumn(15)->getVisible();
              
        
        ?>
</body>
</html>
