<?php

/*
 *  Каждое занятие здесь уникально,
 *  т.е., оно не дублируется в зависимости от количества занимаемых строк
 */

class LocationSingle extends LocationBasic
{
    protected function getMeetingsCount($height) {
        return 1;
    }
}