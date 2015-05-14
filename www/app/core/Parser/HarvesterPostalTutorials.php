<?php

/*
 *  Реализует сборщик расписания консультаций заочного факультета
 */

class HarvesterPostalTutorials extends HarvesterEvening
{
    protected $locationType = 'Single';
    
    protected function postProcess(&$harvest) {
        foreach ( $harvest as &$meeting )
            $meeting->type = 'конс';
        
        return $harvest;
    }
    
}