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
        
        $calendarClass = 'Calendar' . $this->calendarType;
        $calendar = new $calendarClass($sheet, $datesMatrixFirstColumn, $datesMatrixWidth, $rx + 1, $height - 1);
        
        return $this->harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar);
    }
    
    protected function harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar)
    {
        // проходим по дням недели
        // индекс первой строки $i инициализируется здесь на основании первой строки таблицы данных
        // здесь же он инкрементируется по таблице индексов $dayLimitRowIndexes в конце каждого цикла
        for ( $i = $rx + 1, $d = 0; $d < count($calendar->dayLimitRowIndexes); $i = $calendar->dayLimitRowIndexes[$d], $d++ )
        {
            // регистр эксплицитных сеточных и внесеточных смещений времени
            $timeshift = array_fill(0, count($groups), 0); // сбрасывается в начале каждого дня недели
            for (; $i < $calendar->dayLimitRowIndexes[$d]; $i++ )
            {
                for( $k = $firstDataColumn; $k < $width; $k++ )
                {
                    $cellData = $sheet->getCellByColumnAndRow($k, $i);
                    $bLeft = $this->hasRightBorder($sheet, $k - 1, $i);
                    $bTop = $this->hasBottomBorder($sheet, $k, $i - 1);
                    // эксплорим занятие (спускаемся в клетку)
                    // если в текущей ячейке точно есть левая и верхняя границы
                    if ( $bLeft && $bTop )
                    {
                        if ( empty($cellData) ) continue;
                        $layout = $this->inspectLocation($sheet, $k, $i);
                        $meetings = array();
                        if ( $layout['offset'] ) {
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
                        }
                        else {
                            $res = $this->extractLocation($sheet, $k, $layout['width'], $i, $layout['height']);
                            if ( empty($res['dates']) )
                                $res['dates'] = $calendar->getDatesByRow($i);
                            $meetings[] = new Meeting();
                            $meetings[0]->initFromArray($res);
                        }

                        if ( empty($meetings[0]->discipline) ) {
                            $k += $layout['width'] - 1;
                            continue;
                        }

                        // индекс текущей группы в массиве Group
                        $gid = floor(($k - $firstDataColumn) / $groupWidth);

                        // количество групп, задействованных в занятии
                        $groupsCount = ceil($layout['width'] / $groupWidth);

                        if ( ! $groupsCount  ) throw new Exception(var_dump($meetings[0]));

                        // количество занятий
                        $meetingsCount = floor($layout['height'] / 2);

                        // обнаруживаем эксплицитное время и фиксируем его в регистре
                        foreach ( $meetings as $meeting ) {
                            if ( !empty($meeting->time) ) {
                                $shift = $calendar->convertTimeToOffset($meeting->time);                          
                                for ( $g = 0; $g < $groupsCount; $g++ )
                                    $timeshift[$gid + $g] = $shift;
                                break;
                            }
                        }

                        // сохраняем базовое смещение времени для всех групп
                        // это нужно, когда встречается деление по подгруппам
                        // потому что в этом случае каждое занятие подгруппы инкрементирует $timeshift[$g]
                        for ( $g = 0; $g < $groupsCount; $g++ )
                            $basetimeshift[$gid + $g] = $timeshift[$gid + $g];

                        foreach ( $meetings as $meeting ) {
                            // множим занятия (по группам и по академическим часам)
                            for ( $g = $gid; $g < $groupsCount + $gid; $g++ ) {
                                // восстанавливаем базовое смещение в начале каждого цикла
                                $timeshift[$g] = $basetimeshift[$g]; 
                                for ( $z = 0; $z < $meetingsCount; $z++ ) {
                                    $m = new Meeting();
                                    $m->copyFrom($meeting);                                      
                                    if ( empty($timeshift[$g]) )
                                        $m->time = $calendar->lookupTimeByRow($i + $z * 2);
                                    else {
                                        if ( $z > 0 || empty($meeting->time) ) {
                                            $gridOffset = $calendar->lookupOffsetByRow($i + $z * 2);
                                            if ( $gridOffset >= $timeshift[$g] ) { // всё ок, идём по сетке
                                                $m->time = $calendar->convertOffsetToTime($gridOffset);
                                                $timeshift[$g] = $gridOffset + 100;
                                            }
                                            else { // смещение подпирает, отталкиваемся от него и инкрементируем до сеточного значения
                                                $m->time = $calendar->convertOffsetToTime($timeshift[$g]);
                                                $timeshift[$g] += 100 - ($timeshift[$g] % 100);
                                            }
                                        }
                                        else // для первой встречи просто инкрементируем значение в регистре
                                            $timeshift[$g] += 100 - ($timeshift[$g] % 100);                         
                                    }
                                    $m->group = $groups[$g];
                                    $harvest[] = $m;
                                }
                            }
                        }
                        // двигаем указатель столбца на ширину текущей локации
                        $k += $layout['width'] - 1;                        
                    }
                    else { // ищем указатели смещения времени
                        if ( preg_match('/[СCсc]\s(1?\d:[0-5]0)/u', $cellData, $matches) ) {
                            // индекс текущей группы в массиве Group
                            $gid = floor(($k - $firstDataColumn) / $groupWidth);
                            $shift = $calendar->convertTimeToOffset($matches[1]);
                            if ( $timeshift[$gid] < $shift ) {
                                // фиксируем смещение в регистре, если оно больше уже установленного
                                $timeshift[$gid] = $shift;
                            }
                        }                        
                    }
                }
            }
        }
        return $harvest;
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