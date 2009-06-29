<?php
/*
    @class MapReduce_Job
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class MapReduce_Job
{
 public $name;
 public $reducedObjects = array();
 public $TTL = 0;
 public $mapreduce;
 public function __construct() {}
 public function input($instance,$key)
 {
  return $this->mapreduce->input($this->name,$instance,$key);
 }
 public function getReducedObject($instance)
 {
  if (isset($this->reducedObjects[$instance])) {return $this->reducedObjects[$instance];}
  return $this->reducedObjects[$instance] = $this->mapreduce->getReducedObject($this,$instance);
 }
 //abstract public function init();
 //abstract public function getMapValue($key);
 //abstract public function masterIteration();
 //abstract public function reduceIteration();
}
