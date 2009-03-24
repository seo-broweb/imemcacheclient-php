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
 public function __construct($memcache,$id,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 3600;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->mapqueue = $this->memcache->queue('mr.m.'.$this->id,TRUE,$TTL);
  $this->reducequeue = $this->memcache->queue('mr.r.'.$this->id,TRUE,$TTL);
 }
 public function addJob($job)
 {
  $job->mapreduce = $this;
  return $this->jobs[$job->name] = $job;
 }
 public function input($job,$key)
 {
  return $this->mapqueue->push(json_encode($job,$key));
 }
 public function masterIteration()
 {
  $r = FALSE;
  foreach ($this->jobs as &$job)
  {
   if ($job->masterIteration()) {$r = TRUE;}
  }
  return $r;
 }
 public function mapIteration()
 {
  $msg = $this->mapqueue->getNext();
  if ($msg)
  {
   list($job,$key) = json_decode($msg);
   $value = $this->jobs[$job]->getMapValue($key);
   var_dump(array('mapIteration',$job,$key,$value));
   $json = json_encode(array($job,$key,$value));
   $this->reducequeue->push($json);
   return TRUE;
  }
  return FALSE;
 }
 public function reduceIteration()
 {
  if ($item = $this->reducequeue->getNext())
  {
   list ($job, $key, $value) = json_decode($item);
   $this->jobs[$job]->reduceIteration($key,$value);
   return TRUE;
  }
  return FALSE;
 }
}
