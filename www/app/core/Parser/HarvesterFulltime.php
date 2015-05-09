<?php

/*
 *  Реализует сборщик дневного расписания 
 * 
 */

class HarvesterFulltime extends HarvesterBasic
{
    protected $calendarType = 'Basic';
    
    public function run()
    {
        $harvest = array();
        
        $sheet = $this->sheet;
        $rx = $this->firstRow;
        
        $params = $this->establishTableParams($sheet, $rx);
        list ( $width, $height, $datesMatrixFirstColumn, $datesMatrixWidth, $firstDataColumn, $groupWidth ) = array_values($params);
        $groups = $this->exploreGroups($sheet, $firstDataColumn, $width, $rx, $groupWidth);
        $timeshift = new Timeshift(count($groups));
        
        $calendarClass = 'Calendar' . $this->calendarType;
        $calendar = new $calendarClass($sheet, $datesMatrixFirstColumn, $datesMatrixWidth, $rx + 1, $height - 1, $timeshift);
        
        return $this->harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar);
    }
    
    
    // === Собрать данные с секции
        
    protected function harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar)
    {
        // проходим по дням недели
        // индекс первой строки $i инициализируется здесь на основании первой строки таблицы данных
        // здесь же он инкрементируется по таблице индексов $dayLimitRowIndexes в конце каждого цикла
        for ( $r = $rx + 1, $d = 0; $d < count($calendar->dayLimitRowIndexes); $r = $calendar->dayLimitRowIndexes[$d], $d++ )
        {
            $calendar->timeshift->reset();
            for (; $r < $calendar->dayLimitRowIndexes[$d]; $r++ )
            {
                for ( $c = $firstDataColumn; $c < $width; $c++ )
                {
                    $cellData = $sheet->getCellByColumnAndRow($c, $r);
                    if ( empty($cellData) ) continue;
                    
                    // эксплорим занятие (спускаемся в клетку) если под курсором локация
                    if ( $this->isLocationEntryPoint($sheet, $c, $r) )
                        $c = $this->develop($sheet, $cellData, $c, $r, $calendar, $firstDataColumn, $groupWidth, $groups, $harvest);
                    else // ищем указатели смещения времени
                        $this->obtainTimeMarker($cellData, $c, $calendar, $groupWidth, $firstDataColumn);
                }
            }
        }
        return $harvest;
    }
    
    
    // === Начало локации?
    // определяется по наличию левой и верхней границ у ячейки
    
    private function isLocationEntryPoint($sheet, $c, $r)
    {
        $bLeft = $this->hasRightBorder($sheet, $c - 1, $r);
        $bTop = $this->hasBottomBorder($sheet, $c, $r - 1);
        return $bLeft && $bTop;
    }


    // === Найти метку времени в содержимом ячейки

    private function obtainTimeMarker($cellData, $c, &$calendar, $groupWidth, $firstDataColumn)
    {
        if ( preg_match('/[СCсc]\s(1?\d:[0-5]0)/u', $cellData, $matches) ) {
            // индекс текущей группы в массиве Group
            $gid = $this->getGroupId($c, $groupWidth, $firstDataColumn);
            $shift = $calendar->convertTimeToOffset($matches[1]);
            if ( $calendar->timeshift->get($gid) < $shift ) {
                // фиксируем смещение в регистре, если оно больше уже установленного
                $calendar->timeshift->set($gid, $shift);
            }
        }
    }
    
    
    // === Исследовать участок под курсором  
    
    private function develop($sheet, $cellData, $k, $i, &$calendar, $firstDataColumn, $groupWidth, $groups, &$harvest)
    {   
        $layout = $this->inspectLocation($sheet, $k, $i);
        $knext = $k + $layout['width'] - 1; // новое значение указателя столбца (прибавляем ширину текущей локации)
        
        $retrieverAlgorithm = $layout['offset'] ? 'retrieveMeetingsSplit' : 'retrieveMeeting';        
        $meetings = $this->$retrieverAlgorithm($sheet, $calendar, $layout, $k, $i);
        
        if ( empty($meetings[0]->discipline) )
            return $knext;
        
        // индекс текущей группы в массиве Group
        $gid = $this->getGroupId($k, $groupWidth, $firstDataColumn);

        // количество групп, задействованных в занятии
        $groupsCount = $this->getGroupsCount($layout['width'], $groupWidth);
        
        // количество занятий
        $meetingsCount = $this->getMeetingsCount($layout['height']);

        $this->pack($groupsCount, $groups, $gid, $calendar, $i, $meetings, $meetingsCount, $harvest);
        
        return $knext;                
    }
    
    // === Найти порядковый номер текущей группы
    
    private function getGroupId($c, $groupWidth, $firstDataColumn)
    {
        return floor(($c - $firstDataColumn) / $groupWidth);
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
    
    private function retrieveMeeting($sheet, $calendar, $layout, $k, $i)
    {
        $res = $this->extractLocation($sheet, $k, $layout['width'], $i, $layout['height']);
        if ( empty($res['dates']) )
            $res['dates'] = $calendar->getDatesByRow($i);
        $meetings[] = new Meeting();
        $meetings[0]->initFromArray($res);
        return $meetings;
    }
    
    private function retrieveMeetingsSplit($sheet, $calendar, $layout, $k, $i)
    {
        $w1 = $layout['offset'];
        $w2 = $layout['width'] - $w1;
        $res1 = $this->extractLocation($sheet, $k, $w1, $i, $layout['height']);
        $res2 = $this->extractLocation($sheet, $k + $w1, $w2, $i, $layout['height']);
        $basis = array('discipline', 'type', 'room', 'lecturer');
        $areEqual = true;
        foreach ( $basis as $el )
            $areEqual &= empty($res1[$el]) ^ empty($res2[$el]);
        if ( $areEqual ) {
            foreach ( $basis as $el )
                if ( empty($res1[$el]) ) $res1[$el] = $res2[$el];
                $res1['comment'] = trim($res1['comment'] . ' ' . $res2['comment']);
            if ( empty($res1['dates']) )
                $res1['dates'] = $calendar->getDatesByRow($i);
            $meetings[] = new Meeting();
            $meetings[0]->initFromArray($res1);
        }
        else {
            foreach ( array($res1, $res2) as $res )
                if ( empty($res['dates']) )
                $res['dates'] = $calendar->getDatesByRow($i);
                $meetings[] = new Meeting();
            $meetings[] = new Meeting();
            $meetings[0]->initFromArray($res1);
            $meetings[1]->initFromArray($res2);
            $this->crossFillItems($meetings[0], $meetings[1]);
        }
        return $meetings;
    }
    
    private function pack($groupsCount, $groups, $gid, &$calendar, $i, $meetings, $meetingsCount, &$harvest)
    {
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
                    $harvest[] = $m;
                }
            }
        }
    }
    
    // === Определить основные параметры таблицы
    
    private function establishTableParams(&$sheet, $rx)
    {   
        list ( $w, $h ) = array_values($this->inspectTableGeometry($sheet, $rx));
        $valid = $this->validateTableBorders($sheet, $rx, $w, $h);
        if ( ! $valid ) throw new Exception('Нарушена целостность правой и/или нижней границ таблицы');
        $this->cleanupTable($sheet, $rx, $w, $h);
        
        // определяем ширину матрицы дат
        $cdm = 1; // dates matrix first column
        $dmw = $cdm; // dates matrix width
        while ( trim($sheet->getCellByColumnAndRow($dmw + 1, $rx)) !== 'Часы' ) $dmw++;
        
        if ( $dmw > 5 ) throw new Exception("Некорректное количество столбцов в календаре. Удалите все скрытые столбцы");
        
        $cd = $cdm + $dmw + 1; // first data column
        
        // рассчитываем ширину на группу по первой ячейке для группы
        $gw = 1;
        $c = $cd;
        while ( empty(trim($sheet->getCellByColumnAndRow($c + 1, $rx))) ) $c++;
        $gw = $c - $cd + 1;
        if ( ($w - $cd) % $gw !== 0 ) throw new Exception('Ширина групп должна быть равной');
               
        return array(
            'width' => $w,
            'height' => $h,
            'datesMatrixFirstColumn' => $cdm,
            'datesMatrixWidth' => $dmw,
            'firstDataColumn' => $cd,
            'groupWidth' => $gw
        );
    }
    
    
    
}