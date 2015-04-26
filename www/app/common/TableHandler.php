<?php

class TableHandler
{
    
    // === Проверить, есть ли правая граница
    
    protected function hasRightBorder($sheet, $cx, $rx)
    {
        $currentCellHasRightBorder = $sheet->getCellByColumnAndRow($cx, $rx)
            ->getStyle()->getBorders()->getRight()->getBorderStyle() !== "none";
        
        $nextCellHasLeftBorder = $sheet->getCellByColumnAndRow($cx + 1, $rx)
            ->getStyle()->getBorders()->getLeft()->getBorderStyle() !== "none";
        
        return ( $currentCellHasRightBorder || $nextCellHasLeftBorder );
    }
    
    // === Проверить, есть ли нижняя граница
    
    protected function hasBottomBorder($sheet, $cx, $rx)
    {
        $currentCellHasBottomBorder = $sheet->getCellByColumnAndRow($cx, $rx)
            ->getStyle()->getBorders()->getBottom()->getBorderStyle() !== "none";
        
        $nextCellHasTopBorder = $sheet->getCellByColumnAndRow($cx, $rx + 1)
            ->getStyle()->getBorders()->getTop()->getBorderStyle() !== "none";
        
        return ( $currentCellHasBottomBorder || $nextCellHasTopBorder );
    }
    
}