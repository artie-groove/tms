<?php

class DataImporter extends Handler implements IStatus
{
    public function import($par_mass, $Type_stady, $DisciplineMatcher)//Запись в базу данных массива
    {
        try
        {
            $dbh = new PDO("mysql:host=localhost;dbname=tms", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $dbh->exec("set names utf8");
            $dbh->exec("truncate table timetable_stage");

            $positive = 0;
            $negative = 0;
            $insert = 0;
                        
            for($i = 0; $i < count($par_mass); $i++)
            {
                //if ( ($par_mass[$i]->lecturer === "Хаирова") && ( strpos($par_mass[$i]->comment, '10.00-') !== false) && ($par_mass[$i]->offset === 1)) throw new Exception("Fuck! " . implode("&bull;", (array)$par_mass[$i]) . ' and ' . implode("&bull;", (array)$par_mass[$i-1]));
                
                $group_id = 0;
                if (trim($par_mass[$i]->group) != "")
                {
                    $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->group . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $group_id = $row['id'];
                    else
                    {
                        preg_match("/\d/", $par_mass[$i]->group, $mach);
                        $form_stady = "";
                        switch ($Type_stady)
                        {
                            case 0: $form_stady = "FULLTIME";   break;
                            case 1: $form_stady = "EVENING";    break;
                            case 2: $form_stady = "EXTRAMURAL"; break;
                            case 3: $form_stady = "SECOND";     break;
                        }

                        $res = $dbh->prepare("INSERT INTO groups (name,year,form) VALUES (?, ?, ?)");
                        $res->execute(array($par_mass[$i]->group, $mach[0], $form_stady));

                        $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->group . "'");

                        if ($row = $res->fetch(PDO::FETCH_ASSOC))
                            $group_id = $row['id'];
                    }
                }

                $prepod_id=0;
                $par_mass[$i]->lecturer=trim($par_mass[$i]->lecturer);
                if($par_mass[$i]->lecturer != "")
                {
                    //if ( (strpos($par_mass[$i]->lecturer, 'Перевалова') !== false) && ( $par_mass[$i]->room === 'Б-207' ) ) throw new Exception('Fuck: ' . implode('&bull;', (array)$par_mass[$i]) . '  id => ' . $prepod_id);
                    $inicial = array();
                    if (preg_match_all("/[А-Я]\./u", $par_mass[$i]->lecturer, $matches, PREG_PATTERN_ORDER) > 0)
                    {
                        $surname = $par_mass[$i]->lecturer;
                        for ( $l = 0; $l < count($matches[0]); $l++ )
                        {                            
                            $inicial[$l] = trim(rtrim($matches[0][$l], '.'));                            
                            $surname = trim(str_replace($matches[0][$l], "", $surname));
                        }
                        

                        $query = "
                            SELECT id, surname, name, patronymic
                            FROM lecturers
                            WHERE
                                surname='" . $surname . "'
                                AND (name LIKE '" . $inicial[0] . "%' OR name='-')
                                AND (patronymic LIKE '" . $inicial[1] . "%' OR patronymic='-')";
                        
                        $res = $dbh->query($query);
                        
                        if ( $row = $res->fetch(PDO::FETCH_ASSOC) )
                            $prepod_id = $row['id'];
                        
                        
                    }
                    else
                    {
                        $res = $dbh->query("SELECT id, surname, name, patronymic FROM lecturers WHERE surname='" . $par_mass[$i]->lecturer . "'");

                        if ( $row = $res->fetch(PDO::FETCH_ASSOC) )
                            $prepod_id = $row['id'];
                    }
                }

                $predmet_id=0;
                if($par_mass[$i]->discipline != "")
                {
					$par_mass[$i]->discipline = trim($par_mass[$i]->discipline);
					//$par_mass[$i]->discipline = str_replace('ё', 'е', $par_mass[$i]->discipline);
                    $par_mass[$i]->discipline = str_replace('/', '.', $par_mass[$i]->discipline);
                    
// 					preg_match("/\S/ui", $par_mass[$i]->discipline, $mc);
                    
//                     if (count($mc) > 0)
//                     {
//                     // сначала поищем в таблице сокращений
                        $res = $dbh->query("SELECT id, shortening, id_discipline FROM disciplines_shortenings WHERE shortening = '" . $par_mass[$i]->discipline . "'");

                        if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        {
                            if ($par_mass[$i]->discipline == $row['shortening'])
                            {                                
                                $positive++;
                                $predmet_id = $row['id_discipline'];
                            }
                        }
                        
                        // если не нашли, включаем основной алгоритм поиска
                        if ($predmet_id == 0)
                        {
                            $mc = $par_mass[$i]->discipline;
                            //$mc = str_replace('  ', ' ', $mc);
                            $mc = mb_eregi_replace('\.{2,}', '.', $mc);                            

                            $mc = str_replace(array('.)', '.-', '-', '.,', '.'), array('%', '%', '%', '%, ', '% '), $mc);
//                             $mc = preg_replace('\.\-', '%', $mc);
//                             $mc = preg_replace('\-', '%', $mc);
//                             $mc = preg_replace('\.', '% ', $mc);
                            $mc = mb_eregi_replace('\s{2,}', ' ', $mc);
                            
                            
                            // разбиваем аббревиатуры на отдельные символы
                            $c = 0; // количество произведённых замен
                            $mc = preg_replace('/([А-Я])(?=(?:[А-Я]|\s|$))/u', '$1% ', $mc, -1, $c);
                            $mc = trim($mc);
                            //if ( $c > 0 )  throw new Exception('replaced: ' . $mc . '|');
                            
                            
                            
//                             file_put_contents('log.txt', $mc . "\n", FILE_APPEND);
                            /*
                            $query = "SELECT id, REPLACE(name, 'ё', 'е') AS name FROM disciplines WHERE name LIKE '" . $mc[0] . "%'";
                            $res = $dbh->query($query);                            
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

                                $index = $DisciplineMatcher->GetMatch($base_dump['name'], $par_mass[$i]->discipline);
                                if (!is_null($index))
                                {
                                    $positive++;
                                    $predmet_id = $base_dump['id'][$index];
                                }
                                else
                                    $negative++;
                            }
                            */
                            
                            //$query = "SELECT id, REPLACE(name, 'ё', 'е') AS name FROM disciplines WHERE name LIKE '" . $mc . "'";
                            $query = "SELECT id, name FROM disciplines WHERE name LIKE '" . $mc . "'";
                            $res = $dbh->query($query);                 
							$data = $res->fetch(PDO::FETCH_ASSOC);
                            if ( $data !== FALSE )
                            {
                                $predmet_id = $data['id']; 
                            }
                            else
                            {
                                //preg_match("/\S/ui", $par_mass[$i]->discipline, $mc);
                                $a = mb_substr($mc, 0, 1);
                                //$query = "SELECT id, REPLACE(name, 'ё', 'е') AS name FROM disciplines WHERE name LIKE '" . $a . "%'";
                                $query = "SELECT id, name FROM disciplines WHERE name LIKE '" . $a . "%'";
//                                 file_put_contents('log.txt', $a . "\n", FILE_APPEND);
                                $res = $dbh->query($query);                            
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

                                    $index = $DisciplineMatcher->GetMatch($base_dump['name'], $par_mass[$i]->discipline);
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
//                     }
                }

                $auditoria_id=0;
                
                $special_list = array(631, 717); // Физическая культура
                if ( empty($par_mass[$i]->room) && empty($par_mass[$i]->type) && in_array($predmet_id, $special_list) )
                {
                    $par_mass[$i]->room = 'СК';
                    $par_mass[$i]->type = 'пр';
                    
                }
                    
                //if ( ($par_mass[$i]->lecturer === "Хаирова") && ( strpos($par_mass[$i]->comment, '10.00-') !== false) && ($par_mass[$i]->offset === 1)) throw new Exception("Fuck! " . implode("&bull;", (array)$par_mass[$i]) . ' and disciplineId = ' . $predmet_id);
                
                if ( $par_mass[$i]->room != "" )
                {
                    $res = $dbh->query("SELECT id, name FROM rooms WHERE name='" . $par_mass[$i]->room . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $auditoria_id = $row['id'];
                }

                if($par_mass[$i]->date!="")
                {
                    $date_m = explode(",", $par_mass[$i]->date);
                    $correct = 0;
                    if (trim($date_m[count($date_m) - 1]) == "")
                        $correct = 1;

                    $type_sabjeckt = "";
                    //$par_mass[$i]->type = str_replace(".", "", $par_mass[$i]->type);
                    ///* Установка внутренней кодировки в UTF-8 */
                    //mb_internal_encoding("UTF-8");
                    $par_mass[$i]->type = trim($par_mass[$i]->type, " .\t");
					$par_mass[$i]->type = mb_strtolower($par_mass[$i]->type);
                    //$par_mass[$i]->type = mb_check_encoding($par_mass[$i]->type, 'UTF-8') ? $par_mass[$i]->type : utf8_encode($par_mass[$i]->type);
                    //$par_mass[$i]->type = mb_convert_encoding($par_mass[$i]->type, 'UTF-8', 'cp1251');
                    //$par_mass[$i]->type = iconv('UTF-8', 'cp1251', $par_mass[$i]->type);

                    switch ($par_mass[$i]->type)
                    {
                        case "лаб": $type_sabjeckt = "LAB";      break;
                        case "лек": $type_sabjeckt = "LECTURE";  break;
                        case "пр":  $type_sabjeckt = "WORKSHOP"; break;
                        default:    $type_sabjeckt = 0;          break;
                    }                    

                    $nay_year = date('Y'); // обратить внимание!

                    for ($in = 0; $in < count($date_m) - $correct; $in++)
                    {
                        $d_m_c = explode(".", trim($date_m[$in]));                        
                        $d_m_c[0] = str_pad($d_m_c[0], 2, "0", STR_PAD_LEFT);
                        $d_m_c[1] = str_pad($d_m_c[1], 2, "0", STR_PAD_LEFT);
                        $date_to_write = $nay_year . "-" . $d_m_c[1] . "-" . $d_m_c[0];
                        if ( $d_m_c[0] === '00' || $d_m_c[1] === '00' ) $date_to_write = null;
						$el = $par_mass[$i];
						$comment = implode('&bull; | &bull;', (array)($par_mass[$i]));
                        $comment = rtrim($comment, "&bull;");
                        
                        //mb_substitute_character('long');
                        //$comment = mb_convert_encoding($comment, 'UTF-8', 'UTF-8');
                        
						//$comment = $par_mass[i]->discipline . "  " . $par_mass[i]->lecturer . "  " . $par_mass[i]->type . "  " . $par_mass[i]->room . "  " . $par_mass[i]->offset . "  " . $par_mass[i]->date . "  " . $par_mass[i]->comment . "  " . $par_mass[i]->group;

                        $res = $dbh->prepare("INSERT INTO timetable_stage (id_discipline,id_group,id_lecturer,id_room,`offset`,`date`,`type`,`comment`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $row = $res->execute(array($predmet_id, $group_id, $prepod_id, $auditoria_id, $par_mass[$i]->offset, $date_to_write, $type_sabjeckt, $comment));

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
    }
}