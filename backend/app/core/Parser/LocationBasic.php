<?php

class LocationBasic extends TableHandler
{    
    protected $height;              // высота (в ячейках)
    protected $width;               // ширина (в ячейках)
    protected $innerBorderPosition; // смещение в столбцах до внутренней границы
    
    public function __construct()
    {
       
    }
    
    
    public function collect($sheet, &$calendar, $cx, $rx, $groups, $groupWidth, $gid, $lastColumn, $lastRow)
    {
        $this->inspect($sheet, $cx, $rx, $lastColumn, $lastRow);
        $width = $this->width;
        $height = $this->height;
        $innerBorderPosition = $this->innerBorderPosition;
        
        // устанавливаем высоту занятия в строках
        if ( empty($calendar->meetingHeight) ) {
            if ( $height == 3 ) $calendar->meetingHeight = 3;
            else if ( $height == 2 || $height == 4 ) $calendar->meetingHeight = 2;
            else throw new Exception("Не удаётся достоверно установить высоту одного занятия по высоте текущей ячейки: $height");
        }
        
        $retrieverAlgorithm = $innerBorderPosition ? 'retrieveMeetingsSplit' : 'retrieveMeeting';
        $meetings = $this->$retrieverAlgorithm($sheet, $calendar, $cx, $width, $rx, $height, $innerBorderPosition);

        if ( empty($meetings[0]->discipline) )
            return false;
        
        // количество групп, задействованных в занятии
        $groupsCount = $this->getGroupsCount($this->width, $groupWidth);
        
        // количество занятий
        $meetingsCount = $this->getMeetingsCount($this->height, $calendar->meetingHeight);

        $chunk = $this->pack($groupsCount, $groups, $gid, $calendar, $rx, $meetings, $meetingsCount);
        
        return $chunk;
        
    }
    
    // === Замерить локацию (клетку с описанием занятия)
    // ищем границы локации: упираемся в правую и находим ширину, затем в нижнюю и находим высоту
    // пока ищем высоту, выискиваем "дырки" в правой границе
    // если обнаружена "дырка" - ныряем до упора: граница типа B
    // если нет - проходимся по строкам локации в поисках внутренней границы типа А
    // факт существования границы говорит о том, что у локации нарушена целостность
    
    protected function inspect($sheet, $cx, $rx, $lastColumn, $lastRow)
    {   
        $row = $rx; // начальная строка
        $col = $cx; // начальный столбец
        
        // находим правую границу локации
        $i = 0; // счётчик цикла while 
        while ( ! $this->hasRightBorder($sheet, $col, $rx) && $col < $lastColumn)
        {
            if ( $i > 0 )
            {
                $cell = $sheet->getCellByColumnAndRow($col, $rx);
                $isBold = $cell->getStyle()->getFont()->getBold() == 1;
                $value = $cell->getValue();
                $isEmpty = trim($value) == false;
                if ( $isBold && !$isEmpty ) // скорее всего, опущена граница между двумя занятиями
                {
                    $col--;
                    $styleArray = array(
                        'borders' => array(
                            'right' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THICK,
                                'color' => array('argb' => 'FF000000'),
                            ),
                        ),
                    );
                    $coord = $sheet->getCellByColumnAndRow($col, $rx)->getCoordinate();
                    $sheet->getStyle($coord)->applyFromArray($styleArray);
                    $this->width = $col - $cx + 1;
                    
                    // тут нужно сразу найти нижнюю границу, без проверки правой
                    $row++;
                    while ( ! $this->hasBottomBorder($sheet, $col, $row - 1) && $row <= $lastRow ) {
                        $row++;
                    }
                    $this->height = $row - $rx;                    
                    return;
                }
            }
            $col++;
            $i++;
        }
        $this->width = $col - $cx + 1;
        // ищем нижнюю границу локации начиная со второй строки
        $row++;
        while ( ! $this->hasBottomBorder($sheet, $col, $row - 1) && $row <= $lastRow ) { // хитрое условие: пока нет нижней границы предыдущей ячейки
            // в это же время поглядываем на правую границу
            if ( ! $this->hasRightBorder($sheet, $col, $row) ) {
                // если справа "дырка", то фиксируем это в протокол,
                // смещаемся до правой границы, корректируем ширину и падаем на дно 
                $this->innerBorderPosition = $col - $cx + 1;     
                while ( ! $this->hasRightBorder($sheet, $col, $row) && $col < $lastColumn ) $col++;
                $this->width = $col - $cx + 1;
                while ( ! $this->hasBottomBorder($sheet, $col, $row) && $row < $lastRow ) $row++;
                $this->height = $row - $rx; 
            }     
            $row++;
        }  
        $this->height = $row - $rx;
        
        // определяемся c внутренней границей
        if ( ! empty($this->innerBorderPosition) ) return;
        if ( $this->height === 1 ) {
            $row--;
            throw new Exception("Некорректная локация близ ячейки C$col:R$row [высота меньше единицы] (лист &laquo;{$sheet->getTitle()}&raquo;)");
        } 
        
        // ищем внутренние границы
        for ( $r = $rx + 1; $r < $row; $r++ ) {
            for ( $c = $cx; $c < $col; $c++ ) {                
                if ( $this->hasRightBorder($sheet, $c, $r) ) {
                    $this->innerBorderPosition = $c - $cx + 1;
                    return;
                }
            }
        }
        
