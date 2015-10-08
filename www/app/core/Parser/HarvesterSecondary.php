<?php

/*
    Реализует сборщик расписания занятий второго высшего образования 
*/

class HarvesterSecondary extends HarvesterFulltime
{
    protected $calendarType = 'Secondary';
    protected $locationType = 'Secondary';
    protected $sectionType = 'Secondary';
    
    private function getGroupId($c, $groupWidth, $firstDataColumn) 
    {
        return 0;
    }
}

   