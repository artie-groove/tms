
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        /**//*
         * Немного о коде - функцции с постфиксом _d подходят для работы с дневным и вечерним расписанием.
         * Многие функции используют глобальные переменные. Есл ифункция инициализирует глобальную переменную - 
         * то ряддом с её объявлением стоит соответсвующий коментарий.
         * 
         */
        class cell
        {
            public $date;
            public $BorderTop;
            public $BorderRight;
            public $BorderBottom;
            public $BorderLeft;
            public $BackgroundColor;            
        }
        
        class para
        {
            public $Predmet;
            public $Prepod;
            public $Type;
            public $Auditoria;
            public $ParNumber;
            public $Date;
            public $Comment;
         
            public function copy($old)
            {
                
                $this->Predmet = $old->Predmet;
                $this->Prepod = $old->Prepod;
                $this->Type= $old->Type;
                $this->Auditoria= $old->Auditoria;
                $this->ParNumber= $old->ParNumber;
                $this->Date= $old->Date;
                $this->Comment= $old->Comment;
            }
        }
        //$s= new para();
        
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
        require_once dirname(__FILE__) . '/lib/Classes/PHPExcel.php';
       // $objPHPExcel = PHPExcel_IOFactory::load("fei5.xlsx");
       //fei4_140213
       //$objPHPExcel = PHPExcel_IOFactory::load("fei4_140213.xlsx");//vf5_140213.xlsx
       $objPHPExcel = PHPExcel_IOFactory::load("vf5_140213.xlsx");//postal_3course_140506.xlsx
       //$objPHPExcel = PHPExcel_IOFactory::load("postal_3course_140506.xlsx");
      //-----------------------------------------------------------------------  
      // Функциональная зона. Возвращает: 0)Предмет. 1) Тип занятия. 2)Аудитории (массив) 3) даты 4) преподаватель 5) комментарий. 6)Число строк 7) число столбцов
      
      
      //---------------------------------------------------------------------переменные общего назначения
        $objPHPExcel->getSheetCount();
        $Sheat;//Текущий лист
        $Coll_Start;//начало таблицы (непосредственно данных)
        $Coll_End;//за концом таблицы
        $Row_Start;//начало таблицы
        $Row_End;//за концом таблицы
        $Row_Start_Date;//начало данных
        $Group;//массив с данными.
        $Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
        $gani; //массив хранит границы дней недели
        $date_massiv; // сохраняет названия месяцев и соответсвующие им дни
        $Type_stady; //форма обучения. 0 - дневная, 1 - вечерняя, 2-заочная.
      //-----------------------------------------------------------------------Перемнные заочного распсиания
        $Section_width;// ширина одной секции
        
      //----------------------------------------------------------------------- функции общего назначения
        function read_cell($Staret_Row,$Start_Coll,$Sheet)//Читает ячейку
      {
          global $objPHPExcel;
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
                                       
          while(!($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getBorders()->getRight()->getBorderStyle()!="none"||$objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll+1, $row)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"))
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
            if(trim($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row))!="")
            { 
                $str=trim($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row));
               /**/
                if($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getBold()==1)
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
            //print($row.":".$coll.":".trim($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row))."<BR>");
           }
           while($coll<$Start_Coll+$shirina);
           // print($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle().":".$objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()."<BR>");  
          }
         while(!($objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row)->getStyle()->getBorders()->getBottom()->getBorderStyle()!="none"||$objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll,$row+1)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none"));
          $result[6]=$row-$Staret_Row+1;
          $result[7]=$coll-$Start_Coll+1;
         // print();
          return $result;
          
      }

      function Mesac_to_chislo( $str)// определяет, что за месяц передан в строке и возвращает его номер
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
      function writ_to_bd_d()//Запись в базу данных массива GROUP
      {  
         global $Type_stady;
         global $Group; 
        $link = mysql_connect('localhost', 'root', '') or die('Не удалось соединиться: ' . mysql_error());
        mysql_select_db('raspisanie') or die('Не удалось выбрать базу данных');

        
        for($i=0;$i<count($Group);$i++)
        {
           print("<BR>".$Group[$i]["NameGroup"].":<BR>");
           //проверяем, есть ли такая группа
            $query = "SELECT * FROM groups Where Number_Group='".$Group[$i]["NameGroup"]."'";
            $res_SQL = mysql_query($query);
             $temp=mysql_fetch_array($res_SQL);
             $group_id=false;
            if($temp)
            {
             $group_id= $temp['ID_group'];  
            }
            else
            {
               $query="INSERT INTO groups (Number_Group,Form_of_study) VALUES ('".$Group[$i]["NameGroup"]."',".$Type_stady.")"; 
                mysql_query($query) or die('Не удалось добавить группу ' . mysql_error());
               $query = "SELECT * FROM groups Where Number_Group='".$Group[$i]["NameGroup"]."'";
               $res_SQL = mysql_query($query);
               $temp=mysql_fetch_array($res_SQL);
               if($temp)
               { 
                   $group_id=$temp['ID_group'];
               }
               else
               {
                  die('Не удалось найти добавленную группу');
               }   
            }
            
           for($k=0;$k<count($Group[$i]["Para"]);$k++)
           {
               print($Group[$i]["Para"][$k]->Predmet." ".$Group[$i]["Para"][$k]->Type);
               if($Group[$i]["Para"][$k]->Auditoria!="")
               {
                    for($u=0;$u<count($Group[$i]["Para"][$k]->Auditoria);$u++)
                    {  // print("!".count($Group[$i]["Para"][$k]->Auditoria)."!");
                        print($Group[$i]["Para"][$k]->Auditoria[$u]." ");
                    }
               }
               print($Group[$i]["Para"][$k]->Prepod." Комментарий:".$Group[$i]["Para"][$k]->Comment." Даты:".$Group[$i]["Para"][$k]->Date." Номер пары:".$Group[$i]["Para"][$k]->ParNumber."");//Date
               //проверяем, есть ли такой препод
               $query = "SELECT * FROM lecturer Where Family='".$Group[$i]["Para"][$k]->Prepod."'";
               $res_SQL = mysql_query($query);
               $temp=mysql_fetch_array($res_SQL);
               $prepod_id=false;
               if($temp)
               {                   
                $prepod_id= $temp['ID_Lecturer'];  
               }
                else
                {
                    $query="INSERT INTO lecturer (Family,Department_ID) VALUES ('".$Group[$i]["Para"][$k]->Prepod."',1)"; 
                    mysql_query($query) or die('Не удалось добавить преподавателя ' . mysql_error());
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
            }
               //проверяем, есть ли такой кабинет
                if($Group[$i]["Para"][$k]->Auditoria=="")
                {
                   $Group[$i]["Para"][$k]->Auditoria[0]=-1; 
                   print("!!!НЕТ АУДИТОРИИ!!!");
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
                    mysql_query($query) or die('Не удалось добавить аудиторию ' . mysql_error());
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
                    mysql_query($query) or die('Не удалось добавить предмет ' . mysql_error());
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
              $query="INSERT INTO timetable (ID_Grup,ID_Lecturer,ID_classroom,Time,Date,Type,ID_Subject,Comment) VALUES (".$group_id.",".$prepod_id.",".$Auditoria_id.",".$Group[$i]["Para"][$k]->ParNumber.",'".$nau_ear."-".$d_a_m[1]."-".$d_a_m[0]."',".$type.",". $Subject_id.",'".$Group[$i]["Para"][$k]->Comment."')";  
              mysql_query($query) or die('Не удалось добавить пару ' . mysql_error());
             }
             print("<BR>");
           }
        }
      }  
      //--------------------------------------------------------------------- функции для дневного и вечернего отделения.
      function get_par_number($rows,$Coll_Start,$Sheat, &$NewPar)//получить номер пары
      {
         global $objPHPExcel;
         $k=0;
         do
         {
         $str=$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start-1, $rows+$k);
         $str=trim($str);
         str_replace(" ", "", $str);
         $k++;
         }
         while($str=="");
         switch ($str)
         {
             case "1-2": {$NewPar->ParNumber=1;break;}
             case "3-4": {$NewPar->ParNumber=2;break;}
             case "5-6": {$NewPar->ParNumber=3;break;}
             case "7-8": {$NewPar->ParNumber=4;break;}
             case "9-10": {$NewPar->ParNumber=5;break;}
             case "11-12": {$NewPar->ParNumber=6;break;}
             case "8-00": {$NewPar->ParNumber=1;$NewPar->Comment.=$str;break;}
             case "9-40": {$NewPar->ParNumber=1;$NewPar->Comment.=$str;break;}
             case "11-20": {$NewPar->ParNumber=1;$NewPar->Comment.=$str;break;}
             case "13-00": {$NewPar->ParNumber=1;$NewPar->Comment.=$str;break;}
             default : {$NewPar->ParNumber=0;break;}
         }
         //print("<BR>Было: ".$str.". Номер пары:".$rez."<BR>");
        }
      function get_orientirs_d($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные. 
      {
        global $objPHPExcel;
        global $Coll_Start;//начало таблицы (непосредственно данных)//инициализирует
        global $Coll_End;//за концом таблицы//инициализирует
        global $Row_Start;//начало таблицы//инициализирует
        global $Row_End;//за концом таблицы//инициализирует
        global $Row_Start_Date;//начало данных//инициализирует
        global $Shirina_na_gruppu;//инициализирует
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
          $Row_Start++; 
        }
        $Row_Start++;
        //Print $Row_Start;
        $Row_Start_Date =  $Row_Start+1;
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
          $Row_Start_Date++;
        }
        $Row_End=$Row_Start_Date;
         While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
         {
                 $Row_End++;
         }
         while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start, $Row_Start))))
         {
             $Coll_Start++;
         }
        $count_z=0;
        $coll=$Coll_Start;
        while($count_z<1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $Shirina_na_gruppu++;
            if(trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $Row_Start))!="")
            {
                $count_z++;
            }
        }
        //print($Shirina_na_gruppu);
          $Coll_End=$Coll_Start;
          While(($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End, $Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle()!=="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End-1, $Row_Start)->getStyle()->getBorders()->getRight()->getBorderStyle()!=="none"))
          {
             $Coll_End+=$Shirina_na_gruppu; 
          }
         $Coll_End-=$Shirina_na_gruppu; 
      }
      function group_init_d($Coll_Start,$Coll_End,$Row_Start,$Sheat,$Shirina_na_gruppu)//распознавание групп. Объявляет глобальные переменные
      {
          global $Group;//инициализирует
          global $objPHPExcel;
          $gr_cl=0;
          for($i=$Coll_Start;$i<$Coll_End;$i+=$Shirina_na_gruppu)
          {
           $Group[$gr_cl]["NameGroup"]=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));  
           $Group[$gr_cl]["Para"]= array();
           $gr_cl++;
          } 
      }
      function dey_gran_d($Row_Start_Date,$Row_End,$Sheat)//устанавливает грани между днями недели.
      {
          global $objPHPExcel;
          global $gani;//инициализирует
          $k=0;
           for($i=$Row_Start_Date;$i<$Row_End;$i++)
          {
              if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle()!=="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i+1)->getStyle()->getBorders()->getTop()->getBorderStyle()!=="none")
              {
                  if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB()==="FFFFFF"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB()==="000000")
                {
                  $gani[$k]=$i+1;
                  $k++;
                }
              }
          }
      }
      function get_mounday_d($Coll_Start,$Row_Start,$Sheat,$Row_Start_Date)
      {
          global $gani;
          global $objPHPExcel;
          global $date_massiv; //инициализирует
          for($k=1;$k<$Coll_Start-1;$k++)
          {
              $date_massiv[$k-1]["month"]=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $Row_Start));
              $date_massiv[$k-1]["date"]=array();
              for($p=0;$p<count($gani);$p++)
              {
                  $date_massiv[$k-1]["date"][$p]="";
                  //$date_massiv[$k]["gran"]=$gani[$p];
                  if($p==0)
                  {
                    $i=$Row_Start_Date;  
                  }
                  else
                  {
                     $i=$gani[$p-1];
                  }
                  for($i;$i<$gani[$p];$i++)
                  {
                   if(trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))!="")
                   {
                     $date_massiv[$k-1]["date"][$p].=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))."|";
                   }
                  }
              }
          } 
      }
      
      
      function get_day_raspisanie ()// анализирует дневное распсиание.
      {
            global $objPHPExcel;
            global $Coll_Start;//начало таблицы (непосредственно данных)
            global $Coll_End;//за концом таблицы
            global $Row_Start;//начало таблицы
            global $Row_End;//за концом таблицы
            global $Row_Start_Date;//начало данных
            global $Group;//массив с данными.
            global $Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
            global $gani; //массив хранит границы дней недели
            global $date_massiv;
            global $Type_stady;
          for($Sheat=0;$Sheat<$objPHPExcel->getSheetCount();$Sheat++)
        { 
            $Coll_Start=1;//начало таблицы (непосредственно данных)
            $Coll_End=1;//за концом таблицы
            $Row_Start=0;//начало таблицы
            $Row_End=0;//за концом таблицы
            $Row_Start_Date=0;//начало данных
            $Group=array();//массив с данными.
            $Shirina_na_gruppu=1;//Число ячеек, отведённых на одну группу.
            $gani=false; //массив хранит границы дней недели
            $date_massiv=false;
            
         get_orientirs_d($Sheat);
         group_init_d($Coll_Start,$Coll_End,$Row_Start,$Sheat,$Shirina_na_gruppu);
         dey_gran_d($Row_Start_Date,$Row_End,$Sheat);
         get_mounday_d($Coll_Start,$Row_Start,$Sheat,$Row_Start_Date);          
          for($i=$Row_Start_Date;$i<$Row_End;$i++)
        {
            for($k=$Coll_Start;$k<$Coll_End;$k++)
            {
                if(($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k-1, $i)->getStyle()->getBorders()->getRight()->getBorderStyle()!="none")&&($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i-1)->getStyle()->getBorders()->getBottom()->getBorderStyle()!="none"))
                {  
                   
                   $res= read_cell($i,$k,$Sheat);
                   $nau= floor(($k-$Coll_Start)/$Shirina_na_gruppu);
                   if($res[0]!="")//Если есть название предмета
                   {                       //var_dump($Group[$nau]);
                       $nau_par_count =count($Group[$nau]["Para"]); 
                       if($nau_par_count>0)//Проверяем, если у нас пара не первая
                       {   //["Para"]
                           
                           $Prev_par=$nau_par_count-1;
                           if($Group[$nau]["Para"][$Prev_par]->Predmet==false)//если у предыдущей пары нет предмета
                           {
                               $NewPar=array_pop($Group[$nau]["Para"]);//Вытягиваем предыдущую пару на заполнение.
                           }
                            else
                           {
                               $NewPar=new para();  // Иначе создаём новую пару.
                           }
                       }
                       else// если у нас первая пара
                       {
                           $Prev_par=$nau_par_count;
                           $NewPar=new para();
                       }
                               
                                 if($res[3]=="")//если у нас нет дат в ячейке
                                     {  
                                    for($d=0;$d<count($date_massiv);$d++)
                                         {
                                           $moun=Mesac_to_chislo ($date_massiv[$d]["month"]);
                                             $f=0;
                                             while($i>$gani[$f])
                                             {
                                                 $f++;
                                             }
                                             $dart=$date_massiv[$d]["date"][$f];
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
                           $group_count= floor($res[7]/$Shirina_na_gruppu);
                           get_par_number($i,$Coll_Start,$Sheat,&$NewPar);
                           
                           if($group_count==0&& !is_int(($k-$Coll_Start+$Shirina_na_gruppu)/$Shirina_na_gruppu))
                           { 
                               //print($NewPar->Predmet." ".$NewPar->Type." ".$NewPar->Auditoria." ".$NewPar->Prepod."<BR>");
                             if($NewPar->Auditoria=="")
                             {
                              $NewPar->Auditoria= $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Auditoria;  
                             }
                             if($NewPar->Prepod=="")
                             {
                               $NewPar->Prepod= $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Prepod;     
                             }
                             if($NewPar->Comment=="")
                             {//print("Много мыши!!".$Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Predmet."!");
                              $NewPar->Comment= $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Comment;    
                             }
                             if($NewPar->Type=="")
                             {
                                $NewPar->Type= $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Type;  
                             }
                             //_____________________________________________
                             if($Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Auditoria=="")
                             {
                             $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Auditoria= $NewPar->Auditoria;  
                             }
                             if($Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Prepod=="")
                             {
                              $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Prepod= $NewPar->Prepod;     
                             }
                             if($Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Comment=="")
                             {//print("Много мыши!!".$Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Predmet."!");
                             $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Comment= $NewPar->Comment;    
                             }
                             if($Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Type=="")
                             {
                               $Group[$nau]["Para"][count($Group[$nau]["Para"])-1]->Type;  
                             }
                             //_______________________________________
                             $par_count=floor($res[6]/2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.
                             for($d=0;$d<$par_count;$d++)
                               {    
                                       $par_temp= new para;
                                       $par_temp->copy($NewPar);
                                       $par_temp->ParNumber+=$d;
                                       array_push( $Group[$nau]["Para"],$par_temp);
                                   
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
                               {
                               if(count($Group[$nau+$l]["Para"])>0)
                                {
                                   if($Group[$nau+$l]["Para"][count($Group[$nau+$l]["Para"])-1]->Predmet=="")
                                   {
                                     array_pop($Group[$nau+$l]["Para"]);  
                                   }
                                       
                                }
                                for($z=0;$z<$par_count;$z++)
                                    {
                                       $par_temp= new para;
                                       $par_temp->copy($NewPar);
                                       $par_temp->ParNumber+=$z;
                                       array_push( $Group[$nau+$l]["Para"],$par_temp);
                                    }  
                               }
                           }
                     }
                else // названия предммета нет.
                    {
                     if(trim($res[5])!="")
                        {
                         $NewPar= new para();
                         $NewPar->Predmet=false;
                         $NewPar->Comment=$res[5];  
                         array_push( $Group[$nau]["Para"],$NewPar); 
                        } 
                    }
                }
             
            }
        }
      writ_to_bd_d();  
        }
      }
       //----------------------------------------------------------------------//Функции для заочного распсиания
       function get_orientirs_z($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные. 
      {
        global $objPHPExcel;
        global $Coll_Start;//начало таблицы (непосредственно данных)//инициализирует
        global $Coll_End;//за концом таблицы//инициализирует
        global $Row_Start;//начало таблицы//инициализирует
        global $Row_End;//за концом таблицы//инициализирует
        global $Row_Start_Date;//начало данных//инициализирует
        global $Shirina_na_gruppu;//инициализирует
        global $Section_width;//инициализирует
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
          $Row_Start++; 
        }
        $Row_Start++;
        //Print $Row_Start;
        $Row_Start_Date =  $Row_Start+1;
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
          $Row_Start_Date++;
        }
        $Row_End=$Row_Start_Date;
         While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
         {
          $Row_End++;
         }
         while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start, $Row_Start))))
         {
             $Coll_Start++;
         }
        $count_z=0;
        $coll=$Coll_Start;
        while($count_z<1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $Shirina_na_gruppu++;
            if(trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $Row_Start))!="")
            {
                $count_z++;
            }
        }
        //print($Shirina_na_gruppu);
          $Coll_End=$Coll_Start;
          While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End, $Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End, $Row_Start+1)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
          {
             $Coll_End++; 
          }
          $coll =$Coll_Start;
          while($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()==="FFFFFF"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()==="000000")
          {
              $Section_width++;
              $coll++;
          }
                  
          } 
       //----------------------------------------------------------------------
      $Type_stady=1;// Тип распознаваемого расписания
     
      switch ($Type_stady)
      {
      case 0:{ get_day_raspisanie();break;}//расписание дневное - фас!
      case 1:{ get_day_raspisanie();break;}//распсиание вечернее - фас!
      }
      
      if($Type_stady==2){//будущая функция
          $Coll_Start=1;//начало таблицы (непосредственно данных)
          $Coll_End=1;//за концом таблицы
          $Row_Start=0;//начало таблицы
          $Row_End=0;//за концом таблицы
          $Row_Start_Date=0;//начало данных
          $Group=array();//массив с данными.
          $Shirina_na_gruppu=1;//Число ячеек, отведённых на одну группу.
          $gani=false; //массив хранит границы дней недели
          $date_massiv=false;
          $Section_width=0;
      get_orientirs_z(0);
      print ($Row_Start."|".$Row_Start_Date."|".$Row_End."<Br>".$Coll_Start."|".$Coll_End."|".$Section_width);
      }
      ?>
    </body>
</html>
