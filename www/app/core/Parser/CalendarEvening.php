<?php


class CalendarEvening extends CalendarBasic {
    
    // === Получить время начала занятия по номеру строки
    
    public function lookupTimeByRow($r)
    {
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ ); // смещаем указатель к текущей строке
        
        /*
        // занятие может занимать от двух до трёх строк
        // определить, сколько строк занимает занятие можно по чётности / нечётности
        // если разница между текущей позицией и разделом чётная, то это трёхстрочка
        // к первому занятию это, естественно, не относится (там всегда единица)
        */
        
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / $this->meetingHeight;
        if ( $i % 6 !== 0 ) $offset += 6; // по субботам занятия начинаются как обычно с 8:00
        if ( $offset >= count($this->timetable) ) throw new Exception("Ошибка в расчёте номера занятия в строке $r (лист &laquo;{$this->sheet->getTitle()}&raquo;)" . var_export($this->timetable) . $offset);
        return $this->timetable[$offset];
    }
    
    
    // === Получить смещение занятия относительно 8:00 в минутах
    
    public function lookupOffsetByRow($r)
    {
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / $this->meetingHeight * 100;
        if ( $i % 6 !== 0 ) $offset += 600;
        return $offset;
    }
    
}

