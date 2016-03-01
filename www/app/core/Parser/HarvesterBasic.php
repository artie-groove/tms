<?php

abstract class HarvesterBasic extends TableHandler
{
    protected $type;
    protected $table;
    protected $harvest;
    
    public function __construct($table)
    {
        $this->type = str_replace('Harvester', '', get_class($this));
        $this->table = $table;
        $this->harvest = array();
    }
        
    // === Запустить сбор данных
    abstract public function run();
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getSection()
    {
        $customSections = array('Secondary');
        $section = in_array($this->type, $customSections) ? $this->type : '';
        $section = 'TableSection' . $section;
        return new $section($this->table->sheet, $this->getCalendar());
    }
    
    public function getCalendar()
    {
        $customCalendars = array('Evening', 'PostalSession', 'Secondary');
        $type = $this->type;
        if ( $type == 'PostalTutorials' ) $type = 'Evening';
        $calendar = in_array($type, $customCalendars) ? $type : 'Basic';
        $calendar = 'Calendar' . $calendar;
        return new $calendar($this->table->sheet);
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
    
    public function getHarvest()
    {
        return $this->harvest;
    }
    
}