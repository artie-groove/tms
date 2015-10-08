<?php


class CalendarBasic extends TableHandler
{
    public $dayLimitRowIndexes = array();
    protected $dayLimitRowIndexesPre = array(); // здесь нулевым индексом вставлен индекс первой строки таблицы
    public $dates = array();
    protected $timetable = array('8:00', '9:40', '11:20', '13:00', '14:40', '16:20', '18:00', '19:40');
    protected $sheet; // для доступа к текущему листу при генерации исключений
    
    public $meetingHeight = 2; // высота занятия в строках (2 или 3)
    public $timeshift; // для хранания динамического индекса смещения в массиве $timetable
    
    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }
    
    public function init($firstCol, $width, $firstRow, $height, $timeshift = null)
    {
        $sheet = $this->sheet;
        $this->dayLimitRowIndexes = $this->lookupDayLimitRowIndexes($sheet, $firstCol - 1, $firstRow, $firstRow + $height);
        $this->dayLimitRowIndexesPre = $this->dayLimitRowIndexes;
        array_unshift($this->dayLimitRowIndexesPre, $firstRow);
        $this->dates = $this->gatherDates($sheet, $firstRow - 1, $firstCol, $width, $this->dayLimitRowIndexes);
        $this->timeshift = $timeshift;
    }
    
    // === Определить индексы разделителей дней недели
    
    protected function lookupDayLimitRowIndexes($sheet, $firstCol, $firstRow, $finalRow)
    {        
        $dayLimitRowIndexes = array();
        $k = 0;
        for ( $i = $firstRow; $i < $finalRow; $i++ )
        {   
            // если наткнулись на границу
            if ( $this->hasBottomBorder($sheet, $firstCol, $i) && $this->hasRightBorder($sheet, $firstCol, $i) ) {  
                $dayLimitRowIndexes[$k] = $i + 1;
                $k++;
            }
        }
        return $dayLimitRowIndexes;
    }
    
    
    // === Заполнить массив с датами
    // массив проиндексирован по каждому дню из таблицы
    
    protected function gatherDates($sheet, $rx, $firstCol, $width, $dayLimitRowIndexes)
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
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / $this->meetingHeight;
        if ( $offset >= count($this->timetable) )
        {
            $limitsDump = implode(',', $this->dayLimitRowIndexesPre);
            throw new Exception("Нарушение целостности сетки (календарь, лист: '{$this->sheet->getTitle()}', строка: $r, limits: $limitsDump)");
        }
        return $this->timetable[$offset];
    }
    
    
    // === Получить смещение занятия относительно 8:00 в минутах
    
    public function lookupOffsetByRow($r)
    {
//         $dayLimitRowIndexes = $this->dayLimitRowIndexes;
//         array_unshift($dayLimitRowIndexes, $rx + 1);
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );
        return ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / $this->meetingHeight * 100;
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

