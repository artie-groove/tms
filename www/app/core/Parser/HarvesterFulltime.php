<?php

/*
 *  Реализует сборщик дневного расписания 
 * 
 */

class HarvesterFulltime extends HarvesterBasic
{
    public function run()
    {
        $harvest = array();
        
        $sheet = $this->sheet;
        $rx = $this->firstRow;
        
        $params = $this->establishTableParams($sheet, $rx);
        list ( $width, $height, $datesMatrixFirstColumn, $datesMatrixWidth, $firstDataColumn, $groupWidth ) = array_values($params);
        $groups = $this->exploreGroups($sheet, $firstDataColumn, $width, $rx, $groupWidth);       
        $dayLimitRowIndexes = $this->lookupDayLimitRowIndexes($sheet, $rx + 1, $rx + $height);            
        $dates = $this->gatherDates($sheet, $rx, $datesMatrixFirstColumn, $datesMatrixWidth, $dayLimitRowIndexes);

        // проходим по дням недели
        // индекс первой строки $i инициализируется здесь на основании первой строки таблицы данных
        // здесь же он инкрементируется по таблице индексов $dayLimitRowIndexes в конце каждого цикла
        for ( $i = $rx + 1, $d = 0; $d < count($dayLimitRowIndexes); $i = $dayLimitRowIndexes[$d], $d++ )
        {
            // регистр эксплицитных сеточных и внесеточных смещений времени
            $timeshift = array_fill(0, count($groups), 0); // сбрасывается в начале каждого дня недели
            for (; $i < $dayLimitRowIndexes[$d]; $i++ )
            {
                for( $k = $firstDataColumn; $k < $firstDataColumn + $width; $k++ )
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
                                    $res1['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
                                $meetings[] = new Meeting();
                                $meetings[0]->initFromArray($res1);
                            }
                            else {
                                foreach ( array($res1, $res2) as $res )
                                    if ( empty($res['dates']) )
                                    $res['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
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
                                $res['dates'] = $this->getDatesByRow($i, $dayLimitRowIndexes, $dates);
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
                                $shift = $this->convertTimeToOffset($meeting->time);                          
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
                                        $m->time = $this->lookupTimeByRow($rx, $i + $z * 2, $dayLimitRowIndexes);
                                    else {
                                        if ( $z > 0 || empty($meeting->time) ) {
                                            $gridOffset = $this->lookupOffsetByRow($rx, $i + $z * 2, $dayLimitRowIndexes);
                                            if ( $gridOffset >= $timeshift[$g] ) { // всё ок, идём по сетке
                                                $m->time = $this->convertOffsetToTime($gridOffset);
                                                $timeshift[$g] = $gridOffset + 100;
                                            }
                                            else { // смещение подпирает, отталкиваемся от него и инкрементируем до сеточного значения
                                                $m->time = $this->convertOffsetToTime($timeshift[$g]);
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
                            $shift = $this->convertTimeToOffset($matches[1]);
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
        //if ( ! $valid ) throw new Exception('Table is not valid');
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
    
    // === Заполнить массив с датами
    // массив проиндексирован по каждому дню из таблицы
    
    private function gatherDates($sheet, $rx, $datesMatrixFirstColumn, $datesMatrixWidth, $dayLimitRowIndexes)
    {        
        $months = array(
            'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
        );
        
        $nDays = count($dayLimitRowIndexes);
        $dates = array_fill(0, $nDays, '');
        
        for ( $m = $datesMatrixFirstColumn; $m < $datesMatrixFirstColumn + $datesMatrixWidth; $m++ )
        {
            // вытащим название месяца строкой
            $monthName = mb_strtolower(trim($sheet->getCellByColumnAndRow($m, $rx)));
            // найдём числовое соответствие месяцу и запишем его в формате "ММ"
            $month = sprintf('%02d', array_search($monthName, $months) + 1);

            $r = $rx + 1; // счётчик индекса строки
            
            // для каждого дня недели заполняем соответствующий индекс массива dates
            for ( $wd = 0; $wd < $nDays; $wd++ )
            {
                for (; $r < $dayLimitRowIndexes[$wd]; $r++)
                {
                    $dateCellData = trim($sheet->getCellByColumnAndRow($m, $r));
                    if ( empty($dateCellData) ) continue; // пустые ячейки пропускаем
                    $dates[$wd] .= "$dateCellData.$month,";
                }
                $r = $dayLimitRowIndexes[$wd];
            }
        }
        // отрезаем запятые в конце каждой строки
        foreach ( $dates as &$d ) $d = rtrim($d, ',');
        return $dates;
    }
    
}