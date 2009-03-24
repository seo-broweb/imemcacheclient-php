<?php
class IMemcacheClient_Lock
{
 public $id;
 public $memcache;
 public $time;
 public $repeats;
 public $interval;
 public function __construct($memcache,$id,$time = NULL,$repeats = NULL,$interval = NULL)
 {
  if ($time === NULL) {$time = 10;}
  if ($repeats === NULL) {$repeats = 0;}
  if ($interval === NULL) {$interaval = 1;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->time = $time;
  $this->repeats = $repeats;
  $this->interval = $interval;
 }
 public function acquire()
 {
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('acquire',$this->id,$this->time,$this->repeats,$this->interval);}
  $i = 0;
  while (!$r = $this->memcache->add('lck.'.$this->id,time(),$this->time))
  {
   if ($i >= $this->repeats) {break;}
   sleep($this->interval);
   ++$i;
  }
  return $r;
 }
 public function release($d = 0)
 {
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('release',$this->memcache->id,$d);}
  return $this->memcache->delete('lck.'.$id,$d);
 }
 public function isLocked($id)
 {
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('isLocked',$id);}
  return $this->memcache->get('lck.'.$id);
 }
}
