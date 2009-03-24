<?php
class MapReduce_Job
{
 public $id;
 public function __construct() {}
 public function input($a)
 {
  return $this->mapreduce->input($a);
 }
 //abstract public function init();
 //abstract public function getMapValue($key);
 //abstract public function masterIteration();
 //abstract public function reduceIteration();
}
