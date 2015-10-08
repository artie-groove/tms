<?php

abstract class HarvesterBasic extends TableHandler
{
    protected $sheet;
    protected $firstColumn;
    protected $firstRow;
    protected $calendarType = 'Basic';
    protected $locationType = 'Basic';
    protected $sectionType = 'Basic';
    
    public function __construct($sheet, $firstColumn, $firstRow)
    {
        $this->sheet = $sheet;
        $this->firstColumn = $firstColumn;
        $this->firstRow = $firstRow;
    }
    
    
    // === Запустить сбор данных
    abstract public function run();
    
    
    // === Постобработка собранных данных
    abstract protected function postProcess(&$harvest);
    
}