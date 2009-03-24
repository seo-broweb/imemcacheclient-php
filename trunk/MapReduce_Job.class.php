<?php
class MapReduce_Job
{
 public $name;
 public $reducedObj;
 public $TTL = 0;
 public function __construct() {}
 public function input($instance,$key)
 {
  return $this->mapreduce->input($this->name,$instance,$key);
 }
 //abstract public function init();
 //abstract public function getMapValue($key);
 //abstract public function masterIteration();
 //abstract public function reduceIteration();
}
