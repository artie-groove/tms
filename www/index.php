
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        /**/
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
            
        }
        //$s= new para();
        
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
        require_once dirname(__FILE__) . '/lib/Classes/PHPExcel.php';
        $objPHPExcel = PHPExcel_IOFactory::load("fei5.xlsx");
        
      //-----------------------------------------------------------------------  
      // Функциональная зона. Возвращает: 0)Предмет. 1) Тип занятия. 2)Аудитории (массив) 3) даты 4) преподаватель 5) комментарий.
      function read_cell($Staret_Row,$Start_Coll,$Sheet)//Читает ячейку
      {
          global $objPHPExcel;
          $row=$Staret_Row;
          $result[0]="";
          $result[1]="";
          $result[2]="";
          $result[3]="";
          $result[4]="";
          $rezult[5]="";
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
                    $rezult[5].=" ".$matches[0];
                    }
                    $result[0]=$str;
                   // print("Предмет:".$str."<BR>");
                    
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
                            $rezult[3]=$maches[0];
                            $str=  str_replace($maches[0], "", $str);
                            $str=trim($str);
                        }
                      if(preg_match("/(([а-я](\.)?( )*){0,2}[а-я][а-я]+)|[а-я][а-я]+( )+(([а-я](\.)?( )*){0,2})/ui", $str,$maches))
                        {
                            //print("Препод:".$maches[0]."<BR>");
                             $rezult[4]=$maches[0]; 
                             $str=  str_replace($maches[0], "", $str);
                             $str=trim($str);
                        }
                        if(trim($str)!="")
                        {
                           // Print("Комент: ".$str."<BR>");
                            $rezult[5].=$str." ";
                            
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
          return $result;
          
      }

      function Mesac_to_chislo( $str)
      {  
         str_replace("a", "а", $str); 
         str_replace("A", "А", $str);
         str_replace("c", "с", $str); 
         str_replace("C", "С", $str);
         str_replace("e", "е", $str); 
         str_replace("E", "Е", $str);
         str_replace("o", "о", $str); 
         str_replace("O", "О", $str);
          switch (trim($str))
                                    {
                                        case "Январь": {$messs=01; break;}
                                        case "январь": {$messs=01; break;}
                                        case "ЯНВАРЬ": {$messs=01; break;}
                                        case "Февраль": {$messs=02; break;}
                                        case "февраль": {$messs=02; break;}
                                        case "ФЕВРАЛЬ": {$messs=02; break;}
                                        case "Март": {$messs=03; break;}
                                        case "март": {$messs=03; break;}
                                        case "МАРТ": {$messs=03; break;}
                                        case "Апрель": {$messs=04; break;}
                                        case "апрель": {$messs=04; break;}
                                        case "АПРЕЛЬ": {$messs=04; break;}
                                        case "Май": {$messs=05; break;}
                                        case "май": {$messs=05; break;}
                                        case "МАЙ": {$messs=05; break;}
                                        case "Июнь": {$messs=06; break;}
                                        case "июнь": {$messs=06; break;}
                                        case "ИЮНЬ": {$messs=06; break;}
                                        case "Июль": {$messs=07; break;}
                                        case "июль": {$messs=07; break;}
                                        case "ИЮЛЬ": {$messs=07; break;}
                                        case "Август": {$messs=08; break;}
                                        case "август": {$messs=08; break;}
                                        case "АВГУСТ": {$messs=08; break;}
                                        case "Сентябрь": {$messs=09; break;}
                                        case "сентябрь": {$messs=09; break;}
                                        case "СЕНТЯБРЬ": {$messs=09; break;}
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
      //-----------------------------------------------------------------------
       // $objPHPExcel->getAllSheets();
       // var_dump($objPHPExcel->getSheet(0)->getCell()->getStyle()->getBorders()->getRight()->getBorderStyle());
        /*
        for($i=10;$i<156;$i++)
        {
        echo $objPHPExcel->getSheet(0)->getCellByColumnAndRow(21, $i);
        echo "<br>";
        var_dump($objPHPExcel->getSheet(0)->getCellByColumnAndRow(21, $i)->getStyle()->getBorders()->getRight()->getBorderStyle());
        echo "<br>";
        $objPHPExcel->getSheet();
        }
        */
       // ----------------------------------------------------------------------
       //Тестовая зона
        /** /
      echo $objPHPExcel->getSheet(0)->getCellByColumnAndRow(1, 35)->getStyle()->getBorders()->getBottom()->getBorderStyle();
      echo "<br>";
      echo"--------------------------------------------------------------------<br>";
      /**/
       //----------------------------------------------------------------------
        $Coll_Start=1;//начало таблицы (непосредственно данных)
        $Coll_End=1;//за концом таблицы
        $Row_Start=0;//начало таблицы
        $Row_End=0;//за концом таблицы
        $Sheat=0;//Текущий лист
        $Row_Start_Date=0;//начало данных
        $Group;//массив с данными.
        $Shirina_na_gruppu=-1;//Число ячеек, отведённых на одну группу.
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
          $Row_Start++; 
        }
        $Row_Start++;
        //echo  $Row_Start; 
        //$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End)->getStyle()->getBorders()->getTop()->getBorderStyle()!=="none"
        //$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End)->getStyle()->getFill()->getStartColor()->getRGB();
        $Row_Start_Date =  $Row_Start+1;
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF")
        {
          $Row_Start_Date++;
        }
    //    echo $Row_Start_Date;
        $Row_End=$Row_Start_Date;
         While(!($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End-1)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"))
         {
             if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF")
             {
                 $Row_End++;
             }
            else 
                {
                 $Row_End+=2;
                }
         //  Print($Row_End.":".$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $Row_End)->getStyle()->getBorders()->getTop()->getBorderStyle()."<Br>");     
         }
         $Row_End-=2;
         //echo $Row_End;
         //print (trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(8, $Row_Start))."<BR>");
         //print(preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(8, $Row_Start))));
         while (!preg_match("/[А-Яа-я]+( )*-( )*\d\d\d/",trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_Start, $Row_Start))))
         {
             $Coll_Start++;
         }
         //$Coll_Start++;
        //print( $Coll_Start."<BR>");
         $coll = $Coll_Start;
       //  print($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle());
       
        $count_z=0;
        while($count_z<2)
        {
            $coll++;
            $Shirina_na_gruppu++;
            if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $Row_Start)!="")
            {
                $count_z++;
            }
        }
          $Coll_End=$Coll_Start;
          While(($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End, $Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle()!=="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($Coll_End-1, $Row_Start)->getStyle()->getBorders()->getRight()->getBorderStyle()!=="none"))
          {
             $Coll_End+=$Shirina_na_gruppu; 
          }
         $Coll_End-=$Shirina_na_gruppu;
        // print($Coll_End."<BR>");
         //var_dump(read_cell(28,8,0)) ; 
         //var_dump($objPHPExcel->getSheet(0)->getCellByColumnAndRow(9, 28)->getStyle()->getBorders()->getRight()->getBorderStyle())  ;
          $gr_cl=0;
          for($i=$Coll_Start;$i<$Coll_End;$i+=$Shirina_na_gruppu)
          {
           $Group[$gr_cl]["NameGroup"]=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($i, $Row_Start));  
           $Group[$gr_cl]["Para"]= array();
           $gr_cl++;
          }
          //echo  $Coll_End;
          //var_dump($Group);
          /**/
          $gani;
          $k=0;
          for($i=$Row_Start_Date;$i<$Row_End;$i++)
          {
              if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i)->getStyle()->getBorders()->getBottom()->getBorderStyle()!=="none"||$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $i+1)->getStyle()->getBorders()->getTop()->getBorderStyle()!=="none")
              {
                  if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $i)->getStyle()->getFill()->getStartColor()->getRGB()==="FFFFFF")
                {
                  $gani[$k]=$i+1;
                  $k++;
                }
              }
          }
          $date_massiv;
          for($k=1;$k<$Coll_Start-1;$k++)
          {
              $date_massiv[$k]["month"]=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $Row_Start));
              $date_massiv[$k]["date"]=array();
              for($p=0;$p<count($gani);$p++)
              {
                  $date_massiv[$k]["date"][$p]="";
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
                     $date_massiv[$k]["date"][$p].=trim($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i))."|";
                   }
                  }
              }
          }
        // var_dump($date_massiv);
          /**/
          for($i=$Row_Start_Date;$i<$Row_End;$i++)
        {
            for($k=$Coll_Start;$k<$Coll_End;$k++)
            {
                if($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"&&$objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none")
                {
                   $res= read_cell($i,$k,$Sheat);
                 //  print($i.":".$k.":".$res[0].":".$res[1].":".$res[2]."<BR>");
                   $nau= floor(($k-$Coll_Start)/$Shirina_na_gruppu);
                   if($res[0]!="")//Если есть название предмета
                   {   
                       $Prev_par= $Group[$nau](count($Group[$nau])-1);
                       if($Prev_par>=0)//Проверяем, если у нас пара не первая
                       {   //["Para"]
                           if($Group[$nau]["Para"][$Prev_par]->Predmet==false)//если у предыдущей пары нет предмета
                           {
                               $NewPar=array_pop($Group[$nau]["Para"]);//Вытягиваем предыдущую пару на заполнение.
                           }
                            else
                           {
                               $NewPar=new para();  // Иначе создаём новую пару.
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
                                             
                                        }
                                     }
                                     else// если даты в ячейке есть
                                    {
                       
                                    }
                           
                          
                       }
                       else// если у нас первая пара
                       {
                           
                       }
                    }
                else // названия предммета нет.
                    {
                    // ВНИМАНИЕ!!!!!!---------------------------- Добавь проверку на комментарий!!!! Вдруг ячейка вообще пустая?
                    //  $NewPar= new para();
                    //  $NewPar->Predmet=false;
                    //  $NewPar->Comment=$res[5];
                    }
                }
             
            }
        }
        /**/
       // print($objPHPExcel->getSheet(0)->getCellByColumnAndRow(16,54)->getStyle()->getBorders()->getRight()->getBorderStyle().":".$objPHPExcel->getSheet(0)->getCellByColumnAndRow(15,54)->getStyle()->getBorders()->getLeft()->getBorderStyle()."<BR>");  
         
         $res= read_cell(42,20,0);
       //  var_dump($res);
       //  echo  preg_match("/[с]( )+\d{1,2}[-:\.]\d\d/iu", $res[0], $matches) ;
       //  var_dump($matches);
        /**/
          ?>
    </body>
</html>
