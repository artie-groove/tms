<?php

class LocationSecondary extends LocationBasic
{   
    // === Прочесать локацию
    // извлекает данные из локации и возвращает массивом 
    
    protected function extract($sheet, $cx, $w, $rx, $h)
    {   
        $row = $rx;     // начальная строка
        $col = $cx;     // начальный столбец
        $width = 0;
        
        // массив результатов
        $result = array(
            'discipline' => '',
            'type' => '',
            'room' => '',
            'lecturer' => '',
            'dates' => '',
            'time' => '',
            'comment' => ''
        );
        
        for ( $r = $rx; $r < $rx + $h; $r++ )
        {
            for ( $c = $cx; $c < $cx + $w; $c++ )
            {
                $str = trim($sheet->getCellByColumnAndRow($c, $r));
                if ( ! empty($str) )
                {
                    $matches = array();
                    
                    // если текст помечен жирным, то это название дисциплины
                    if ( $sheet->getCellByColumnAndRow($c, $r)->getStyle()->getFont()->getBold() == 1 )
                    {
                        // кроме названия дисциплины в строке могут находиться и другие сведения
                        if ( preg_match('/((?:[А-яA-z]+[\s.,\/-]{0,3})+)?(?:\((?1)\))?/u', $str, $matches) !== false )
                        {   
                            //if ( stristr($str, 'Теория автом.') !== false ) throw new Exception("<pre>$str</pre>");
                            // конкатенация для тех случаев, когда название дисциплины
                            // продолжается в следующей ячейке
                            $result['discipline'] .= $matches[0] . ' ';
                            $str = str_replace($matches[0], '', $str);                                   
                        }                        
                    }
                    
                    // поиск типа занятия                        
                    if ( preg_match("/(?:^|\s)((?:лаб|лек|пр|конс|зач(?:ет)?|экз(?:амен)?)\s*\.?)/u", $str, $matches) )
                    {
                        $result['type'] = $matches[1];
                        $str = str_replace($matches[1], '', $str);
                    }

                    // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)

                    /*
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= ' ' . $matches[0];
                            $str = str_replace($matches[0], '', $str);                           
                        }
                        */

                    if ( preg_match('/(?:[СCсc]\s*)?([012]?\d:[0-5]0)(?:\s*-\s*(?-1))?/u', $str, $matches) )
                    {
                        $result['time'] = $matches[1];
                        $result['comment'] .= ' ' . $matches[0];
                        $str = str_replace($matches[0], '', $str);
                        //if ( $matches[1] == "11:50" ) throw new Exception($matches[0] . '/' . $str);
                    }

                    // поиск аудитории                        
                    if ( preg_match("/((?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|гараж\s№3)(?:\s|$|,)/u", $str, $matches) )
                    {   
                        $result['room'] = $matches[1];
                        $str = str_replace($matches[0], '', $str);
                    }

                    // поиск эксплицитных дат
                    //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                    if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                    {   
                        $result['dates'] = preg_replace('/\s/u', '', $matches[0]);
                        $str = str_replace($matches[0], "", $str);
                    }
                    
                    /*
                        // вырезаем подстроку с датами из комментария
                        $posFrom = $mc[0][1];
                        $posTo = $posFrom + mb_strlen($mc[0][0]);
                        $data['comment'] = mb_substr($data['comment'], 0, $posFrom) . mb_substr($data['comment'], $posTo);
                        // вырезаем все пробелы из строки с датами                                    
                        $data['dates'] = preg_replace('/\s/u', '', $mc[0][0]);
                    */

                    // поиск преподавателя                        
                    if ( preg_match('/[А-Я][а-яё]+(?:\s*[А-Я]\.){0,2}/u', $str, $matches) )
                    {                            
                        $result['lecturer'] = $matches[0];
                        $str= str_replace($matches[0], '', $str);
                    }

                    // оставшийся кусок строки цепляем к комментарию
                    $str = trim($str);
                    if ( !empty($str) ) $result['comment'] .= $str . " ";                    
                }
            }        
        }
        $result['comment'] = trim($result['comment']);
        $result['discipline'] = trim($result['discipline']);
        return $result;
    }
    
    
    private function pack($groupsCount, $groups, $gid, &$calendar, $i, $meetings, $meetingsCount)
    {
        $chunk = array(); // порция встреч
        
        // обнаруживаем эксплицитное время и фиксируем его в регистре
        foreach ( $meetings as $meeting ) {
            if ( !empty($meeting->time) ) {
                $shift = $calendar->convertTimeToOffset($meeting->time);                          
                for ( $g = 0; $g < $groupsCount; $g++ )
                    $calendar->timeshift->set($gid + $g, $shift);
                break;
            }
        }
        
        // сохраняем базовое смещение времени для всех групп
        // это нужно, когда встречается деление по подгруппам
        // потому что в этом случае каждое занятие подгруппы инкрементирует $timeshift[$g]
//         for ( $g = 0; $g < $groupsCount; $g++ )
//             $calendar->timeshift->backup();
            //$basetimeshift[$gid + $g] = $timeshift[$gid + $g];

        $calendar->timeshift->backup();
        
        foreach ( $meetings as $meeting ) {
            // множим занятия (по группам и по академическим часам)
            for ( $g = $gid; $g < $groupsCount + $gid; $g++ ) {
                // восстанавливаем базовое смещение в начале каждого цикла
                $calendar->timeshift->restore($g);
                //$timeshift[$g] = $basetimeshift[$g]; 
                for ( $z = 0; $z < $meetingsCount; $z++ ) {
                    $m = new Meeting();
                    $m->copyFrom($meeting);
                    $shift = $calendar->timeshift->get($g);
                    if ( empty($shift) )
                        $m->time = $calendar->lookupTimeByRow($i + $z * $calendar->meetingHeight);
                    else {
                        if ( $z > 0 || empty($meeting->time) ) {
                            $gridOffset = $calendar->lookupOffsetByRow($i + $z * $calendar->meetingHeight);
                            if ( $gridOffset >= $shift ) { // всё ок, идём по сетке
                                $m->time = $calendar->convertOffsetToTime($gridOffset);
                                 $calendar->timeshift->set($g, $gridOffset + 100);
                            }
                            else { // смещение подпирает, отталкиваемся от него и инкрементируем до сеточного значения
                                $m->time = $calendar->convertOffsetToTime($shift);
                                $calendar->timeshift->set($g, $shift + 100 - ($shift % 100));
                            }
                        }
                        else // для первой встречи просто инкрементируем значение в регистре
                            $calendar->timeshift->set($g, $shift + 100 - ($shift % 100));                         
                    }
                    $m->group = $groups[$g];
                    $chunk[] = $m;
                }
            }
        }
        
        return $chunk;
    }
    
    protected function getGroupsCount($width, $groupWidth)
    {
        return 1;
    }   
}