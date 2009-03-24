<?php
class MapReduce_Job
{
 public $name;
 public function __construct() {}
 public function input($key)
 {
  return $this->mapreduce->input($this->name,$key);
 }
 //abstract public function init();
 //abstract public function getMapValue($key);
 //abstract public function masterIteration();
 //abstract public function reduceIteration();
}
