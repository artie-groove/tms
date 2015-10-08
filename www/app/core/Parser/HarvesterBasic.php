<?php

abstract class HarvesterBasic extends TableHandler
{
    protected $factory;
    protected $sheet;
    protected $type;
    protected $firstColumn;
    protected $firstRow;
    
    public function __construct($type, $sheet, $firstColumn, $firstRow)
    {
        $this->type = $type;
        $this->sheet = $sheet;
        $this->firstColumn = $firstColumn;
        $this->firstRow = $firstRow;
    }
    
    
    // === Запустить сбор данных
    abstract public function run();
    
    
    // === Постобработка собранных данных
    abstract protected function postProcess(&$harvest);
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getSection()
    {
        $customSections = array('Secondary');
        $section = in_array($this->type, $customSections) ? $this->type : '';
        $section = 'TableSection' . $section;
        return new $section($this->sheet, $this->getCalendar());
    }
    
    public function getCalendar()
    {
        $customCalendars = array('Evening', 'PostalSession', 'Secondary');
        $type = $this->type;
        if ( $type == 'PostalTutorials' ) $type = 'Evening';
        $calendar = in_array($type, $customCalendars) ? $type : 'Basic';
        $calendar = 'Calendar' . $calendar;
        return new $calendar($this->sheet);
    }
    
    public function getLocation()
    {
        $location = '';
        switch ( $this->type )
        {
            case 'Secondary':
                $location = 'Secondary';
                break;
            
            case 'PostalTutorials':
                $location = 'Single';
                break;
            
            default:
                $location = 'Basic';
        }
        $location = 'Location' . $location;
        return new $location();
    }
    
}