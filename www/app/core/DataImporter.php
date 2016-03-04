<?php

class DataImporter extends Handler implements IStatus
{
    protected function mb_split_string($str)
    {
        $split = array();
        $len = mb_strlen($str, "UTF-8");                                          
        for ($i = 0; $i < $len; $i++) {
            $split[] = mb_substr($str, $i, 1, "UTF-8");
        }
        return $split;
    }
    
    /**
     * Returns array of matches in same format as preg_match or preg_match_all
     * @param bool   $matchAll If true, execute preg_match_all, otherwise preg_match
     * @param string $pattern  The pattern to search for, as a string.
     * @param string $subject  The input string.
     * @param int    $offset   The place from which to start the search (in bytes).
     * @return array
     */
    protected function pregMatchCapture($matchAll, $pattern, $subject, $offset = 0)
    {
        $matchInfo = array();
        $method    = 'preg_match';
        $flag      = PREG_OFFSET_CAPTURE;
        if ($matchAll) {
            $method .= '_all';
        }
        $n = $method($pattern, $subject, $matchInfo, $flag, $offset);
        $result = array();
        if ( $n !== 0 && !empty($matchInfo) ) {
            if (!$matchAll) {
                $matchInfo = array($matchInfo);
            }
            foreach ($matchInfo as $matches) {
                $positions = array();
                foreach ($matches as $match) {
                    $matchedText   = $match[0];
                    $matchedLength = $match[1];
                    $positions[]   = array(
                        $matchedText,
                        mb_strlen(mb_strcut($subject, 0, $matchedLength))
                    );
                }
                $result[] = $positions;
            }
            if (!$matchAll) {
                $result = $result[0];
            }
        }
        return $result;
    }
    
