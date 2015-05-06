<?php

/*
 *  Реализует сборщик расписания занятий вечернего факультета
 */

class HarvesterEvening extends HarvesterFulltime
{
    protected $calendarType = 'Evening';
}