<?php

abstract class HarvesterBasic extends TableHandler
{
    protected $sheet;
    protected $firstRow;
    
    public function __construct($sheet, $firstRow)
    {
        $this->sheet = $sheet;
        $this->firstRow = $firstRow;
    }
    
    
    // === Запустить сбор данных
    abstract public function run();
    
    
    
    
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    protected function inspectTableGeometry($sheet, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $w, $rx)      || $this->hasBottomBorder($sheet, $w, $rx) 
             || $this->hasRightBorder($sheet, $w-1, $rx)    || $this->hasBottomBorder($sheet, $w, $rx-1) )
        {
            $w++;    
        }
        $w--;
        
        $c = $w - 1;
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)   || $this->hasBottomBorder($sheet, $c, $rx+$h)
             || $this->hasRightBorder($sheet, $c-1, $rx+$h) || $this->hasBottomBorder($sheet, $c, $rx+$h-1))
        {
            $h++;
        }
        $h--;
        return array('width' => $w, 'height' => $h);
    }
    
    
    // === Проверить целостность границ таблицы

    protected function validateTableBorders($sheet, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $h; $r++ )  
            if ( ! $this->hasRightBorder($sheet, $w - 1, $r) )            
                throw new Exception("Нарушена целостность правой границы на строке $r");   
                //return false;
            
        // проверяем нижнюю границу
        for ( $c = 0; $c < $w; $c++ )
            if ( ! $this->hasBottomBorder($sheet, $c, $rx + $h - 1) )
                throw new Exception("Нарушена целостность нижней границы в столбце $c");  
                //return false;            
        
        return true;
    }
    
    
    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    
    protected function cleanupTable($sheet, $rx, &$w, &$h)
    {
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
        
        // сносим плашки первой и второй недель
        for ( $r = $rx + 1; $r < $rx + $h; $r++ )
        {
            $cellColor = $sheet->getCellByColumnAndRow(1, $r)->getStyle()->getFill()->getStartColor()->getRGB();            
            $currentCellIsNotWhite = $cellColor !== "FFFFFF";
            $currentCellIsNotTransparent = $cellColor !== "000000";
            if ( $currentCellIsNotWhite && $currentCellIsNotTransparent ) {     
                $sheet->removeRow($r, 1);                
                $h--;
            }
        }
    }
    
    
    // === Распознать группы
    
    protected function exploreGroups($sheet, $cx, $tableWidth, $rx, $groupWidth)
    {
        $groups = array();
        
        for ( $c = $cx; $c < $tableWidth; $c += $groupWidth )
        {   
            $groupName = trim($sheet->getCellByColumnAndRow($c, $rx));            
            
            if ( !preg_match('/В[А-Я]{1,3}-(\d|\d{3})/u', $groupName) )
                throw new Exception('Неверное название группы: "' . $groupName . '"');
            
            $groups[] = $groupName;   
        }        
        return $groups;
    }
    
}