    public function import($storage, $DisciplineMatcher)//Запись в базу данных массива
    {
        try
        {
            $dbh = new PDO("mysql:host=localhost;dbname=tms", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $dbh->exec("set names utf8");
            $dbh->exec("truncate table timetable_stage");

            for ( $s = 0; $s < count($storage); $s++ )
            {
                list ( $type, $par_mass ) = array_values($storage[$s]);
                

                    $positive = 0;
                    $negative = 0;
                    $insert = 0;

                    $logfile = $_SERVER['DOCUMENT_ROOT'] . '/punctuation.log';
                    if ( file_exists($logfile) ) unlink($logfile);      

                    for ( $i = 0; $i < count($par_mass); $i++ )
                    {
                        foreach ( $par_mass[$i] as &$m ) $m = trim($m);
                        
                        $group_id = null;
                        if ( !empty($par_mass[$i]->group) )
                        {
                            $res = $dbh->query("SELECT id, name FROM groups WHERE name = '" . $par_mass[$i]->group . "'");

                            if ($row = $res->fetch(PDO::FETCH_ASSOC))
                                $group_id = $row['id'];
                            else
                            {
                                preg_match('/\d/', $par_mass[$i]->group, $mach);

                                $form_stady = "FULLTIME"; // TODO: substitute with the value from $type
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
                                    throw new Exception('Не удалось получить идентификатор группы &laquo;' . $par_mass[$i]->group . '&raquo;');
                            }
                        }

                        // если в поле lecturer пустая строка (то есть, парсер не распознал паттерн преподавателя)
                        // записываем в базу "0", который значит, что преподаватель не указан (его может не быть намеренно)
                        // в противном - случае мы либо находим его в справочнике, либо записываем в базу NULL
                        
                        if ( empty($par_mass[$i]->lecturer) ) $prepod_id = 0;
                        else
                        {
                            $prepod_id = null;
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

                        
                        $predmet_id = null;
                        if ( !empty($par_mass[$i]->discipline) )
                        {
                            
                            $par_mass[$i]->discipline = trim($par_mass[$i]->discipline);
                            //$par_mass[$i]->discipline = str_replace('/', '.', $par_mass[$i]->discipline);

        // 					preg_match("/\S/ui", $par_mass[$i]->discipline, $mc);

        //                     if (count($mc) > 0)
        //                     {
        //                     // сначала поищем в таблице сокращений
        //                     
                                
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
                                if ( is_null($predmet_id) )
                                {
                                    $mc = $par_mass[$i]->discipline;

                                    // устраняем дублирование пунктуации
                                    $mc = preg_replace('/(\s|\.|-)(?=\1)/u', '', $mc);

                                    // корректируем типографику
                                    $mc = preg_replace('/(\.|\.?,)(?![\s-,)]|$)/u', '$1 ', $mc);
                                    
                                    // отбиваем скобку пробелом
                                    $mc = preg_replace('/(?<!\s)\(/u', ' (', $mc);

                                    // вставляем подстановочный знак % вместо точек, дефисов и слэшей
                                    $mc = preg_replace('/[\.\/-]/u', '%', $mc);

                                    // разбиваем аббервиатуры
                                    $mc = preg_replace('/([A-ZА-Я])(?=(?1)|[,\s)]|$)/u', '$1%', $mc);

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
                                        
                                        
                                        $mc_orig = $mc;
                                        // упрощаем шаблон поиска дисциплины: убираем всю пунктуацию и однобуквенные предлоги
                                        $mc = preg_replace('/([,.]| [а-я])(?= )/u', '', $mc);

                                        $mc_prev = $mc;
                                        // упрощаем шаблон поиска дисциплины: обрезаем все слова до трёх символов
                                        // а также добавляем символ % к последнему слову, если его там не было 
                                        $mc = preg_replace('/(?|([А-Яа-я][а-я]{2})(?:[а-я]+%|[а-я]*(?= |$))|([А-Яа-я][а-я]+)(?:%[а-я]{1,3}(?= |$)))/u', '$1%', $mc);
                                        
                                        $n_spaces = mb_substr_count($mc, ' ');
                                        $avg_word_len = (mb_strlen($mc) - $n_spaces) / ($n_spaces + 1);
                                        
                                        if ( $avg_word_len > 3 )
                                        {
                                            // избавляемся от случайной подмены кириллических букв визуально схожими
                                            // латинскими: [ABCEHKMOPTX] в начале слова и [aceknopruxy]

                                            $pattern = '/(?(DEFINE)(?<ch>[aceknopruxy]))([ABCEHKMOPTX](?=[А-я])|(?<=[а-я])(?&ch)|(?&ch)(?=[а-я])|(?<![a-z]{2})(?&ch)(?=[,\.% ]|$))/u';
                                            $illegal_letters = $this->pregMatchCapture(true, $pattern, $mc);
                                            if ( !empty($illegal_letters) ) // заменяем все вхождения по таблице
                                            {
                                                // из-за плохой поддержки Юникода в PHP приходится городить
                                                // такие костыли для замены символов в строке
                                                $illegal_chars  = 'ABCEHKMOPTXaceknopruxy';
                                                $subst_chars = $this->mb_split_string('АВСЕНКМОРТХасекпоргиху');
                                                $mc = $this->mb_split_string($mc);
                                                foreach ( $illegal_letters[0] as $letter ) {
                                                    list ( $ch, $pos ) = $letter;
                                                    $k = strpos($illegal_chars, $ch);
                                                    $mc[$pos] = $subst_chars[$k];
                                                }
                                                $mc_restored = '';
                                                foreach ( $mc as $ch ) $mc_restored .= $ch;
                                                $mc = $mc_restored;
                                                //throw new DebugException('substituted', array($illegal_letters, $mc));
                                            }
                                        }
                                        /*
                                        $excluded_list = array(
                                            'Спе% арх% про% пр% сис%',
                                            'Пак% при% инж% про%',
                                            'Мет% ста% сер%',
                                            'Дет% маш% осн% кон%',
                                            'Спр%%пра% сис%',
                                            'Мет% ср%ва изм% кон%',
                                            'Выч% маш% сис% сет%',
                                            'Опт% ада% сис%',
                                            'Кон% рас%',
                                            'Мет% ср% изм% кон%',
                                            'Про% авт% пр%',
                                            'Про% маш% пр%',
                                            'Авт% фир% Обс%',
                                            'Эко% обо% нау% реш%',
                                            'Рас% мод% кон% при% К%Т%',
                                            'Тех% тр% тр% ср% Тео% авт%',
                                            'Ста% Ч%П%У% авт% лин%',
                                            'Тео% осн% авт% упр%',
                                            'Эко% ста% сер% кач%',
                                            'Сис% авт% про%',
                                            'Сис% иск% инт%',
                                            'Мет% фин% рас%',
                                            'Кон% рас% эл% обо%',
                                            'Инт% сис% про%',
                                            'Эко% ста% сер% У%К%',
                                            'Авт% упр% Ж%Ц%П%',
                                            'Тех% изг% изд% на осн% эла%'
                                        );
                                        if ( !in_array($mc, $excluded_list) )
                                            throw new Exception($par_mass[$i]->discipline . '<br>' . $mc_orig . '<br>' . $mc_prev . '<br>' . $mc);
                                        */
                                        $query = "SELECT id, name FROM disciplines WHERE name LIKE '" . $mc . "' ORDER BY CHAR_LENGTH(name) ASC";
                                        $res = $dbh->query($query);                 
                                        $data = $res->fetch(PDO::FETCH_ASSOC);

                                        if ( $data !== FALSE )
                                        {
                                            $predmet_id = $data['id'];
                                        }                                        
                                        else
                                        {
                                            throw new Exception($par_mass[$i]->discipline . ' was failed to match');
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
                                }
                            }
                        

                        
                        $special_list = array(631, 717); // Физическая культура
                        if ( in_array($predmet_id, $special_list) )
                        {
                            if ( empty($par_mass[$i]->room) )
                                $par_mass[$i]->room = 'СК';
                            
                            if ( empty($par_mass[$i]->type) )
                                $par_mass[$i]->type = 'пр';
                        }

                        
                        // если в поле room пустая строка (то есть, парсер не распознал паттерн аудитории)
                        // записываем в базу "0", который значит, что аудитория не указана (её может не быть намеренно)
                        // в противном - случае мы либо находим её в словаре, либо записываем в базу NULL
                        if ( empty($par_mass[$i]->room) ) $auditoria_id = 0;
                        else
                        {
                            $auditoria_id = null;
                            $res = $dbh->query("SELECT id, name FROM rooms WHERE name='" . $par_mass[$i]->room . "'");

                            if ($row = $res->fetch(PDO::FETCH_ASSOC))
                                $auditoria_id = $row['id'];
                        }

                        if ( empty($par_mass[$i]->dates) ) throw new Exception("Не указана дата занятия " . var_export($par_mass[$i], true));
                        {
                            $date_m = explode(",", $par_mass[$i]->dates);
                            $correct = 0;
                            if (trim($date_m[count($date_m) - 1]) == "")
                                $correct = 1;

                            //$par_mass[$i]->type = str_replace(".", "", $par_mass[$i]->type);
                            ///* Установка внутренней кодировки в UTF-8 */
                            //mb_internal_encoding("UTF-8");
                            $par_mass[$i]->type = rtrim($par_mass[$i]->type, '.');
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
                                case "зач":
                                case "зачет":   $type_sabjeckt = "QUIZ";        break;
                                case "экз":
                                case "экзамен": $type_sabjeckt = "EXAMINATION"; break;
                                default:        $type_sabjeckt = "WORKSHOP";     break;
                            }                    

                            $nay_year = date('Y'); // обратить внимание!

                            $time = $par_mass[$i]->time;
                            if ( empty($time) ) $time = null;
                            
                            $comment = $par_mass[$i]->comment;
                            if ( empty($comment) ) $comment = null;
                            
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
                    $this->setStatus("OK", "Массив данных успешно загружен в базу данных", "Добавлено $insert записей");
                }
        }
        catch(PDOException $exc)
        {
            $dbh = null;
            $this->setStatus("Error", "On line:" . $exc->getLine() . " --- " . $exc->getMessage());
            return false;
        }
        $dbh = null;
        return true;
    }
}