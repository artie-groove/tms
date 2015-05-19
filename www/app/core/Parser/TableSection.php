<?php
   
class TableSection extends TableHandler
{
    public $sheet;
    public $rx;
    public $cx;
    public $width;
    public $datesMatrixFirstColumn;
    public $datesMatrixWidth;
    public $firstDataColumn;
    public $groupWidth;
    public $groups;
    public $calendarType;
    public $calendar;

    public function __construct($sheet, $cx, $rx, $width, $height, $calendarType)
    {
        $this->sheet = $sheet;
        $this->cx = $cx;
        $this->rx = $rx;
        $this->width = $width;
        $this->height = $height;
        $this->calendarType = $calendarType;
    }
    
    public function init()
    {
        $sheet = $this->sheet;
        $cx = $this->cx;
        $rx = $this->rx;
        $width = $this->width;
        $height = $this->height;
        $calendarType = $this->calendarType;
        
        $this->validateBorders($sheet, $cx, $rx, $width, $height);
        
        // определяем ширину матрицы дат
        $this->datesMatrixFirstColumn = $cx + 1;       
        
        $this->datesMatrixWidth = $this->fetchDatesMatrixWidth($sheet, $this->datesMatrixFirstColumn, $rx);
        $this->firstDataColumn = $this->establishFirstDataColumn();
        $this->groupWidth = $this->getGroupWidth($sheet, $this->firstDataColumn, $rx, $width);
        $this->groups = $this->exploreGroups($sheet, $cx, $rx, $this->firstDataColumn, $width, $this->groupWidth);
        
        $timeshift = new Timeshift(count($this->groups));
        $calendarClass = 'Calendar' . $calendarType;
        $this->calendar = new $calendarClass($sheet, $this->datesMatrixFirstColumn, $this->datesMatrixWidth, $rx + 1, $height - 1, $timeshift);
        
    }
    
    
    // === Проверить целостность границ таблицы
    protected function validateBorders($sheet, $cx, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $h; $r++ ) {
            $c = $cx + $w - 1;
            if ( ! $this->hasRightBorder($sheet, $c, $r) )            
                throw new Exception("Нарушена целостность правой границы близ ячейки (C$c:R$r)");  
        }             
            
        // проверяем нижнюю границу
        for ( $c = $cx; $c < $cx + $w; $c++ ) {
            $r = $rx + $h - 1;
            if ( ! $this->hasBottomBorder($sheet, $c, $r) )
                throw new Exception("Нарушена целостность нижней границы близ ячейки (C$c:R$r), $cx, $w");  
        }
        
        return true;
    }
 
    
    protected function fetchDatesMatrixWidth($sheet, $datesMatrixFirstColumn, $rx)
    {
        $datesMatrixWidth = 0;
        while ( ! in_array(trim($sheet->getCellByColumnAndRow($datesMatrixFirstColumn + $datesMatrixWidth, $rx)), array('Часы', 'Время')) ) $datesMatrixWidth++;
        
//         if ( $datesMatrixWidth === 0 ) throw new Exception(var_export($datesMatrixWidth));
        
        if ( $datesMatrixWidth > 5 ) throw new Exception("Некорректное количество столбцов в календаре. Удалите все скрытые столбцы");
        
        return $datesMatrixWidth;
    }
    
    
    protected function establishFirstDataColumn()
    {
        return $this->datesMatrixFirstColumn + $this->datesMatrixWidth + 1;
    }
    
    
    // рассчитываем ширину на группу по первой ячейке для группы
    protected function getGroupWidth($sheet, $firstDataColumn, $rx, $width)
    {
        $groupWidth = 1;
        $c = $firstDataColumn;
        while ( empty(trim($sheet->getCellByColumnAndRow($c + 1, $rx))) ) $c++;
        $groupWidth = $c - $firstDataColumn + 1;
        if ( ($width - $firstDataColumn) % $groupWidth !== 0 ) throw new Exception('Ширина групп должна быть равной');
        return $groupWidth;
    }
    
    
    // === Распознать группы    
    protected function exploreGroups($sheet, $cx, $rx, $firstDataColumn, $width, $groupWidth)
    {
        $groups = array();
        
        for ( $c = $firstDataColumn; $c < $cx + $width; $c += $groupWidth )
        {   
            $groupName = trim($sheet->getCellByColumnAndRow($c, $rx));            
            
            if ( !preg_match('/В[А-Я]{1,3}-(?:\d|\d{3}[ам]?)/u', $groupName) )
                throw new Exception('Неверное название группы: "' . $groupName . '"');
            
            $groups[] = $groupName;
        }        
        return $groups;
    }
    
    
}