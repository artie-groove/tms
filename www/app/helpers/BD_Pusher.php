<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 27.05.14
 * Time: 23:59
 */

/*
 *          public $Predmet;?!
            public $Prepod;!
            public $Type;!
            public $Auditoria;!
            public $ParNumber;
            public $Date;!
            public $Comment;
            public $Group;!
 */
class BD_Pusher
       {
           public function push($par_mass,$Type_stady)//Запись в базу данных массива
              {
               
                $link = mysql_connect('localhost', 'root', '');
                if($link==false)
                {
                    return false;
                }
                $statusDB = mysql_select_db('tms');
                if($statusDB==false)
                {
                    return false;
                }
                $positive = 0;
                $negative = 0;
                for($i=0;$i<count($par_mass);$i++)
                {
                  $group_id=0;  
                  if(trim($par_mass[$i]->Group)!="")
                  {
                     $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
                     $res_SQL = mysql_query($query);
                     if($res_SQL==false)
                     {
                         //print("Провал запроса на группу");
                         return false;
                     }
                     $row = mysql_fetch_assoc($res_SQL);
                     if($row)
                    {
                      $group_id= $row['id']; 
                    }
                    else
                    {
                        preg_match("/\d/", $par_mass[$i]->Group,$mach);
                        $form_stady="";
                        switch ($Type_stady)
                        {
                            case 0:{$form_stady="FULLTIME";break;}
                            case 1:{$form_stady="EVENING";break;}
                            case 2:{$form_stady="EXTRAMURAL";break;}
                            case 3:{$form_stady="SECOND";break;}
                        }
                        //print($mach[0]." ");
                        $query="INSERT INTO groups (name,year,form)VALUES ('".$par_mass[$i]->Group."',".$mach[0].",'".$form_stady."')";
                        $resuktInserGroup= mysql_query($query);
                        if($resuktInserGroup==false)
                        {
                            //print("Провал вставки группы");
                            return false;
                        }
                        $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
                        $res_SQL = mysql_query($query);
                        if($res_SQL==false)
                        {
                           //print("Провал запроса на группу");
                           return false;
                        }
                        $row = mysql_fetch_assoc($res_SQL);
                        if($row)
                        {
                             $group_id= $row['id']; 
                        }
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
                            $res_SQL = mysql_query($query);
                            if($res_SQL==false)
                            {
                                //print("Провал поиска препода по инициалам и фамилии");
                                return false;
                            }
                            if(mysql_affected_rows()==1)
                            {
                                 $row = mysql_fetch_assoc($res_SQL);
                                 $prepod_id=$row['id'];
                            }
                       }
                       else
                       {
                           $query = "SELECT id,surname,name,patronymic FROM lecturers Where surname='".$par_mass[$i]->Prepod."'";
                           $res_SQL = mysql_query($query);
                           if ($res_SQL==false)
                           {
                              //print("Провал поиска препода по фамилии");
                                return false; 
                           }
                           if(mysql_affected_rows()==1)
                            {
                                 $row = mysql_fetch_assoc($res_SQL);
                                 $prepod_id=$row['id'];
                            }
                       }
                  }
                  $predmet_id=0;
                  if($par_mass[$i]->Predmet!="")
                  {
                      preg_match("/\S/ui", $par_mass[$i]->Predmet, $mc);
                      
                      if(count($mc)>0)
                      {
                          //Print(" ".$mc[0]." !");
                         $query = "SELECT id,name FROM disciplines Where name LIKE '".$mc[0]."%'";
                         $res_SQL = mysql_query($query);
                         if($res_SQL==false)
                         {
                           //print("Провал по предметам");
                           return false;
                         }
                         if(mysql_affected_rows()>0)
                         {
                             $base_dump = array();
                             $p=0;
                             while($row = mysql_fetch_assoc($res_SQL))
                             {
                                 $base_dump['id'][$p]=$row['id'];
                                 $base_dump['name'][$p]=$row['name'];
                                 $p++;
                             }
                             
                             //print  ("<BR>__________________________________________________<BR>");
                            // for($t=0;$t<count($base_dump['name']);$t++)
                            // {
                            //   print($base_dump['name'][$t]."<BR>");  
                            // }
                             //var_dump($base_dump['name']);
                             //print ("<BR>");
                             //var_dump($par_mass[$i]->Predmet);
                             //print ("<BR>");;
                             
                             $index=$this->GetMatch($base_dump['name'], $par_mass[$i]->Predmet);
                             if(!is_null($index))
                             {
                                  
                                 //print ($par_mass[$i]->Predmet." ".$base_dump['name'][$index]." ");
                                  $positive++;
                                  $predmet_id=$base_dump['id'][$index];
                             }
                             else 
                             {
                                 //print ("!!!НЕ НАШЁЛ ПРЕДМЕТА!!! ");
                                 $negative++;
                             }
                             
                         }
                      }
                            
                  }
                  $auditoria_id=0;
                  if($par_mass[$i]->Auditoria!="")
                  {
                       $query = "SELECT id,name FROM rooms Where name='".$par_mass[$i]->Auditoria."'";
                       $res_SQL = mysql_query($query);
                       if($res_SQL==false)
                         {
                           //print("Поиск аудитори провалился");
                           return false;
                         }
                       $row = mysql_fetch_assoc($res_SQL);
                       if($row)
                       {
                           $auditoria_id=$row['id'];
                       }
                  } 
                
                if($par_mass[$i]->Date!="")
                {
                  $date_m=  explode(",", $par_mass[$i]->Date);
                  $correct=0;
                  if(trim($date_m[count($date_m)-1])=="")
                  {
                     $correct=1; 
                  }
                  $type_sabjeckt="";
                  $par_mass[$i]->Type= str_replace(".", "", $par_mass[$i]->Type);
                  mb_strtolower($par_mass[$i]->Type);
                  $par_mass[$i]->Type=trim($par_mass[$i]->Type);
                  switch ($par_mass[$i]->Type)
                  {
                    case "лаб":{$type_sabjeckt="LAB";break;}
                    case "лек":{$type_sabjeckt="LECTURE";break;}
                    case "пр":{$type_sabjeckt="WORKSHOP";break;}
                    //case 4:{break;}
                    //case 5:{break;}
                    //case 6:{break;}
                  }
                  $nay_year=date('Y');
                  for($in=0;$in<count($date_m)-$correct;$in++)
                  {
                     $d_m_c = explode(".",$date_m[$in]);
                     $date_to_write=$nay_year."-".$d_m_c[1]."-".$d_m_c[0]; 
                     $query="INSERT INTO timetable (id_discipline,id_group,id_lecturer,id_room,offset,date,type,comment) VALUES (".$predmet_id.",".$group_id.",".$prepod_id.",".$auditoria_id.",".$par_mass[$i]->ParNumber.",'".$date_to_write."','".$type_sabjeckt."','".$par_mass[$i]->Comment."')"; 
                     //echo $query;
                     //echo "<br>";
                     $rez= mysql_query($query);
                     if($rez==false)
                         {
                           //print("Провал вставки расписания");
                           return false;
                         }
                  }
                  /**/
                }
                else 
                    {
                    //print("Нет дат");
                    return false;
                    }
                  //print(" Itteration: ".$i." ".$par_mass[$i]->Group."(".$group_id.") ".$par_mass[$i]->Predmet."(".$predmet_id.") ".$par_mass[$i]->Prepod."(".$prepod_id.") ".$par_mass[$i]->Auditoria."(".$auditoria_id.") ".$par_mass[$i]->Type."<BR>"); 
             }
                //$percent = round(100*$positive/($positive+$negative));
                //echo "positive: $positive; negative: $negative; percent: $percent<BR>";
  
      return true;
}
           private function GetMatch($_subjects, $_short)
   {
       /**
        * Выделение слов в сокрщанеии $_short в масив $shortWords
        * Удаляем последнюю точку, так как из-за неё неправильно составляется массив слов
        */
        $short = mb_convert_case(rtrim($_short), MB_CASE_LOWER);
        //var_dump($short);
        //$short = rtrim($short, '.');
        //var_dump($short);
        $shortWords = mb_split("[,.\\- ]+", $short);
        //var_dump($shortWords);
        //echo "<BR><BR>\n";

       foreach ($_subjects as $_key=>$_subject)
       {
           mb_internal_encoding("UTF-8");
           mb_regex_encoding('UTF-8');
           /**
            * Выделение слов в названии текущего предмета в массив $subjectWords
            */
            //echo "<BR><BR>\n";
            //var_dump($_subject);
            $subject = mb_convert_case($_subject, MB_CASE_LOWER);
            //var_dump($subject);
            $subjectWords = mb_split("[,.\\- ]+", $subject);
            //var_dump($subjectWords);
            //echo "<BR><BR>\n";

           /**
            * Создание аббревиатур
            */
            {
                $abbreviation = "";
                foreach ($subjectWords as $subjectWord)
                {
                    if (mb_strlen($subjectWord)>=3)
                    {
                        $abbreviation .= mb_substr($subjectWord, 0, 1);
                    }
                }

		//echo $abbreviation, " ", $short, "<BR>\n";
                if ($abbreviation === $short)
                {
                    return $_key;
                }
            }

           /**
            * Поиск сокращений. Сравнение идет попарно между словами в $short и $subject.
            * Считаем что слова совпадают если слово из $subject начинается со слова из $short.
            */
            {
                $subjectWordsCount = count($subjectWords);
                $keepTry = true;
                for ($i=0; $i<$subjectWordsCount && $keepTry; $i++)
                {
                    if (!empty($shortWords[$i]))
                    {
                        $position = mb_strpos($subjectWords[$i], $shortWords[$i]);
                        if ( ($position === false) || !($position === 0) )
                        {
                            $keepTry = false;
                        }
                    }
                    else
                    {
                        $keepTry = false;
                    }
                }
                if ($keepTry === true)
                {
                    return $_key;
                }
            }

           /**
            * Отлавливаем креатив когда в названии предмета после каждой буквы ставится пробел
            * по типу 'О р г а н и з а ц и я   и   т е х н о л о г и я   о т р а с л и'.
            * Будем считать что в таком случае название предмета пишется полностью, поэтому
            * сливаем все буквы из $short в одно большое слово, так же слова из $subject сливаем
            * в одно слово и сравниваем их.
            */
            {
                $wordSubject = implode('', $subjectWords);
                $wordShort = implode('', $shortWords);
                //var_dump($wordSubject);
                //var_dump($wordShort);
                if (!empty($wordShort))
                {
                    $position = mb_strpos($wordSubject, $wordShort);
                    if ($position === 0)
                    {
                        return $_key;
                    }
                }
                else
                {
                    return null;
                }
            }
       }

       return null;
   }
}