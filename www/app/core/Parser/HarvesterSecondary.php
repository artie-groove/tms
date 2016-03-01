<?php

/*
    Реализует сборщик расписания занятий второго высшего образования 
*/

class HarvesterSecondary extends HarvesterFulltime
{   
    private function getGroupId($c, $groupWidth, $firstDataColumn) 
    {
        return 0;
    }
}

   