<?php


class CalendarEvening extends CalendarBasic {

    // === Получить время начала занятия по номеру строки
    
    public function lookupTimeByRow($r)
    {
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );        
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / 2;
        if ( $i % 6 !== 0 ) $offset += 6;
        return $this->timetable[$offset];
    }
    
    
    // === Получить смещение занятия относительно 8:00 в минутах
    
    public function lookupOffsetByRow($r)
    {
        for ( $i = 1; $r >= $this->dayLimitRowIndexesPre[$i]; $i++ );
        $offset = ( $r - $this->dayLimitRowIndexesPre[$i-1] ) / 2 * 100;
        if ( $i % 6 !== 0 ) $offset += 600;
        return $offset;
    }
    
}

