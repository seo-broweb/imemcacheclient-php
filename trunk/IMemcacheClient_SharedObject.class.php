<?php
class IMemcacheClient_SharedObject
{
 public $id;
 public $memcache;
 public $lock;
 public $obj;
 public $TTL;
 public $interval = 0.3;
 public $repeats = 10;
 public function __construct($memcache,$id,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
  $this->lock = $this->memcache->Lock('sho.'.$this->id,$this->TTL,$this->repeats,$this->interval);
 }
 public function fetchInter()
 {
  if ($this->fetch()) {return 1;}
  if ($this->lock->acquire(0)) {return 2;}
  $i = 0;
  while (!$this->fetch())
  {
   sleep($this->interval);
   ++$i;
   if ($i > $this->repeats) {return 0;}
  }
  return 1;
 }
 public function fetchWrite()
 {
  if ($this->lock->acquire() && $this->fetch(TRUE)) {return 1;}
  return 0;
 }
 public function fetch($nonCache = FALSE)
 {
  if (!isset($this->obj) || $nonCache)
  {
   $o = $this->memcache->get('sho.'.$this->id);
   $this->obj = ($o === FALSE)?FALSE:unserialize($o);
   return $o !== FALSE;
  }
  return TRUE;
 }
 public function write()
 {
  return $this->memcache->set('sho.'.$this->id,serialize($this->obj),$this->TTL);
 }
 public function flush()
 {
  $this->write();
  $this->unlock();
 }
 public function isLocked()
 {
  return $this->lock->isLocked();
 }
 public function lock()
 {
  return $this->lock->acquire();
 }
 public function unlock()
 {
  return $this->lock->release();
 }
}
