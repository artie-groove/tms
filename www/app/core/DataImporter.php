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
class DataImporter extends Handler implements IStatus
{
    public function import($par_mass, $Type_stady, $DisciplineMatcher)//Запись в базу данных массива
    {

        try
        {
            $dbh = new PDO("mysql:host=localhost;dbname=tms", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $dbh->exec("set character set utf8");
            $dbh->exec("truncate table timetable_stage");

            $positive = 0;
            $negative = 0;
            $insert = 0;
            for($i = 0; $i < count($par_mass); $i++)
            {
                $group_id = 0;
                if (trim($par_mass[$i]->Group) != "")
                {
                    $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->Group . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $group_id = $row['id'];
                    else
                    {
                        preg_match("/\d/", $par_mass[$i]->Group, $mach);
                        $form_stady = "";
                        switch ($Type_stady)
                        {
                            case 0: $form_stady = "FULLTIME";   break;
                            case 1: $form_stady = "EVENING";    break;
                            case 2: $form_stady = "EXTRAMURAL"; break;
                            case 3: $form_stady = "SECOND";     break;
                        }

                        $res = $dbh->prepare("INSERT INTO groups (name,year,form) VALUES (?, ?, ?)");
                        $res->execute(array($par_mass[$i]->Group, $mach[0], $form_stady));

                        $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->Group . "'");

                        if ($row = $res->fetch(PDO::FETCH_ASSOC))
                            $group_id = $row['id'];
                    }
                }

                $prepod_id=0;
                $par_mass[$i]->Prepod=trim($par_mass[$i]->Prepod);
                if($par_mass[$i]->Prepod != "")
                {
                    $inicial = array();
                    if (preg_match_all("/[А-Я]\./ui", $par_mass[$i]->Prepod, $matches, PREG_PATTERN_ORDER) > 0)
                    {
                        for ($l = 0; $l < count($matches[0]); $l++)
                        {
                            $par_mass[$i]->Prepod = trim(str_replace($matches[0][$l], "", $par_mass[$i]->Prepod));
                            $inicial[$l] = trim(rtrim($matches[0][$l], '.'));
                        }

                        $res = $dbh->query("SELECT id, surname, name, patronymic FROM lecturers WHERE surname='" . $par_mass[$i]->Prepod . "' AND name LIKE '" . $inicial[0] . "%' AND patronymic LIKE '" . $inicial[1] . "%'");

                        if($row = $res->fetch(PDO::FETCH_ASSOC))
                            $prepod_id = $row['id'];

                    }
                    else
                    {
                        $res = $dbh->query("SELECT id, surname, name, patronymic FROM lecturers WHERE surname='" . $par_mass[$i]->Prepod . "'");

                        if($row = $res->fetch(PDO::FETCH_ASSOC))
                            $prepod_id = $row['id'];
                    }
                }

                $predmet_id=0;
                if($par_mass[$i]->Predmet != "")
                {
					$par_mass[$i]->Predmet = trim($par_mass[$i]->Predmet);
					$par_mass[$i]->Predmet = str_replace('ё', 'е', $par_mass[$i]->Predmet);

					preg_match("/\S/ui", $par_mass[$i]->Predmet, $mc);

                    if (count($mc) > 0)
                    {
                        $res = $dbh->query("SELECT id, shortening, id_discipline FROM disciplines_shortenings WHERE shortening = '" . $par_mass[$i]->Predmet . "'");

                        if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        {
                            if ($par_mass[$i]->Predmet == $row['shortening'])
                            {
                                $positive++;
                                $predmet_id = $row['id_discipline'];
                            }
                        }

                        if ($predmet_id == 0)
                        {
                            $res = $dbh->query("SELECT id, REPLACE(name, 'ё', 'е') AS name FROM disciplines WHERE REPLACE(name, 'ё', 'е') LIKE '" . $mc[0] . "%'");
							$data = $res->fetchAll(PDO::FETCH_ASSOC);

                            if (count($data))
                            {
                                $base_dump = array();
                                $p = 0;
								foreach($data as $row)
                                {
                                    $base_dump['id'][$p] = $row['id'];
                                    $base_dump['name'][$p] = $row['name'];
                                    $p++;
                                }

                                $index = $DisciplineMatcher->GetMatch($base_dump['name'], $par_mass[$i]->Predmet);
                                if (!is_null($index))
                                {
                                    $positive++;
                                    $predmet_id = $base_dump['id'][$index];
                                }
                                else
                                    $negative++;
                            }
                        }
                    }
                }

                $auditoria_id=0;
                 if($par_mass[$i]->Auditoria!="")
                {
                    $res = $dbh->query("SELECT id, name FROM rooms WHERE name='" . $par_mass[$i]->Auditoria . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $auditoria_id = $row['id'];
                }

                if($par_mass[$i]->Date!="")
                {
                    $date_m = explode(",", $par_mass[$i]->Date);
                    $correct = 0;
                    if (trim($date_m[count($date_m) - 1]) == "")
                        $correct = 1;

                    $type_sabjeckt = "";
                    //$par_mass[$i]->Type = str_replace(".", "", $par_mass[$i]->Type);
                    $par_mass[$i]->Type = trim($par_mass[$i]->Type, " .\t\x\n\0\r");
					$par_mass[$i]->Type = mb_strtolower($par_mass[$i]->Type);

                    switch ($par_mass[$i]->Type)
                    {
                        case "лаб": $type_sabjeckt = "LAB";      break;
                        case "лек": $type_sabjeckt = "LECTURE";  break;
                        case "пр":  $type_sabjeckt = "WORKSHOP"; break;
                        default:    $type_sabjeckt = 0;          break;
                    }

                    $nay_year = date('Y');

                    for ($in = 0; $in < count($date_m) - $correct; $in++)
                    {
                        $d_m_c = explode(".", trim($date_m[$in]));
                        $d_m_c[0] = str_pad($d_m_c[0], 2, "0", STR_PAD_LEFT);
                        $d_m_c[1] = str_pad($d_m_c[1], 2, "0", STR_PAD_LEFT);
                        $date_to_write = $nay_year . "-" . $d_m_c[1] . "-" . $d_m_c[0];
						$el = $par_mass[$i];
						$comment = implode('- / -', (array)($par_mass[$i]));
						//$comment = $par_mass[i]->Predmet . "  " . $par_mass[i]->Prepod . "  " . $par_mass[i]->Type . "  " . $par_mass[i]->Auditoria . "  " . $par_mass[i]->ParNumber . "  " . $par_mass[i]->Date . "  " . $par_mass[i]->Comment . "  " . $par_mass[i]->Group;

                        $res = $dbh->prepare("INSERT INTO timetable_stage (id_discipline,id_group,id_lecturer,id_room,`offset`,`date`,`type`,`comment`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $row = $res->execute(array($predmet_id, $group_id, $prepod_id, $auditoria_id, $par_mass[$i]->ParNumber, $date_to_write, $type_sabjeckt, $comment));

                        if ($row)
                            $insert++;
                    }
                }
            }
            $dbh = null;
            $this->setStatus("OK", "Массив данных успешно загружен в базу данных", "Добавлено $insert записей");
            return true;
        }
        catch(PDOException $exc)
        {
            $dbh = null;
            $this->setStatus("Error", "On line:" . $exc->getLine() . " --- " . $exc->getMessage());
            return false;
        }
//                $link = mysql_connect('localhost', 'root', '');
//				if($link==false)
//                {
//                    $this->setStatus("Error", "Ошибка при подключении к БД");
//                    return false;
//                }
//
//				mysql_set_charset('utf8');
//				//mb_internal_encoding("UTF-8");
//                //mb_regex_encoding('UTF-8');
//                $statusDB = mysql_select_db('tms');
//                if($statusDB==false)
//                {
//                    $this->setStatus("Error", "Ошибка при подключении к таблице tms");
//                    return false;
//                }
//
//                // purge table `timetable`
//               $query = "TRUNCATE TABLE timetable";
//               $res_SQL = mysql_query($query);
//               if($res_SQL==false)
//               {
//                   $this->setStatus("Error", "Не удалось очистить таблицу расписания", "Запрос: $query");
//                   return false;
//               }
//
//                $positive = 0;
//                $negative = 0;
//                $insert=0;
//                for($i=0;$i<count($par_mass);$i++)
//                {
//                  $group_id=0;
//                  if(trim($par_mass[$i]->Group)!="")
//                  {
//                     $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
//                     $res_SQL = mysql_query($query);
//                     if($res_SQL==false)
//                     {
//                         //print("Провал запроса на группу");
//                         $this->setStatus("Error", "Ошибка заброса SQL при попытки найти группу","Падение на запросе: $query");
//                         return false;
//                     }
//                     $row = mysql_fetch_assoc($res_SQL);
//                     if($row)
//                    {
//                      $group_id= $row['id'];
//                    }
//                    else
//                    {
//                        preg_match("/\d/", $par_mass[$i]->Group,$mach);
//                        $form_stady="";
//                        switch ($Type_stady)
//                        {
//                            case 0:{$form_stady="FULLTIME";break;}
//                            case 1:{$form_stady="EVENING";break;}
//                            case 2:{$form_stady="EXTRAMURAL";break;}
//                            case 3:{$form_stady="SECOND";break;}
//                        }
//                        //print($mach[0]." ");
//                        $query="INSERT INTO groups (name,year,form)VALUES ('".$par_mass[$i]->Group."',".$mach[0].",'".$form_stady."')";
//                        $resuktInserGroup= mysql_query($query);
//                        if($resuktInserGroup==false)
//                        {
//                            //print("Провал вставки группы");
//                            $this->setStatus("Error", "Ошибка заброса SQL при попытки добавить новую группу","Падение на запросе: $query");
//                            return false;
//                        }
//                        $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
//                        $res_SQL = mysql_query($query);
//                        if($res_SQL==false)
//                        {
//                           //print("Провал запроса на группу");
//                           $this->setStatus("Error", "Ошибка заброса SQL при попытки найти вновь добавленную группу","Падение на запросе: $query");
//                           return false;
//                        }
//                        $row = mysql_fetch_assoc($res_SQL);
//                        if($row)
//                        {
//                             $group_id= $row['id'];
//                        }
//                    }
//                  }
//
//                  $prepod_id=0;
//                  $par_mass[$i]->Prepod=trim($par_mass[$i]->Prepod);
//                  if($par_mass[$i]->Prepod!="")
//                  {
//                       $inicial=array();
//                       if(preg_match_all("/[А-Я]\./ui", $par_mass[$i]->Prepod, $matches,PREG_PATTERN_ORDER)>0)
//                       {
//                           for($l=0;$l<count($matches[0]);$l++)
//                            {
//                               $par_mass[$i]->Prepod=trim(str_replace($matches[0][$l],"", $par_mass[$i]->Prepod));
//                               $inicial[$l]=trim(rtrim($matches[0][$l],'.'));
//                            }
//                            $query = "SELECT id,surname,name,patronymic FROM lecturers Where surname='".$par_mass[$i]->Prepod."' AND name LIKE '".$inicial[0]."%' AND patronymic LIKE '".$inicial[1]."%'";
//                           //echo $query;
//                            $res_SQL = mysql_query($query);
//                            if($res_SQL==false)
//                            {
//                                //print("Провал поиска препода по инициалам и фамилии");
//                                $this->setStatus("Error", "Ошибка заброса SQL при попытки найти преподавателя по инициалам и фамилии","Падение на запросе: $query");
//                                return false;
//                            }
//                            if(mysql_affected_rows()==1)
//                            {
//                                 $row = mysql_fetch_assoc($res_SQL);
//                                 $prepod_id=$row['id'];
//                            }
//                       }
//                       else
//                       {
//                           $query = "SELECT id,surname,name,patronymic FROM lecturers Where surname='".$par_mass[$i]->Prepod."'";
//                           $res_SQL = mysql_query($query);
//                           if ($res_SQL==false)
//                           {
//                              //print("Провал поиска препода по фамилии");
//                               $this->setStatus("Error", "Ошибка заброса SQL при попытки найти преподавателя по фамилии","Падение на запросе: $query");
//                                return false;
//                           }
//                           if(mysql_affected_rows()==1)
//                            {
//                                 $row = mysql_fetch_assoc($res_SQL);
//                                 $prepod_id=$row['id'];
//                            }
//
//                       }
//                  }
//                  $predmet_id=0;
//                  if($par_mass[$i]->Predmet!="")
//                  {
//                      preg_match("/\S/ui", $par_mass[$i]->Predmet, $mc);
//
//                      if(count($mc)>0)
//                      {
//                         //Print(" ".$mc[0]." !");
//
//                         $query = "SELECT id, shortening, id_discipline FROM disciplines_shortenings WHERE shortening = '" . $par_mass[$i]->Predmet . "'";
//                         $res_SQL = mysql_query($query);
//                         if( $res_SQL == false )
//                         {
//                           //print("Провал по предметам");
//                           $this->setStatus("Error", "Ошибка заброса SQL при попытки найти предмет","Падение на запросе: $query");
//                           return false;
//                         }
//                         if( mysql_num_rows($res_SQL) == 1 )
//                         {
//                             $row = mysql_fetch_assoc($res_SQL);
//
//                             if ( $par_mass[$i]->Predmet == $row['shortening'] )
//                             {
//                                 $positive++;
//                                 $predmet_id = $row['id_discipline'];
//                             }
//
//                         }
//
//                         if ( $predmet_id == 0 )
//                         {
//                             $query = "SELECT id,name FROM disciplines Where name LIKE '".$mc[0]."%'";
//                             $res_SQL = mysql_query($query);
//                             if($res_SQL==false)
//                             {
//                               //print("Провал по предметам");
//                               $this->setStatus("Error", "Ошибка заброса SQL при попытки найти предмет","Падение на запросе: $query");
//                               return false;
//                             }
//                             if(mysql_affected_rows()>0)
//                             {
//                                 $base_dump = array();
//                                 $p=0;
//                                 while($row = mysql_fetch_assoc($res_SQL))
//                                 {
//                                     $base_dump['id'][$p]=$row['id'];
//                                     $base_dump['name'][$p]=$row['name'];
//                                     $p++;
//                                 }
//
//                                 //print  ("<BR>__________________________________________________<BR>");
//                                // for($t=0;$t<count($base_dump['name']);$t++)
//                                // {
//                                //   print($base_dump['name'][$t]."<BR>");
//                                // }
//                                 //var_dump($base_dump['name']);
//                                 //print ("<BR>");
//                                 //var_dump($par_mass[$i]->Predmet);
//                                 //print ("<BR>");;
//
//                                 $index = $DisciplineMatcher->GetMatch($base_dump['name'], $par_mass[$i]->Predmet);
//                                 if(!is_null($index))
//                                 {
//
//                                     //print ($par_mass[$i]->Predmet." ".$base_dump['name'][$index]." ");
//                                      $positive++;
//                                      $predmet_id=$base_dump['id'][$index];
//                                 }
//                                 else
//                                 {
//                                     //print ("!!!НЕ НАШЁЛ ПРЕДМЕТА!!! ");
//                                     $negative++;
//                                 }
//                            }
//                         }
//                      }
//
//                  }
//                  $auditoria_id=0;
//                  if($par_mass[$i]->Auditoria!="")
//                  {                                                       //iconv('windows-1251', 'UTF-8', $par_mass[$i]->Auditoria);
//                       $query = "SELECT id,name FROM rooms Where name='".$par_mass[$i]->Auditoria."'";
//                       $res_SQL = mysql_query($query);
//                       if($res_SQL==false)
//                         {
//                           //print("Поиск аудитори провалился");
//                           $this->setStatus("Error", "Ошибка заброса SQL при попытки найти аудиторию","Падение на запросе: $query");
//                           return false;
//                         }
//                       $row = mysql_fetch_assoc($res_SQL);
//                       if($row)
//                       {
//                           $auditoria_id=$row['id'];
//                           // print($row['1']."<br>");
//                       }
//                      /* else
//                       {
//                           print($par_mass[$i]->Auditoria.":".$query."res_SQL: $res_SQL -".mysql_error()."<BR>");
//                       }*/
//                  }
//
//                if($par_mass[$i]->Date!="")
//                {
//                  $date_m=  explode(",", $par_mass[$i]->Date);
//                  $correct=0;
//                  if(trim($date_m[count($date_m)-1])=="")
//                  {
//                     $correct=1;
//                  }
//                  $type_sabjeckt="";
//                  $par_mass[$i]->Type= str_replace(".", "", $par_mass[$i]->Type);
//                  mb_strtolower($par_mass[$i]->Type);
//                  $par_mass[$i]->Type=trim($par_mass[$i]->Type);
//                  switch ($par_mass[$i]->Type)
//                  {
//                    case "лаб":{$type_sabjeckt="LAB";break;}
//                    case "лек":{$type_sabjeckt="LECTURE";break;}
//                    case "пр":{$type_sabjeckt="WORKSHOP";break;}
//                    //case 4:{break;}
//                    //case 5:{break;}
//                    //case 6:{break;}
//                    default: $type_sabjeckt = 0;
//                  }
//                  $nay_year=date('Y');
//
//                  for($in=0;$in<count($date_m)-$correct;$in++)
//                  {
//                     $d_m_c = explode(".", trim($date_m[$in]));
//                     $d_m_c[0] = str_pad($d_m_c[0], 2, "0", STR_PAD_LEFT);
//                     $d_m_c[1] = str_pad($d_m_c[1], 2, "0", STR_PAD_LEFT);
//
//                     $date_to_write=$nay_year."-".$d_m_c[1]."-".$d_m_c[0];
//                     $query="INSERT INTO timetable (id_discipline,id_group,id_lecturer,id_room,`offset`,`date`,`type`,`comment`) VALUES (".$predmet_id.",".$group_id.",".$prepod_id.",".$auditoria_id.",".$par_mass[$i]->ParNumber.",'".$date_to_write."','".$type_sabjeckt."','".$par_mass[$i]->Comment."')";
//                     //echo $query;
//                     //echo "<br>";
//
//                     $rez= mysql_query($query);
//                     if($rez==false)
//                         {
//                         $this->setStatus("Error", "Ошибка заброса SQL при попытки добавить запись о паре в БД","Падение на запросе: $query" . ': ' . mysql_error() . ': ' . implode('-', $d_m_c) . ', type: ' . $type_sabjeckt);
//                           //print("Провал вставки расписания");
//                          return false;
//                         }
//                         else
//                         {
//                          $insert++;
//                         }
//                  }
//                  /**/
//                }
//                else
//                    {
//                    //print("Нет дат");
//                    //$this->setStatus("Error", "В одном из элементов массива нет даты. Невозможно добавление в БД","Номер элемента массива: $i");
//                    //return false;
//                    }
//                  //print(" Itteration: ".$i." ".$par_mass[$i]->Group."(".$group_id.") ".$par_mass[$i]->Predmet."(".$predmet_id.") ".$par_mass[$i]->Prepod."(".$prepod_id.") ".$par_mass[$i]->Auditoria."(".$auditoria_id.") ".$par_mass[$i]->Type."<BR>");
//             }
//                //$percent = round(100*$positive/($positive+$negative));
//                //echo "positive: $positive; negative: $negative; percent: $percent<BR>";
//  $this->setStatus("OK", "Массив данных успешно загружен в базу данных","Добавлено $insert записей");
//  return true;
    }
}