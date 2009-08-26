<?php
/*
    @class IMemcacheClient_Queue
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_Queue
{
 public $memcache;
 public $id;
 public $readpointer = 0; // for non-exclusive readings
 public $exclusiveRead = FALSE;
 public $readStreams = array('main');
 public $defaultItemTTL = 0;
 public $passedCorrupted;
 public $strategy = 1; /*
   1 - check whether if next item exists and use strategy #2,
   2 - only decrement read pointer on failed read,
   3 - check read pointers after push
  */
 public $lastReadStatus = TRUE;
 public $lastItemId;
 public $knownMaxId = 0;
 public function __construct($memcache,$id,$exclusiveRead = NULL,$defaultItemTTL = NULL)
 {
  if ($exclusiveRead === NULL) {$exclusiveRead = TRUE;}
  if ($defaultItemTTL === NULL) {$defaultItemTTL = 10;}
  if ($exclusiveRead === TRUE) {$this->exclusiveRead = 'main';}
  elseif ($exclusiveRead !== FALSE) {$this->exclusiveRead = $exclusiveRead;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->defaultItemTTL = $defaultItemTTL;
 }
 public function checkDeadStreams()
 {
  if (!$this->knownMaxId = $this->memcache->get('qk.'.$this->id)) {return;}
  foreach ($this->readStreams as $stream)
  {
   $n = $this->memcache->get($k = 'qr.'.$stream.'.'.$this->id);
   if ($n && ($n >= $this->knownMaxId)) {$this->memcache->set($k,$this->knownMaxId);}
  }
 }
 public function deleteItem($id = NULL,$t = 0)
 {
  if ($id === NULL) {$id = $this->lastItemId;}
  if (!$id) {return FALSE;}
  return $this->memcache->delete('qi.'.$this->id.'.'.$id,$t);
 }
 public function getRangeNonAtomic($minId = 1,$maxId = NULL)
 {
  $r = array();
  if ($minId < 1) {$minId = 1;}
  for ($id = $minId; ($id < $maxId) || ($maxId === NULL); ++$id)
  {
   $v = $this->memcache->get('qi.'.$this->id.'.'.$id);
   if ($v !== FALSE)
   {
    $v['id'] = $id;
    $r[] = $v;
   }
   elseif ($maxId === NULL) {break;}
  }
  return $r;
 }
 public function getNext()
 {
  $this->passedCorrupted = FALSE;
  if ($this->exclusiveRead === FALSE)
  {
   $r = $this->getById($this->readpointer);
   if ($r) {++$this->readpointer;}
   return $r;
  }
  $k = 'qr.'.$this->exclusiveRead.'.'.$this->id;
  if ($this->strategy === 1)
  {
   $n = $this->memcache->get($k);
   if ($n !== FALSE)
   {
    ++$n;
    if (!$v = $this->getById($n))
    {
     $isFutureCheck = ($this->knownMaxId !== NULL) && ($n > $this->knownMaxId);
     if (!$isFutureCheck) {$this->passedCorrupted = TRUE;}
     else {return FALSE;}
    }
   }
  }
  if (!$id = $this->memcache->increment($k))
  {
   if (!$this->memcache->add($k,$id = 1)) {if (!$id = $this->memcache->increment($k)) {return FALSE;}}
  }
  if (($this->strategy === 1) && ($id == $n)) {$result = $v;}
  else {$result = $this->getById($id);}
  if (($this->strategy === 1) || ($this->strategy === 2))
  {
   if ($result === FALSE)
   {
    if ($this->strategy === 1)
    {
     if ($n > $this->knownMaxId) {$this->memcache->decrement($k);}
     else {$this->passedCorrupted = TRUE;}
    }
    else {$this->memcache->decrement($k);}
   }
  }
  if ($result !== FALSE) {$this->lastItemId = $id;}
  return $result;
 }
 public function getById($id) // non-exclusive
 {
  return $this->memcache->get('qi.'.$this->id.'.'.$id);
 }
 public function set($id,$value,$t = NULL)
 {
  if ($t === NULL) {$t = $this->defaultItemTTL;}
  return $this->memcache->set('qi.'.$this->id.'.'.$id,$value,$t);
 }
 public function push($value,$t = NULL)
 {
  $k = 'qk.'.$this->id;
  if (!$id = $this->memcache->increment($k))
  {
   if (!$this->memcache->add($k,$id = 1)) {if (!$id = $this->memcache->increment($k)) {return FALSE;}}
  }
  if ($t === NULL) {$t = $this->defaultItemTTL;}
  $this->memcache->set('qi.'.$this->id.'.'.$id,$value,$t);
  if ($this->exclusiveRead !== FALSE)
  {
   if ($this->strategy === 3)
   {
    foreach ($this->readStreams as $stream)
    {
     $n = $this->memcache->get($k = 'qr.'.$stream.'.'.$this->id);
     if ($n && ($n >= $id)) {$this->memcache->set($k,$id-1);}
    }
   }
  }
  return $id;
 }
}
