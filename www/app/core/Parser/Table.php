<?php

class Table extends TableHandler
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_PROBE_WIDTH = 5;  // количество столбцов при "прощупывании" левой границы таблицы
    const MAX_WIDTH = 120;      // максимальное количество столбцов, формирующих таблицу
    const lookupWindowSize = 5; // окно просмотра эвристики определения границы
    
    public $sheet;
    public $cx;
    public $rx;
    public $width;
    public $height;
    
    protected $caption;
 
    protected $sectionStartCols = array();
    public $sectionRegions = array();
    public $sections = array();   
    
    public $hiddenRowsIndexes = array();
    public $hiddenColsIndexes = array();
    
    
    public function __construct($sheet)
    {
        $this->sheet = $sheet;    
    }
    
    public function init()
    {        
        $this->removeHiddenRows();
        $this->removeHiddenCols();
       
        $tableIsPresent = $this->probe();
        if ( ! $tableIsPresent )
            return false;
            //throw new Exception('Не удалось обнаружить очертаний таблицы в листе &laquo;' . $this->sheet->getTitle() . '&raquo;');
        
        $tableHasCaption = $this->fetchCaption();
        if ( ! $tableHasCaption ) {
            throw new Exception('Заголовок листа &laquo;' . $this->sheet->getTitle() . '&raquo; пуст');
        }
        
        $this->inspectGeometry();
        $this->cleanup();
        
        $tableHasSections = $this->exploreSectionRegions();
        if ( ! $tableHasSections )
            throw new Exception('Не удалось вычленить ни одной секции таблицы в листе &laquo;' . $this->sheet->getTitle() . '&raquo;');
        
        return true;
    }
    
    
    // === Зондировать лист на предмет наличия таблицы
    // если найден верхний контур таблицы, возвращает true; в противном случае - false
    
    protected function probe()
    {
        $c = 0;
        for ( $r = 1; $r < self::MAX_PROBE_DEPTH; $r++ ) {
            $reachedBottomBorder = $this->hasBottomBorder($this->sheet, 0, $r);
            if ( $reachedBottomBorder ) {
                $this->cx = $c;
                $this->rx = $r+1;
                return true;
            }
        }
        
        for ( $c = 0; $c < self::MAX_PROBE_WIDTH; $c++ ) {
            $reachedLeftBorder = $this->hasRightBorder($this->sheet, $c, $r);
            if ( $reachedLeftBorder ) {
                while ( $r >= 1 ) {
                    $r--;
                    $cantSeeBorderAnymore = !$this->hasRightBorder($this->sheet, $c, $r);
                    if ( $cantSeeBorderAnymore && $this->hasBottomBorder($this->sheet, $c+1, $r) ) {
                        $this->cx = $c+1;
                        $this->rx = $r+1;
                        return true;
                    }
                }
                break;
            }
        }
        
        return false;
    }
    
    
    // === Получить заголовок таблицы
    private function fetchCaption()
    {        
        $this->caption = '';
        for ( $r = 1; $r < $this->rx; $r++ )
            for ( $c = 0; $c < self::MAX_WIDTH; $c++)
                $this->caption .= $this->sheet->getCellByColumnAndRow($c, $r);
        
        if ( empty($this->caption) ) return false;
        return true;
    }
    
    public function getCaption()
    {
        return $this->caption;
    }
    
    // === Вычленяет секции в таблице
    // сохраняет их в виде записей [столбец, строка, ширина, высота]
    public function exploreSectionRegions()
    {
        $rx = $this->rx;
        $cx = $this->cx;
        
        for ( $w = 0, $cs = $c = $cx; $c < $cx + $this->width + 1; $c++ ) {
            // если находим ячейку "Дни", то начинается новая секция
            if ( in_array(trim($this->sheet->getCellByColumnAndRow($c, $rx)), array('Дни', 'дни')) ) {
                $w = 1;
                $cs = $c;
                $c++;
                while (
                    ( $c < $cx + $this->width ) 
                    && $this->hasBottomBorder($this->sheet, $c, $rx)
                    && $this->hasBottomBorder($this->sheet, $c, $rx-1) 
                    && !in_array(trim($this->sheet->getCellByColumnAndRow($c, $rx)), array('Дни', 'дни'))  )
                {
                    $c++;
                    $w++;
                }                
                $this->sectionRegions[] = array($cs, $rx, $w, $this->height);
                $w = 0;
                $c--;
            }            
        }
        
        return ! empty($this->sectionRegions);
    }
    
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    protected function inspectGeometry()
    {
        $sheet = $this->sheet;
        $rx = $this->rx;
        $cx = $this->cx;
 
        $w = 1; // cols
        $h = 0; // rows
        
        while ( $this->hasRightBorder($sheet, $cx + $w, $rx)      || $this->hasBottomBorder($sheet, $cx + $w, $rx) 
             || $this->hasRightBorder($sheet, $cx + $w-1, $rx)    || $this->hasBottomBorder($sheet, $cx + $w, $rx-1) )
        {
            $w++;    
        }
        $w--;
        
        $c = $cx + $w - 1;
        /*
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)   || $this->hasBottomBorder($sheet, $c, $rx+$h) 
             || $this->hasRightBorder($sheet, $c-1, $rx+$h) || $this->hasBottomBorder($sheet, $c, $rx+$h-1) )
        {
            $h++;
        }
        $h--;
        */
        
        // Go down the last border, make steps equal to lookupWindowSize
        // At each iteration find at least one border on the right
        // When you find no border on the right take the previous iteration
        // and move upwards row by row, exploring its bottom borders
        // On the first border met consider its row the last row of the table  

//         $steps = array();
        $rmax = $sheet->getHighestRow();
        while ( $h < $rmax) {
            $flag = false;
            for ( $i = $rx + $h; $i < $rx + $h + self::lookupWindowSize && !$flag; $i++ ) { // diving into the window
                $flag |= $this->hasRightBorder($sheet, $c, $i);
//                 $steps[] = $i;
            }
            if ( $flag ) $h += self::lookupWindowSize; // increase height and go to the next cycle
            else break;
        }        
        
        // no more borders on the right
        if ( $h < self::lookupWindowSize ) throw new Exception('Не удаётся определить высоту таблицы');
        for ( $j = $rx + $h; $j >= $rx + $h - self::lookupWindowSize; $j-- ) { // crawl upwards row by row
//             $steps[] = $j;
            $flag = false;
            for ( $k = $c; $k >= $c - self::lookupWindowSize && !$flag; $k-- ) { // explore row bottom borders
                $flag |= $this->hasBottomBorder($sheet, $k, $j);
            }
            if ( $flag ) { // a cell on the row has bottom border
                $h = $j - $rx + 1; // consider this row the last row of the table 
                break;
            }
        }
        
        
//         $log = "";
//         for ( $i = 0; $i < count($steps); $i++ ) {
//             $log .= "$i => {$steps[$i]}<br>";
//         }
        
//         $debug_info = array(
//             'width' => $this->remapCol($w),
//             'rx' => $this->remapRow($rx),
//             'height' => $h,
//             'lastRow' => $this->remapRow($rx + $h - 1),
//             //'leftCellValue' => $this->sheet->getCellByColumnAndRow($c - 1, $rx + $h - 1)->getValue()
//         );

//         throw new DebugException($log, $debug_info);
     
   
        $this->width = $w;
        $this->height = $h;
    }
  
    
    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    
    protected function cleanup()
    {
        $sheet = $this->sheet;
       
        /*
        // сносим плашки первой и второй недель
        for ( $r = $rx + 1; $r < $rx + $h; $r++ )
        {
            $cellColor = $sheet->getCellByColumnAndRow($cx + 1, $r)->getStyle()->getFill()->getStartColor()->getRGB();            
            $currentCellIsNotWhite = $cellColor !== "FFFFFF";
            $currentCellIsNotTransparent = $cellColor !== "000000";
            if ( $currentCellIsNotWhite && $currentCellIsNotTransparent ) {     
                $sheet->removeRow($r, 1);              
                $h--;
            }
        }
        */
        
        // вычищаем от текста плашку второй недели
        for ( $r = $this->rx + 1; $r < $this->rx + $this->height; $r++ )
        {
            $cellColor = $sheet->getCellByColumnAndRow($this->cx + 1, $r)->getStyle()->getFill()->getStartColor()->getRGB();            
            $currentCellIsNotWhite = $cellColor !== "FFFFFF";
            $currentCellIsNotTransparent = $cellColor !== "000000";
            if ( $currentCellIsNotWhite && $currentCellIsNotTransparent )
                for ( $c = 0; $c < $this->width; $c++ )
                    $sheet->getCellByColumnAndRow($c, $r)->setValue();
        }
        
        // расклеиваем все диапазоны по отдельным ячейкам
        foreach ( $sheet->getMergeCells() as $cells ) {
            $sheet->unmergeCells($cells);
        }
    }
    
    protected function removeHiddenRows()
    {    
        $highestRow = $this->sheet->getHighestRow();
        for ( $r = 1; $r <= $highestRow; $r++ ) {
            $visible = $this->sheet->getRowDimension($r)->getVisible();
            //echo $r . ' => ' . ( $visible ? 'true' : 'false' ) . '<br />';
            if ( ! $visible ) {
                $this->hiddenRowsIndexes[] = $r;
            }
        }

        $i = 0;
        foreach ( $this->hiddenRowsIndexes as $row ) {
            $this->sheet->removeRow($row - $i);
            $i++;
        }
        
        return count($this->hiddenRowsIndexes);
    }

    protected function removeHiddenCols()
    {
        $highestCol = PHPExcel_Cell::columnIndexFromString($this->sheet->getHighestColumn());
        if ( $highestCol > 500 ) $highestCol = 500;
        for ( $c = 0; $c <= $highestCol; $c++ ) {
            $visible = $this->sheet->getColumnDimensionByColumn($c)->getVisible();
            //echo $c . ' => ' . ( $visible ? 'true' : 'false' ) . '<br />';
            if ( ! $visible ) {
                $this->hiddenColsIndexes[] = $c;
            }
        }

        $i = 0;
        foreach ( $this->hiddenColsIndexes as $col ) {
            $this->sheet->removeColumnByIndex($col - $i);
            $i++;
        }
        
        return count($this->hiddenColsIndexes);
    }
    
    protected function remap($x, $values = array(), $offset = 0)
    {    
        $n = count($values);
        if ( $n === 0 || $offset >= $n || $x < $values[$offset] ) return $x;
        for ( $i = $offset; $i < $n; $i++ ) {
            if ( $x >= $values[$i] ) {           
                while ( $i < $n - 1 && $values[$i+1] - $values[$i] === 1 ) {
                    $i++;
                }
                $dx = $i - $offset + 1;
                $offset = $i + 1;
            }
            break;
        }
        
        // debug
        /*
        static $c = 1;
        echo 'step' . $c++ . ': ' . $x . ' => ' . ( $x + $dx ) . '<br>';
        */
        
        $x += $dx;
        
        return $this->remap($x, $values, $offset);
    }
    
    protected function remapRow($r)
    {
        return $this->remap($r, $this->hiddenRowsIndexes);
    }
    
    protected function remapCol($c)
    {
        return $this->remap($c, $this->hiddenColsIndexes);
    }
        
    
    
}