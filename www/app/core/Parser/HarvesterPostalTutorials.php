<?php

/*
 *  Реализует сборщик расписания консультаций заочного факультета
 */

class HarvesterPostalTutorials extends HarvesterFulltime
{
    public function run()
    {
        $n = parent::run();
        $this->postProcess();
        return $n;
    }
    
    protected function postProcess()
    {
        foreach ( $this->harvest as &$meeting )
            $meeting->type = 'конс';
    }
    
}