<?php


class CalendarPostalSession extends CalendarBasic
{   
    // === Заполнить массив с датами
    // массив проиндексирован по каждому дню из таблицы    
    protected function gatherDates($sheet, $rx, $firstCol, $width, $dayLimitRowIndexes)
    {      
        $nDays = count($dayLimitRowIndexes);
        $dates = array_fill(0, $nDays, '');
        
        $r = $rx + 1; // счётчик индекса строки

        // для каждого дня недели заполняем соответствующий индекс массива dates
        for ( $wd = 0; $wd < $nDays; $wd++ )
        {
            for (; $r < $dayLimitRowIndexes[$wd]; $r++)
            {
                $dateCellData = trim($sheet->getCellByColumnAndRow($firstCol, $r));
                if ( empty($dateCellData) ) continue; // пустые ячейки пропускаем
                //$dates[$wd] = $this->floatToString($dateCellData);
                if ( preg_match("/[0-3]?[0-9]\.[01][0-9]/u", $dateCellData, $matches) )
                    $dates[$wd] = $matches[0];
                else
                    throw new Exception("Неверный формат даты: $dateCellData (ожидается: ДД.ММ) [лист &laquo;{$sheet->getTitle()}&raquo;, строка $r]");
                break;
            }
            $r = $dayLimitRowIndexes[$wd];
        }
               
        return $dates;
    }
    
    /*
    private function floatToString($value)
    {
        $day = floor($value);
        $month = round(($value - $day) * 100);
        return implode('.', array($day, $month));
    }
    */
}

