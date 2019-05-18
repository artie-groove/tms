<?php
   
class TableSectionSecondary extends TableSection
{
    // рассчитываем ширину на группу по первой ячейке для группы
    protected function getGroupWidth($sheet, $firstDataColumn, $rx, $dataWidth)
    {
        /*
        $groupWidth = 1;
        $c = $firstDataColumn;
        while ( empty(trim($sheet->getCellByColumnAndRow($c + 1, $rx))) && $c < $firstDataColumn + $dataWidth - 1 ) $c++;
        $groupWidth = $c - $firstDataColumn + 1;
        if ( $dataWidth % $groupWidth !== 0 ) throw new Exception("Ширина групп должна быть равной (лист &laquo;{$sheet->getTitle()}&raquo;)");
        return $groupWidth;
        */
        return $dataWidth;
    }
    
    
    // === Распознать группы    
    protected function exploreGroups($sheet, $cx, $rx, $firstDataColumn, $width, $groupWidth)
    {
        for ( $c = $cx; $c < $cx + $width; $c++ )   
            $str = trim($sheet->getCellByColumnAndRow($c, $rx));            
        
        $groupNameRecognized = array();
        if ( !preg_match('/(В[А-Я]{1,3}-(?:\d{3}|\d)[ам]?)/u', $str, $groupNameRecognized) )
                throw new Exception("Не распознано название группы в строке: &laquo;$str&raquo; (&laquo;{$sheet->getTitle()}&raquo;). Приведите названия групп в соответствие с утверждённым форматом.");
        
        return array($groupNameRecognized[1]);
    }
}