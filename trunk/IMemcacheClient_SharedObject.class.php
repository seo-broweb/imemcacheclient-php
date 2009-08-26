<?php
/*
    @class IMemcacheClient_SharedObject
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_SharedObject
{
 public $id;
 public $memcache;
 public $lock;
 public $obj;
 public $TTL;
 public $interval = 0.3;
 public $repeats = 10;
 public $rewritable = TRUE;
 public $assoc = FALSE;
 public function __construct($memcache,$id,$TTL = NULL,$rewritable = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  if ($rewritable === NULL) {$rewritable = TRUE;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
  $this->rewritable = $rewritable;
  $this->lock = $this->memcache->Lock('sho.'.$this->id,$this->TTL,$this->repeats,$this->interval);
 }
 public function fetchInter()
 {
  if (!$this->rewritable) {return $this->fetch()?1:0;}
  if ($this->fetch(TRUE)) {return 1;}
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
  if (!isset($this->obj) || $nonCache)
  {
   $s = $this->memcache->get('sho.'.$this->id,TRUE);
   $this->obj = ($s === FALSE)?FALSE:$this->decode($s);
   return $s !== FALSE;
  }
  return TRUE;
 }
 public function append($s)
 {
  if (!$this->rewritable)
  {
   return $this->memcache->append('sho.'.$this->id,$this->encode($s));
  }
  if ($this->lock->acquire())
  {
   $r = $this->memcache->append('sho.'.$this->id,$this->encode($s));
   $this->lock->release();
   return $r;
  }
  return FALSE;
 }
 public function prepend($s)
 {
 if (!$this->rewritable)
  {
   return $this->memcache->prepend('sho.'.$this->id,$this->encode($s));
  }
  if ($this->lock->acquire())
  {
   $r = $this->memcache->prepend('sho.'.$this->id,$this->encode($s));
   $this->lock->release();
   return $r;
  }
  return FALSE;
 }
 public function decode($s)
 {
  if (substr($s,-1) == ',') {$s = substr($s,0,-1);}
  return json_decode('{'.$s.'}',$this->assoc);
 }
 public function encode($o)
 {
  $s = json_encode($o);
  if ((substr($s,0,1) == '{') && (substr($s,-1) == '}')) {$s = substr($s,1,-1);}
  elseif ((substr($s,0,1) == '[') && (substr($s,-1) == ']')) {$s = substr($s,1,-1);}
  if ($s !== '') {$s .= ',';}
  return $s;
 }
 public function write()
 {
  return $this->memcache->set('sho.'.$this->id,$this->encode($this->obj),$this->TTL);
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