        // в расписании консультаций заочников встречаются локации для потока групп,
        // разделённые по диагонали, означающей разные занятия для данного потока по датам;
        // в данном случае ищем две ячейки с полужирным текстом;
        // эти ячейки должны находиться в разных столбцах и строках
          
        if ( $this->width === 4 )
        {
            for ( $c = $cx + floor($this->width / 2); $c <= $cx + $this->width - 1; $c++ ) {        
                for ( $r = $rx + 1; $r <= $rx + $this->height - 1; $r++ )    
                {
                    // если текст помечен жирным, то это ещё одно название дисциплины
                    $cell = $sheet->getCellByColumnAndRow($c, $r);
                    if ( false != trim($cell) ) {
                        $isBold = ( $cell->getStyle()->getFont()->getBold() == 1 ) ? true : false;
                        if ( $isBold )
                        {
                            $this->innerBorderPosition = 2;
                            return;
                        }
                    }
                }
            }
        }
    }
    
    
    
    private function retrieveMeeting($sheet, $calendar, $c, $w, $r, $h, $innerBorderPosition = null)
    {
        $res = $this->extract($sheet, $c, $w, $r, $h);
        $this->setDates($res, $calendar, $r);
        $meetings[] = new Meeting($res);
        
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
        if ( $areEqual )
        {
            foreach ( $basis as $el )
                if ( empty($res1[$el]) ) $res1[$el] = $res2[$el];
            $res1['comment'] = trim($res1['comment'] . ' ' . $res2['comment']);            
            if ( isset($res2['dateTill']) ) $res1['dateTill'] = $res2['dateTill'];
            $this->setDates($res1, $calendar, $r);
            $meetings[] = new Meeting($res1);
        }
        else
        {
            foreach ( array($res1, $res2) as $res )
                $this->setDates($res, $calendar, $r);
            $meetings[] = new Meeting($res1);
            $meetings[] = new Meeting($res2);
            $this->crossFillItems($meetings[0], $meetings[1]);
        }
        
        return $meetings;
    }
    
    // === Установить даты шаблона встречи
    // если даты не были эксплицитно указаны, то они импортируются из календаря
    // если задана дата конца встреч, список дат корректируется с учётом этого ограничения
    protected function setDates(&$res, $calendar, $r)
    {
        if ( empty($res['dates']) )
        {
            $res['dates'] = $calendar->getDatesByRow($r);
            if ( !empty($res['dateTill']) ) $this->truncDates($res);
        }
    }
    
    
    // === Обрезать строку дат, если есть ограничение по дате
    protected function truncDates(&$res)
    {
        $pos = mb_strpos($res['dates'], $res['dateTill']);
        $len = mb_strlen($res['dateTill']);
        $res['dates'] = mb_substr($res['dates'], 0, $pos + $len);
        unset($res['dateTill']);
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
                    
                    // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)
                    // важно выполнять этот поиск в самом начале, чтобы избавиться от значений времени,
                    // вписываемых в название дисциплины

                    /*
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= ' ' . $matches[0];
                            $str = str_replace($matches[0], '', $str);                           
                        }
                        */

                    if ( preg_match('/(?:[СCсc]\s*)?([012]?\d)[.:]([0-5]0)(?:\s*-\s*(?1)[.:](?2))?/u', $str, $matches) )
                    {
                        $result['time'] = $matches[1] . ':' . $matches[2];
                        $result['comment'] .= ' ' . $matches[0];
                        $str = str_replace($matches[0], '', $str);
                    }
                    
                    // поиск эксплицитных дат
                    
                    //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                    //if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                    if ( preg_match('/(?<![CcСс]\s)(?<!\d|-\s|-)(?:(до)\s?)?((?:(?:[12]?\d|30|31)\.(?:0\d|1[0-2])(?:\s?(?=,),\s*)?(?!\s-|-))+)/u', $str, $matches) )
                    {   
                        if ( !empty($matches[1]) ) $result['dateTill'] = $matches[2]; // если встретили предлог "до" перед датой
                        else $result['dates'] = preg_replace('/\s/u', '', $matches[0]);                      
                        $str = str_replace($matches[0], '', $str);
                    }
                    
                    /*
                        // вырезаем подстроку с датами из комментария
                        $posFrom = $mc[0][1];
                        $posTo = $posFrom + mb_strlen($mc[0][0]);
                        $data['comment'] = mb_substr($data['comment'], 0, $posFrom) . mb_substr($data['comment'], $posTo);
                        // вырезаем все пробелы из строки с датами                                    
                        $data['dates'] = preg_replace('/\s/u', '', $mc[0][0]);
                    */
                    
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

                    // поиск аудитории                        
                    if ( preg_match("/((?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|гараж\s№3)(?:\s|$|,)/u", $str, $matches) )
                    {   
                        $result['room'] = $matches[1];
                        $str = str_replace($matches[0], '', $str);
                    }

                    // поиск преподавателя                        
                    if ( preg_match('/(?<!,)[А-Я][а-яё]+(?= |,|\(|$)(?:\s*[А-Я]\.){0,2}(?= |,|\(|$)/u', $str, $matches) )
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
    
    
    protected function getMeetingsCount($height, $meetingHeight)
    {
        return floor($height / $meetingHeight);
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