<?php

class Table extends TableHandler
{
    public $sheet;
    public $cx;
    public $rx;
    public $width;
    public $height;
    
    protected $sectionStartCols = array();
    public $sections = array();
    
    
    public function __construct(&$sheet, $cx, $rx)
    {
        $this->sheet = $sheet;
        $this->cx = $cx;
        $this->rx = $rx;
    }
    
    public function init($calendarType)
    {
        $sheet = $this->sheet;
        $cx = $this->cx;
        $rx = $this->rx;
        $this->inspectGeometry($sheet, $cx, $rx);
        $this->cleanup($sheet, $cx, $rx, $this->width, $this->height);
        $this->exploreSections($sheet, $cx, $rx, $this->width, $this->height, $calendarType);
    }
    
    public function exploreSections($sheet, $cx, $rx, $width, $height, $calendarType)
    {
        for ( $w = 0, $cs = $c = $cx; $c < $cx + $width + 1; $c++, $w++ ) {
            if ( ! $this->hasBottomBorder($sheet, $c, $rx)
              && ! $this->hasBottomBorder($sheet, $c, $rx-1) ) {
                $section = new TableSection($sheet, $cs, $rx, $w, $height, $calendarType);
                $section->init();
                $this->sections[] = $section;
                $w = 0;
                $cs = $c + 1;
                $c += 1;
            }
        }
    }
    
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    protected function inspectGeometry($sheet, $cx, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $cx + $w, $rx)      || $this->hasBottomBorder($sheet, $cx + $w, $rx) 
             || $this->hasRightBorder($sheet, $cx + $w-1, $rx)    || $this->hasBottomBorder($sheet, $cx + $w, $rx-1) )
        {
            $w++;    
        }
        $w--;
        
        $c = $cx + $w - 1;
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)   || $this->hasBottomBorder($sheet, $c, $rx+$h)
             || $this->hasRightBorder($sheet, $c-1, $rx+$h) || $this->hasBottomBorder($sheet, $c, $rx+$h-1))
        {
            $h++;
        }
        $h--;
        
        $this->width = $w;
        $this->height = $h;
    }
  
    
    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    
    protected function cleanup(&$sheet, $cx, $rx, &$w, &$h)
    {
        /*
        // избавляемся от пустых столбцов
        for ( $c = 0; $c < $w; $c++ )
        {
            if ( ! $sheet->getColumnDimensionByColumn($c)->getVisible() ) {
                $sheet->removeColumnByIndex($c);
                $w--;
            }
        }
        
        // избавляемся от пустых строк
        for ( $r = $rx; $r < $rx + $h; $r++ )
            if ( ! $sheet->getRowDimension($r)->getVisible() ) {
                $sheet->removeRow($r);
                $h--;
            }
        */
        
        
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
        
        // расклеиваем все диапазоны по отдельным ячейкам
        foreach ( $sheet->getMergeCells() as $cells ) {
            $sheet->unmergeCells($cells);
        }
        
        
    }
}