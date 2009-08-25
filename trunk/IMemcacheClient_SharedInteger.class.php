<?php
/*
    @class IMemcacheClient_SharedInteger
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_SharedInteger extends IMemcacheClient_Entry
{
 public $id;
 public $memcache;
 public $lock;
 public $int;
 public $TTL;
 public $interval = 0.3;
 public $repeats = 10;
 public $rewritable;
 public $initvalue;
 public function __construct($memcache,$id,$initvalue = NULL,$TTL = NULL,$rewritable = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  if ($initvalue === NULL) {$initvalue = 0;}
  if ($rewritable === NULL) {$rewritable = FALSE;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
  $this->rewritable = $rewritable;
  $this->initvalue = $initvalue;
  $this->lock = $this->memcache->Lock('shi.'.$this->id,$this->TTL,$this->repeats,$this->interval);
 }
 public function keepAlive($n) {return $this->memcache->delete('shi.'.$this->id.$this->getTagsID(),$n);}
 public function fetchInter()
 {
  if (!$this->rewritable) {return $this->fetch()?1:0;}
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
  if ($this->lock->acquire())
  {
   if ($this->fetch(TRUE)) {return 1;}
   else
   {
    $this->lock->release();
    return 0;
   }
  }
  return 0;
 }
 public function fetch($nonCache = FALSE)
 {
  if (!isset($this->int) || $nonCache)
  {
   $s = $this->memcache->get('shi.'.$this->id.$this->getTagsID());
   $this->int = ($s === FALSE)?NULL:$s;
   if ($this->int === NULL) {$this->int = is_callable($this->initvalue)?call_user_func($this->initvalue,$this):$this->initvalue;}
   return $s !== FALSE;
  }
  return TRUE;
 }
 public function increment($n = 1)
 {
  $k = 'shi.'.$this->id.$this->getTagsID();
  if (!$this->rewritable)
  {
   $this->int = $this->memcache->increment($k,$n);
   if ($this->int !== FALSE) {return $this->int;}
   $this->memcache->add($k,is_callable($this->initvalue)?call_user_func($this->initvalue,$this):$this->initvalue,$this->TTL);
   return $this->int = $this->memcache->increment($k,$n);
  }
  if ($this->lock->acquire())
  {
   if (($this->int = $this->memcache->increment($k,$n)) === FALSE)
   {
    $this->memcache->add($k,is_callable($this->initvalue)?call_user_func($this->initvalue,$this):$this->initvalue,$this->TTL);
   }
   $r = $this->int = $this->memcache->increment($k,$n);
   $this->lock->release();
   return $r;
  }
  return FALSE;
 }
 public function decrement($n = 1)
 {
  $k = 'shi.'.$this->id.$this->getTagsID();
  if (!$this->rewritable)
  {
   $this->int = $this->memcache->decrement($k,$n);
   if ($this->int !== FALSE) {return $this->int;}
   $this->memcache->add($k,is_callable($this->initvalue)?call_user_func($this->initvalue,$this):$this->initvalue,$this->TTL);
   return $this->int = $this->memcache->decrement($k,$n);
  }
  if ($this->lock->acquire())
  {
   if (($this->int = $this->memcache->decrement($k,$n)) === FALSE)
   {
    $this->memcache->add($k,is_callable($this->initvalue)?call_user_func($this->initvalue,$this):$this->initvalue,$this->TTL);
   }
   $r = $this->int = $this->memcache->decrement($k,$n);
   $this->lock->release();
   return $r;
  }
  return FALSE;
 }
 public function write()
 {
  return $this->memcache->set('shi.'.$this->id.$this->getTagsID(),$this->int,$this->TTL);
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
