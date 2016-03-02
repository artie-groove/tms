<?php

class Table extends TableHandler
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_PROBE_WIDTH = 5;  // количество столбцов при "прощупывании" левой границы таблицы
    const MAX_COL = 100;        // ограничение на верхнее значение индекса столбца
    const MAX_ROW = 200;        // ограничение на верхнее значение индекса строки
    const lookupWindowSize = 5; // окно просмотра эвристики определения границы
    
    public $sheet;
    public $cx;                 // индекс первого столбца
    public $rx;                 // индекс первой строки
    public $width;              // ширина таблицы
    public $height;             // высота таблицы
    
    protected $caption;         // заголовок таблицы
    protected $cmax;            // максимальное значение столбца
    protected $rmax;            // максимальное значение строки
 
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
        $this->cmax = PHPExcel_Cell::columnIndexFromString($this->sheet->getHighestColumn());
        $this->rmax = $this->sheet->getHighestRow();
        
        if ( $this->cmax > self::MAX_COL ) $this->cmax = self::MAX_COL;
        if ( $this->rmax > self::MAX_ROW ) $this->rmax = self::MAX_ROW;
        
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
            for ( $c = 0; $c < self::MAX_COL; $c++)
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
    
    
    // === Определить ширину или высоту таблицы
    // определение производится на основе анализа частичной непрерывности границы
    // алгоритм управляется параметром $horizontal: true - ширина, false - высота
    // описание алгоритма на примере поиска высоты
    // Go down the last border, make steps equal to lookupWindowSize
    // At each iteration find at least one border on the right
    // When you find no border on the right break the main cycle
    // and move upwards row by row, exploring its bottom borders
    // On the first border met consider its row the last row of the table  
    protected function lookupEdge($horizontal)
    {
        $d = 0;
        $m1 = 'hasTopBorder';
        $m2 = 'hasRightBorder';
        $m3 = 'hasBottomBorder';
        $config = array(
            array( $m1, $m2, $this->cx, $this->cmax, $this->rx, $this->rx + self::lookupWindowSize ), // horizontal (width)
            array( $m2, $m3, $this->rx, $this->rmax, $this->cx + $this->width - 1, $this->cx + $this->width - 1 ) // vertical (height)
        );
        list ( $method_main, $method_fine, $a, $dmax, $b, $b1 ) = $horizontal ? $config[0] : $config[1]; 
        
        while ( $d < $dmax ) {
            $flag = false;
            for ( $i = $a + $d; $i < $a + $d + self::lookupWindowSize && !$flag; $i++ ) { // diving into the window
                list ( $c, $r ) = $horizontal ? array($i, $b) : array($b, $i);
                $flag |= $this->$method_main($this->sheet, $c, $r);
            }
            $d += self::lookupWindowSize; // increase depth and go to the next cycle
            if ( ! $flag ) break;
        }        
        
        // no more borders in the set direction
        if ( $d < self::lookupWindowSize * 2 ) throw new DebugException('Не удаётся определить параметр', $config[0]);
        for ( $j = $a + $d; $j >= $a + $d - self::lookupWindowSize * 2; $j-- ) { // crawl backwards line by line
            $flag = false;
            for ( $k = $b1; $k >= $b1 - self::lookupWindowSize && !$flag; $k-- ) { // explore line borders
                list ( $c, $r ) = $horizontal ? array($j, $k) : array($k, $j);
                $flag |= $this->$method_fine($this->sheet, $c, $r);
            }
            if ( $flag ) { // a cell on this index has border
                $d = $j - $a + 1; // consider this index the last one for the table
                break;
            }
        }
        
        return $d;
    }
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    protected function inspectGeometry()
    {
        $this->width = $this->lookupEdge(true);     // всегда должна вызываться первой
        $this->height = $this->lookupEdge(false);   // вычисляется на основе установленной ширины
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