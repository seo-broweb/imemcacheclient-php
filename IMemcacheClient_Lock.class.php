<?php
/*
    @class IMemcacheClient_Lock
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_Lock
{
 public $id;
 public $memcache;
 public $time;
 public $repeats;
 public $interval;
 public $acquired = FALSE;
 /*
    @method __construct
    @param object $memcache parent object.
    @param string $id ID of lock.
    @param string $time=0 time to lock.
    @param string $repeats=0 number of repeats of acquire().
    @param string $interval=1 interval between repeats in seconds.
    @description set option.
    @return object.
 */
 public function __construct($memcache,$id,$time = NULL,$repeats = NULL,$interval = NULL)
 {
  if ($time === NULL) {$time = 0;}
  if ($repeats === NULL) {$repeats = 0;}
  if ($interval === NULL) {$interval = 1;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->time = $time;
  $this->repeats = $repeats;
  $this->interval = $interval;
 }
  /*
    @method acquire()
    @param 
    @description acquire the lock.
    @return object.
 */
 public function acquire($repeats = NULL, $interval = NULL)
 {
  if ($repeats === NULL) {$repeats = $this->repeats;}
  if ($interval === NULL) {$interval = $this->interval;}
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('acquire',$this->id,$this->time,$this->repeats,$this->interval);}
  $i = 0;
  while (!$r = $this->memcache->add('lck.'.$this->id,time(),$this->time))
  {
   if (($i >= $this->repeats) && ($this->repeats !== -1)) {break;}
   usleep($this->interval*1000000);
   ++$i;
  }
  if ($r) {$this->acquired = TRUE;}
  return $r;
 }
 public function release($d = 0,$force = FALSE)
 {
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('release',$this->id,$d);}
  if (!$this->acquired && !$force) {return FALSE;}
  $this->acquired = FALSE;
  $this->memcache->delete('lck.'.$this->id,$d);
  return TRUE;
 }
 public function isLocked()
 {
  if ($this->memcache->trace) {$this->memcache->trace_stack[] = array('isLocked',$this->id);}
  return $this->memcache->get('lck.'.$this->id);
 }
}
