<?php

/*
 *  Реализует сборщик дневного расписания 
 * 
 */

class HarvesterFulltime extends HarvesterBasic
{
    public function run()
    {
        foreach ( $this->table->sectionRegions as $region )
        {
            $section = $this->getSection();
            list ( $cs, $rx, $w, $h ) = $region;

            $section->init($cs, $rx, $w, $h);

            $table->sections[] = $section;
        }

        foreach ( $table->sections as $section ) {
            $chunk = $this->harvestSection($section);
            $this->harvest = array_merge($this->harvest, $chunk);
        }
        
        return count($this->harvest);
    }
    
    // === Собрать данные с секции
        
    protected function harvestSection($section)
    {
        $harvest = array(); // массив занятий
        
        $sheet = $this->table->sheet;
        $cx = $section->cx;
        $rx = $section->rx;
        $width = $section->width;
        $firstDataColumn = $section->firstDataColumn;
        $groupWidth = $section->groupWidth;
        $groups = $section->groups;
        $calendar = $section->calendar;
        
        // проходим по дням недели
        // индекс первой строки $i инициализируется здесь на основании первой строки таблицы данных
        // здесь же он инкрементируется по таблице индексов $dayLimitRowIndexes в конце каждого цикла
        for ( $r = $rx + 1, $d = 0; $d < count($calendar->dayLimitRowIndexes); $r = $calendar->dayLimitRowIndexes[$d], $d++ )
        {
            $calendar->timeshift->reset();
            for (; $r < $calendar->dayLimitRowIndexes[$d]; $r++ )
            {
                for ( $c = $firstDataColumn; $c < $cx + $width; $c++ )
                {
                    $cellData = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                    if ( empty($cellData) ) continue;
                    
                    // индекс текущей группы в массиве Group
                    $gid = $this->getGroupId($c, $groupWidth, $firstDataColumn);
                    
                    // эксплорим занятие (спускаемся в клетку) если под курсором локация
                    if ( $this->isLocationEntryPoint($sheet, $c, $r) ) {
                        $location = $this->getLocation();
                        $lastColumn = $section->cx + $section->width - 1;
                        $lastRow = $section->rx + $section->height - 1;
                        $chunk = $location->collect($sheet, $calendar, $c, $r, $groups, $groupWidth, $gid, $lastColumn, $lastRow);
                        
                        if ( ! empty($chunk) )
                            $harvest = array_merge($harvest, $chunk);

                        $c += $location->getWidth() - 1;
                    }
                    else // ищем указатели смещения времени
                        $this->obtainTimeMarker($cellData, $calendar, $gid);
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

    private function obtainTimeMarker($cellData, &$calendar, $gid)
    {
        if ( preg_match('/[СCсc]\s(1?\d)[.:]([0-5]0)/u', $cellData, $matches) ) {
            $shift = $calendar->convertTimeToOffset($matches[1] . ':' . $matches[2]);
            if ( $calendar->timeshift->get($gid) < $shift ) {
                // фиксируем смещение в регистре, если оно больше уже установленного
                $calendar->timeshift->set($gid, $shift);
            }
        }
    }
    
    
    // === Найти порядковый номер текущей группы
    
    private function getGroupId($c, $groupWidth, $firstDataColumn)
    {
        return floor(($c - $firstDataColumn) / $groupWidth);
    }
    
}