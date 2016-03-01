<?php
   
class TableSection extends TableHandler
{
    const datesMatrixMaxWidth = 5;
    public $sheet;
    public $rx;
    public $cx;
    public $width;
    public $datesMatrixFirstColumn;
    public $datesMatrixWidth;
    public $firstDataColumn;
    public $groupWidth;
    public $groups;
    public $calendar;

    public function __construct($sheet, $calendar)
    {
        $this->sheet = $sheet;
        $this->calendar = $calendar;
    }
    
    public function init($cx, $rx, $width, $height)
    {
        $sheet = $this->sheet;
        
        $this->cx = $cx;
        $this->rx = $rx;
        $this->width = $width;
        $this->height = $height;     
        
        //$this->validateBorders($sheet, $cx, $rx, $width, $height);
        
        // определяем ширину матрицы дат
        $this->datesMatrixFirstColumn = $cx + 1;       
        
        $this->datesMatrixWidth = $this->fetchDatesMatrixWidth($sheet, $this->datesMatrixFirstColumn, $rx);
        $this->firstDataColumn = $this->establishFirstDataColumn();
        $dataWidth = $this->cx + $width - $this->firstDataColumn;
        $this->groupWidth = $this->getGroupWidth($sheet, $this->firstDataColumn, $rx, $dataWidth);
        $this->groups = $this->exploreGroups($sheet, $cx, $rx, $this->firstDataColumn, $width, $this->groupWidth);
        
        $timeshift = new Timeshift(count($this->groups));
        $this->calendar->init($this->datesMatrixFirstColumn, $this->datesMatrixWidth, $rx + 1, $height - 1, $timeshift);
    }
    
    
    // === Проверить целостность границ таблицы
    protected function validateBorders($sheet, $cx, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $h; $r++ ) {
            $c = $cx + $w - 1;
            if ( ! $this->hasRightBorder($sheet, $c, $r) )
            {
                $c++;
                throw new Exception("Нарушена целостность правой границы близ ячейки (лист &laquo;{$sheet->getTitle()}&raquo;, столбец $c, строка $r)");  
            }             
        }
        // проверяем нижнюю границу
        for ( $c = $cx; $c < $cx + $w; $c++ ) {
            $r = $rx + $h - 1;
            if ( ! $this->hasBottomBorder($sheet, $c, $r) )
            {
                $c++;
                throw new Exception("Нарушена целостность нижней границы близ ячейки (лист &laquo;{$sheet->getTitle()}&raquo;, столбец $c, строка $r)");  
            }
        }
        
        return true;
    }
 
    
    protected function fetchDatesMatrixWidth($sheet, $datesMatrixFirstColumn, $rx)
    {
        $hoursColumnCaptions = array('часы', 'время');
        $datesMatrixWidth = 0;
        do {
            $value = mb_strtolower(trim($sheet->getCellByColumnAndRow($datesMatrixFirstColumn + $datesMatrixWidth, $rx)));
            $hoursColumnReached = in_array($value, $hoursColumnCaptions);
            $nonDomainValueRead = ! in_array($value, $this->calendar->months);
            if ( $hoursColumnReached || $nonDomainValueRead ) break;            
            $datesMatrixWidth++;
        } while ( $datesMatrixWidth < self::datesMatrixMaxWidth );
        
       /*
       if ( $datesMatrixWidth > 5 )
           throw new Exception("Не удаётся обнаружить столбец времени занятий (&laquo;Часы&raquo; или &laquo;Время&raquo;) на&nbsp;листе &laquo;{$sheet->getTitle()}&raquo;");

        if ( $datesMatrixWidth > 5 ) throw new Exception("Некорректное количество столбцов в календаре. Удалите все скрытые столбцы (&laquo;{$sheet->getTitle()}&raquo;)");
        */
        
        if ( ! $datesMatrixWidth )
        {
            if ( empty($value) ) $datesMatrixWidth = 1; // for PostalSession calendar type
            else throw new Exception('Не удалось распознать матрицу дат');
        }
        
        return $datesMatrixWidth;
    }
    
    
    protected function establishFirstDataColumn()
    {
        $firstDataColumn = $this->datesMatrixFirstColumn + $this->datesMatrixWidth - 1;
        do {
            $firstDataColumn++;
            $cellValue = $this->sheet->getCellByColumnAndRow($firstDataColumn, $this->rx)->getValue();
            if ( ! empty($cellValue) ) {
                $cellValueContainsDigits = preg_match("/\d+/", $cellValue);
                if ( $cellValueContainsDigits )
                    break;
            }
            
        } while ( $firstDataColumn < $this->cx + $this->width - 1 );

        return $firstDataColumn;
    }
    
    
    // рассчитываем ширину на группу по первой ячейке для группы
    protected function getGroupWidth($sheet, $firstDataColumn, $rx, $dataWidth)
    {
        $groupWidth = 1;
        $c = $firstDataColumn;
        while ( empty(trim($sheet->getCellByColumnAndRow($c + 1, $rx))) && $c < $firstDataColumn + $dataWidth - 1 ) $c++;
        $groupWidth = $c - $firstDataColumn + 1;
//         throw new DebugException('x', array($firstDataColumn, $dataWidth, $groupWidth));
        if ( $dataWidth % $groupWidth !== 0 ) throw new Exception("Ширина групп должна быть равной (лист &laquo;{$sheet->getTitle()}&raquo;)");
        return $groupWidth;
    }
    
    
    // === Распознать группы    
    protected function exploreGroups($sheet, $cx, $rx, $firstDataColumn, $width, $groupWidth)
    {
        $groups = array();
        
        for ( $c = $firstDataColumn; $c < $cx + $width; $c += $groupWidth )
        {   
            $groupName = trim($sheet->getCellByColumnAndRow($c, $rx));            
            $groupNameRecognized = array();
            if ( !preg_match('/(В[А-Я]{1,3}-(?:\d{3}|\d)[ам]?)/u', $groupName, $groupNameRecognized) )
                throw new Exception("Неверное название группы: &laquo;$groupName&raquo; (&laquo;{$sheet->getTitle()}&raquo;). Приведите названия групп в соответствие с утверждённым форматом. Возможно, есть скрытые столбцы в календаре.");
            
            $groups[] = $groupNameRecognized[1];
        }        
        return $groups;
    }
    
    
}