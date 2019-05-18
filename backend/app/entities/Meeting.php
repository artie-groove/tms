<?php

class Meeting
{
    public $dates;
    public $time;
    public $discipline;        
    public $type;
    public $group;
    public $room;        
    public $lecturer;
    public $comment;
    public $offset;

    public function __construct($params = null) {
        if ( !is_array($params) || count($params) === 0 ) return;
        $fields = array( 'dates', 'time', 'room', 'discipline', 'type', 'lecturer', 'group', 'comment' );
        foreach ( $fields as $i => $f ) // предполагается, что на вход подаётся вектор с данными в определённом порядке
            $this->$f = isset($params[$i]) ? $params[$i] : null;
        foreach ( $fields as $f ) // для инициализации из ассоциативных массивов
            if ( array_key_exists($f, $params) ) $this->$f = $params[$f];
    }

    public function copyFrom($original)
    {
        $fields = array( 'dates', 'time', 'room', 'discipline', 'type', 'lecturer', 'group', 'comment' ); 
        foreach ( $fields as $f )
            $this->$f = $original->$f;
    }
    
    public function __get($property)
    {
           
    }
}