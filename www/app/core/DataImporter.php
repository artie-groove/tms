<?php

class DataImporter extends Handler implements IStatus
{
    public function import($storage, $DisciplineMatcher)//Запись в базу данных массива
    {
        list ( $type, $par_mass ) = array_values($storage[0]); // ToDo: implement a cycle
        try
        {
            $dbh = new PDO("mysql:host=localhost;dbname=tms", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $dbh->exec("set names utf8");
            $dbh->exec("truncate table timetable_stage");

            $positive = 0;
            $negative = 0;
            $insert = 0;
            
            $logfile = $_SERVER['DOCUMENT_ROOT'] . '/punctuation.log';
            if ( file_exists($logfile) ) unlink($logfile);      
            
            for ( $i = 0; $i < count($par_mass); $i++ )
            {
                $group_id = 0;
                if (trim($par_mass[$i]->group) != "")
                {
                    $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->group . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $group_id = $row['id'];
                    else
                    {
                        preg_match('/\d/', $par_mass[$i]->group, $mach);
                 
                        $form_stady = "FULLTIME";
                        /*
                        switch ($Type_stady)
                        {
                            case 0: $form_stady = "FULLTIME";   break;
                            case 1: $form_stady = "EVENING";    break;
                            case 2: $form_stady = "EXTRAMURAL"; break;
                            case 3: $form_stady = "SECOND";     break;
                        }
                        */

                        $res = $dbh->prepare("INSERT INTO groups (name,year,form) VALUES (?, ?, ?)");
                        $res->execute(array($par_mass[$i]->group, $mach[0], $form_stady));

                        $res = $dbh->query("SELECT id, name FROM groups WHERE name='" . $par_mass[$i]->group . "'");

                        if ($row = $res->fetch(PDO::FETCH_ASSOC))
                            $group_id = $row['id'];
                        else
                            throw new Exception('Не удалось получить идентификатор группы ' . $par_mass[$i]->group);
                    }
                }

                $prepod_id=0;
                $par_mass[$i]->lecturer=trim($par_mass[$i]->lecturer);
                if($par_mass[$i]->lecturer != "")
                {
                    $inicial = array_fill(0, 2, '');
                    if ( preg_match_all("/[А-Я]\./u", $par_mass[$i]->lecturer, $matches, PREG_PATTERN_ORDER) )
                    {
                        $surname = $par_mass[$i]->lecturer;
                        $n = count($matches[0]); // один или два инициала                        
                        for ( $l = 0; $l < $n; $l++ ) {                            
                            $inicial[$l] = rtrim($matches[0][$l], '.');
                            $surname = trim(str_replace($matches[0][$l], '', $surname));
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
                    //$par_mass[$i]->discipline = str_replace('/', '.', $par_mass[$i]->discipline);
                    
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
                        if ( $predmet_id == 0 )
                        {
                            $mc = $par_mass[$i]->discipline;
                                                        
                            // устраняем дублирование пунктуации
                            $mc = preg_replace('/(\s|\.|-)(?=\1)/u', '', $mc);
                            
                            // корректируем типографику
                            $mc = preg_replace('/(\.|\.?,)(?![\s-,)]|$)/u', '$1 ', $mc);
                            
                            // вставляем подстановочный знак % вместо точек, дефисов и слэшей
                            $mc = preg_replace('/[\.\/-]/u', '%', $mc);
                            
                            // разбиваем аббервиатуры
                            $mc = preg_replace('/([A-ZА-Я])(?=[A-ZА-Я,\s]|$)/u', '$1%', $mc);
                            
                            // вычленяем союзы из аббревиатур (например, союз "и" в аббревиатуре "ИСПиУ")
                            $mc = preg_replace('/(?<=[А-Я])([а-я])(?=[А-Я])/u', '% $1 ', $mc);
                            
                            $query = "SELECT id, name FROM disciplines WHERE name LIKE '" . $mc . "' ORDER BY CHAR_LENGTH(name) ASC";
                            $res = $dbh->query($query);                 
							$data = $res->fetch(PDO::FETCH_ASSOC);
                            
                            if ( $data !== FALSE )
                            {
                                $predmet_id = $data['id']; 
                            }
                            else
                            {
                                file_put_contents($logfile, $par_mass[$i]->discipline . "\n", FILE_APPEND);
                                
                                $a = mb_substr($mc, 0, 1);
                                
                                $query = "SELECT id, name FROM disciplines WHERE name LIKE '" . $a . "%'";

                                $res = $dbh->query($query);                            
                                $data = $res->fetchAll(PDO::FETCH_ASSOC);

                                if ( count($data) )
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
                    
                //if ( ($par_mass[$i]->lecturer === "Хаирова") && ( strpos($par_mass[$i]->comment, '10.00-') !== false) && ($par_mass[$i]->time === 1)) throw new Exception("Fuck! " . implode("&bull;", (array)$par_mass[$i]) . ' and disciplineId = ' . $predmet_id);
                
                if ( $par_mass[$i]->room != "" )
                {
                    $res = $dbh->query("SELECT id, name FROM rooms WHERE name='" . $par_mass[$i]->room . "'");

                    if ($row = $res->fetch(PDO::FETCH_ASSOC))
                        $auditoria_id = $row['id'];
                }

                if($par_mass[$i]->dates!="")
                {
                    $date_m = explode(",", $par_mass[$i]->dates);
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
                        case "лаб":     $type_sabjeckt = "LAB";         break;
                        case "лек":     $type_sabjeckt = "LECTURE";     break;
                        case "пр":      $type_sabjeckt = "WORKSHOP";    break;
                        case "конс":    $type_sabjeckt = "TUTORIAL";    break;
                        case "зач":     $type_sabjeckt = "QUIZ";        break;
                        case "экз":     $type_sabjeckt = "EXAMINATION"; break;
                        default:        $type_sabjeckt = 0;             break;
                    }                    

                    $nay_year = date('Y'); // обратить внимание!
                    
                    $time = $par_mass[$i]->time;
                    $comment = $par_mass[$i]->comment;
                    
                    for ($in = 0; $in < count($date_m) - $correct; $in++)
                    {
                        $d_m_c = explode(".", trim($date_m[$in]));                        
                        $d_m_c[0] = str_pad($d_m_c[0], 2, "0", STR_PAD_LEFT);
                        $d_m_c[1] = str_pad($d_m_c[1], 2, "0", STR_PAD_LEFT);
                        $date_to_write = $nay_year . "-" . $d_m_c[1] . "-" . $d_m_c[0];
                        if ( $d_m_c[0] === '00' || $d_m_c[1] === '00' ) $date_to_write = null;
						$el = $par_mass[$i];
                        unset($par_mass[$i]->comment);
						$debug = implode('&bull; | &bull;', (array)($par_mass[$i]));
                        $debug .= "&bull;";
                        
                        $res = $dbh->prepare("INSERT INTO timetable_stage (id_discipline,id_group,id_lecturer,id_room,`time`,`date`,`type`,`comment`, `debug`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $row = $res->execute(array($predmet_id, $group_id, $prepod_id, $auditoria_id, $time, $date_to_write, $type_sabjeckt, $comment, $debug));

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