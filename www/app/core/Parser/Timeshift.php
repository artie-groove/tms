<?php

class Timeshift
{
    // регистр эксплицитных сеточных и внесеточных смещений времени
    // сбрасывается в начале каждого дня недели
    private $registry = array();
    
    // копия регистра, для корректной обработки времени у подгрупп
    // сохраняется перед клонированием занятий у подгрупп
    private $snapshot = array();
  
    // мощность регистра
    private $capacity;
    
    
    public function __construct($n)
    {
        $this->capacity = $n; 
    }
    
    public function reset()
    {
        $this->registry = array_fill(0, $this->capacity, 0);
    }

    public function backup()
    {
        $this->snapshot = $this->registry;
    }

    public function restore($i)
    {
        $this->registry[$i] = $this->snapshot[$i];
    }

    public function get($i)
    {
        return $this->registry[$i];
    }

    public function set($i, $time)
    {
        $this->registry[$i] = $time;
    }
}