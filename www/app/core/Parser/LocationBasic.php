<?php

class LocationBasic extends TableHandler
{    
    protected $height;              // высота (в ячейках)
    protected $width;               // ширина (в ячейках)
    protected $innerBorderPosition; // смещение в столбцах до внутренней границы
    
    public function __construct()
    {
       
    }
    
    
    public function collect($sheet, &$calendar, $cx, $rx, $groups, $groupWidth, $gid)
    {
        $this->inspect($sheet, $cx, $rx);
        $width = $this->width;
        $height = $this->height;
        $innerBorderPosition = $this->innerBorderPosition;
        
        $retrieverAlgorithm = $innerBorderPosition ? 'retrieveMeetingsSplit' : 'retrieveMeeting';
        $meetings = $this->$retrieverAlgorithm($sheet, $calendar, $cx, $width, $rx, $height, $innerBorderPosition);
        
//         if ( $cx === 11 && $rx === 16 && $meetings[0]->dates == '3.03,31.03,28.04,26.05' ) throw new Exception(var_export(array($meetings, $width, $height, $innerBorderPosition, $cx, $rx)));
        
//         if ( $cx === 11 && $rx === 46 && $meetings[0]->dates == '6.04,4.05' ) throw new Exception(var_export(array($meetings, $width, $height, $innerBorderPosition, $cx, $rx, $groups)));
        
        
        
        if ( empty($meetings[0]->discipline) )
            return false;
        
        // количество групп, задействованных в занятии
        $groupsCount = $this->getGroupsCount($this->width, $groupWidth);
        
        // количество занятий
        $meetingsCount = $this->getMeetingsCount($this->height);

        $chunk = $this->pack($groupsCount, $groups, $gid, $calendar, $rx, $meetings, $meetingsCount);
        
        return $chunk;
        
    }
    
    // === Замерить локацию (клетку с описанием занятия)
    // ищем границы локации: упираемся в правую и находим ширину, затем в нижнюю и находим высоту
    // пока ищем высоту, выискиваем "дырки" в правой границе
    // если обнаружена "дырка" - ныряем до упора: граница типа B
    // если нет - проходимся по строкам локации в поисках внутренней границы типа А
    // факт существования границы говорит о том, что у локации нарушена целостность
    
    protected function inspect($sheet, $cx, $rx)
    {   
        $row = $rx; // начальная строка
        $col = $cx; // начальный столбец
        
        // находим правую границу локации        
        while ( ! $this->hasRightBorder($sheet, $col, $rx) ) $col++;
        $this->width = $col - $cx + 1;
        // ищем нижнюю границу локации начиная со второй строки
        $row++;
        while ( ! $this->hasBottomBorder($sheet, $col, $row - 1) ) { // хитрое условие: пока нет нижней границы предыдущей ячейки
            // в это же время поглядываем на правую границу
            if ( ! $this->hasRightBorder($sheet, $col, $row) ) {
                // если справа "дырка", то фиксируем это в протокол,
                // смещаемся до правой границы, корректируем ширину и падаем на дно 
                $this->innerBorderPosition = $col - $cx + 1;     
                while ( ! $this->hasRightBorder($sheet, $col, $row) ) $col++;
                $this->width = $col - $cx + 1;
                while ( ! $this->hasBottomBorder($sheet, $col, $row) ) $row++;             
                $this->height = $row - $rx; 
            }     
            $row++;
        }  
        $this->height = $row - $rx;
        
        if ( ! empty($this->innerBorderPosition) ) return;
        if ( $this->height === 1 ) {
            $row--;
            throw new Exception("Некорректная локация близ ячейки C$col:R$row [высота меньше единицы] ({$sheet->getTitle()})");
        } 
        
        // ищем внутренние границы
        for ( $r = $rx + 1; $r < $row; $r++ ) {
            for ( $c = $cx; $c < $col; $c++ ) {                
                if ( $this->hasRightBorder($sheet, $c, $r) ) {
                    $this->innerBorderPosition = $c - $cx + 1;
//                     if ( $cx === 11 && $rx === 46 && get_class($this) === 'LocationSingle' ) throw new Exception(var_export(array($rx, $r, $row, $cx, $c, $col, $this->innerBorderPosition))); 
                    //if ( $cx === 11 && $rx === 46 ) throw new Exception(var_export(array($this->innerBorderPosition)));
                    return;
                }
            }
        }
    }
    
    
    
