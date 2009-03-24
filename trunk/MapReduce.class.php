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
 public function input($job,$instance,$key)
 {
  $id = $this->mapqueue->push(json_encode(array($job,$instance,$key)));
  return $id;
 }
 public function masterIteration()
 {
  $ids = array();
  foreach ($this->jobs as &$job)
  {
   if ($id = $job->masterIteration()) {$ids[] = $id;}
  }
  return $ids;
 }
 public function mapIteration()
 {
  $msg = $this->mapqueue->getNext();
  if ($msg)
  {
   list($job, $instance, $key) = json_decode($msg);
   $value = $this->jobs[$job]->getMapValue($key);
   var_dump(array('mapIteration',$job,$key,$value));
   $json = json_encode(array($job,$instance,$key,$value));
   $this->reducequeue->push($json);
   return TRUE;
  }
  return FALSE;
 }
 public function reduceIteration()
 {
  if ($item = $this->reducequeue->getNext())
  {
   list ($job, $instance, $key, $value) = json_decode($item);
   $this->jobs[$job]->reduceIteration($instance, $key, $value);
   return TRUE;
  }
  return FALSE;
 }
}
