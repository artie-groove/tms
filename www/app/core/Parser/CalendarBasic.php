<?php


class CalendarBasic extends TableHandler {
    
//     protected $sheet;
//     protected $firstRow;
//     protected $finalRow;
//     protected $width;
    
    public $dayLimitRowIndexes = array();
    protected $dayLimitRowIndexesPre = array(); // здесь нулевым индексом вставлен индекс первой строки таблицы
    protected $dates = array();
    protected $timetable = array('8:00', '9:40', '11:20', '13:00', '14:40', '16:20', '18:00', '19:40');
    
    public function __construct($sheet, $firstCol, $width, $firstRow, $height)
    {
        $this->dayLimitRowIndexes = $this->lookupDayLimitRowIndexes($sheet, $firstRow, $firstRow + $height);
        $this->dayLimitRowIndexesPre = $this->dayLimitRowIndexes;
        array_unshift($this->dayLimitRowIndexesPre, $firstRow);
        $this->dates = $this->gatherDates($sheet, $firstRow - 1, $firstCol, $width, $this->dayLimitRowIndexes);
    }
    
    
    // === Определить индексы разделителей дней недели
    
    protected function lookupDayLimitRowIndexes($sheet, $firstRow, $finalRow)
    {        
        $dayLimitRowIndexes = array();
        $k = 0;
        for ( $i = $firstRow; $i < $finalRow; $i++ )
        {   
            // если наткнулись на границу
            if ( $this->hasBottomBorder($sheet, 0, $i) ) {  
                $dayLimitRowIndexes[$k] = $i + 1;
                $k++;
            }
        }
        return $dayLimitRowIndexes;
    }
    
    
    // === Заполнить массив с датами
    // массив проиндексирован по каждому дню из таблицы
    
    private function gatherDates($sheet, $rx, $firstCol, $width, $dayLimitRowIndexes)
    {        
        $months = array(
            'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',
            'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
        );
        
        $nDays = count($dayLimitRowIndexes);
        $dates = array_fill(0, $nDays, '');
        
        for ( $m = $firstCol; $m < $firstCol + $width; $m++ )
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
    
    
    // === Определить даты по строке

    public function getDatesByRow($r)
    {
        $wd = 0; // week day

        // находим индекс текущего дня в таблице дат
        while ( $r >= $this->dayLimitRowIndexes[$wd] ) $wd++;

        return $this->dates[$wd];            
    }
    
    
    // === Получить время начала занятия по номеру строки
    
    public function lookupTimeByRow($r)
    {
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / 2;
        return $this->timetable[$offset];
    }
    
    
    // === Получить смещение занятия относительно 8:00 в минутах
    
    public function lookupOffsetByRow($r)
    {
//         $dayLimitRowIndexes = $this->dayLimitRowIndexes;
//         array_unshift($dayLimitRowIndexes, $rx + 1);
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );
        return ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / 2 * 100;
    }
    
    
    // === Преобразовать смещение в строку времени формата "HH:MM"
    
    public function convertOffsetToTime($offset)
    {
        $h = floor($offset / 60) + 8;
        $m = sprintf('%02d', $offset % 60);
        return "$h:$m";
    }
    
    
    // === Преобразовать строку времени формата "HH:MM" в смещение (в минутах)
    
    public function convertTimeToOffset($time)
    {
        list ( $h, $m ) = explode(':', $time);        
        return ($h - 8) * 60 + $m;
    }
    
}