    private function retrieveMeeting($sheet, $calendar, $c, $w, $r, $h, $innerBorderPosition = null)
    {
        $res = $this->extract($sheet, $c, $w, $r, $h);
        if ( empty($res['dates']) )
            $res['dates'] = $calendar->getDatesByRow($r);
        $meetings[] = new Meeting();
        $meetings[0]->initFromArray($res);
        return $meetings;
    }
    
    
    private function retrieveMeetingsSplit($sheet, $calendar, $c, $w, $r, $h, $innerBorderPosition)
    {
        $w1 = $innerBorderPosition;
        $w2 = $w - $w1;
        $res1 = $this->extract($sheet, $c, $w1, $r, $h);
        $res2 = $this->extract($sheet, $c + $w1, $w2, $r, $h);
        $basis = array('discipline', 'type', 'room', 'lecturer');
        $areEqual = true;
        foreach ( $basis as $el )
            $areEqual &= empty($res1[$el]) ^ empty($res2[$el]);
        if ( $areEqual ) {
            foreach ( $basis as $el )
                if ( empty($res1[$el]) ) $res1[$el] = $res2[$el];
                $res1['comment'] = trim($res1['comment'] . ' ' . $res2['comment']);
            if ( empty($res1['dates']) )
                $res1['dates'] = $calendar->getDatesByRow($r);
            $meetings[] = new Meeting();
            $meetings[0]->initFromArray($res1);
        }
        else {
            foreach ( array($res1, $res2) as $res )
                if ( empty($res['dates']) )
                $res['dates'] = $calendar->getDatesByRow($r);
                $meetings[] = new Meeting();
            $meetings[] = new Meeting();
            $meetings[0]->initFromArray($res1);
            $meetings[1]->initFromArray($res2);
            $this->crossFillItems($meetings[0], $meetings[1]);
        }
        return $meetings;
    }
    
    
    
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
                        if ( preg_match('/((?:[А-яA-Z]+[\s.,\/-]{0,3})+)(?:\((?1)\))?/u', $str, $matches) !== false )
                        {   
                            // конкатенация для тех случаев, когда название дисциплины
                            // продолжается в следующей ячейке
                            $result['discipline'] .= $matches[0] . ' ';
                            $str = str_replace($matches[0], '', $str);                                   
                        }                        
                    }
                    
                    // поиск типа занятия                        
                    if ( preg_match("/(?:^|\s)((?:лаб|лек|пр|зач(?:ет)?|экз(?:амен)?)\s*\.?)/u", $str, $matches) )
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
                    if ( preg_match('/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u', $str, $matches) )
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
                        $m->time = $calendar->lookupTimeByRow($i + $z * 2);
                    else {
                        if ( $z > 0 || empty($meeting->time) ) {
                            $gridOffset = $calendar->lookupOffsetByRow($i + $z * 2);
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
    
    
    // === Взаимодополнить поля двух занятий
   
    protected function crossFillItems($m1, $m2) {
        $basis = array('discipline', 'type', 'room', 'lecturer', 'dates', 'time');
        foreach ( $basis as $el ) {
            if ( empty($m1->$el) ) $m1->$el = $m2->$el;
            else if ( empty($m2->$el) ) $m2->$el = $m1->$el;
        }
        $comment = trim($m1->comment . ' ' . $m2->comment);
        $m1->comment = $comment;
        $m2->comment = $comment;      
    }
    
    
    protected function getMeetingsCount($height)
    {
        return floor($height / 2);   
    }
    
    
    protected function getGroupsCount($width, $groupWidth)
    {
        $groupsCount = ceil($width / $groupWidth);
        if ( ! $groupsCount ) throw new Exception('Ни одной группы в локации');
        return $groupsCount;
    }
    
    public function getWidth()
    {
        return $this->width;
    }
    
}