<?php

/*
 *  Реализует сборщик дневного расписания 
 * 
 */

class HarvesterFulltime extends HarvesterBasic
{
    protected $calendarType = 'Basic';
    protected $locationType = 'Basic';
    
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
        
        $harvest = $this->harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar);
        return $this->postProcess($harvest);
    }
    
    protected function postProcess(&$harvest) {
        return $harvest;
    }
    
    // === Собрать данные с секции
        
    protected function harvestSection($sheet, $rx, $firstDataColumn, $width, $groupWidth, $groups, $calendar)
    {
        $harvest = array(); // массив встреч
        
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
                    
                    // индекс текущей группы в массиве Group
                    $gid = $this->getGroupId($c, $groupWidth, $firstDataColumn);
                    
                    // эксплорим занятие (спускаемся в клетку) если под курсором локация
                    if ( $this->isLocationEntryPoint($sheet, $c, $r) ) {
                        $locationType = 'Location' . $this->locationType;
                        $location = new $locationType();
                        $chunk = $location->collect($sheet, $calendar, $c, $r, $groups, $groupWidth, $gid);
                        
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
        if ( preg_match('/[СCсc]\s(1?\d:[0-5]0)/u', $cellData, $matches) ) {
            $shift = $calendar->convertTimeToOffset($matches[1]);
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