<?php
class IMemcacheClient_SharedObject
{
 public $id;
 public $memcache;
 public $lock;
 public $obj;
 public $TTL;
 public function __construct($memcache,$id,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
  $this->lock = $this->memcache->lock('sho.'.$this->id);
 }
 public function fetch($nonCache = FALSE)
 {
  if (!isset($this->obj) || $nonCache)
  {
   $o = $this->memcache->get('sho.'.$this->id);
   return $this->obj = unserialize($o);
  }
  return $this->obj;
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
