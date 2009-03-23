<?php
class MapReduce
{
 public $mapqueue;
 public $reducequeue;
 public $memcache;
 public $jobs = array();
 public $reduceResult = array();
 public $masterfp;
 public $masterdone = FALSE;
 public function __construct($memcache,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 3600;}
  $this->memcache = $memcache;
  $this->mapqueue = $this->memcache->queue('mr.m.'.$this->id,TRUE,$TTL);
  $this->reducequeue = $this->memcache->queue('mr.r.'.$this->id,TRUE,$TTL);
 }
 public function addJob($job)
 {
  $job->mapreduce = $this;
  return $job;
 }
 public function input($string) {return $this->mapqueue->push($string);}
 public function masterIteration()
 {
  return $this->job->masterIteration();
 }
 public function mapIteration()
 {
  $key = $this->mapqueue->getNext();
  if ($key)
  {
   $value = $this->job->getMapValue($key);
   //var_dump(array('mapIteration',$key,$value));
   $json = json_encode(array($key,$value));
   $this->reducequeue->push($json);
   return TRUE;
  }
  return FALSE;
 }
 public function reduceIteration()
 {
  if ($item = $this->reducequeue->getNext())
  {
   list ($key, $value) = json_decode($item);
   $this->job->reduceIteration($key,$value);
   return TRUE;
  }
  return FALSE;
 }
}
