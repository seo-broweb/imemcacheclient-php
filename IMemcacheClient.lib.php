<?php
class IMemcacheClient
{
 public $prefix = '';
 public $trace_stack = array();
 public $trace = FALSE;
 public $conn = NULL;
 public $connector = 'memcached';
 public $pers_id = 'mem';
 public $compress = 0;
 public function __construct()
 {
  $this->prefix = SQL_DBNAME.'.'.SQL_TBLPREFIX.'.';
  if ($this->connector === 'memcache') {$this->conn = new Memcache();}
  else
  {
   $this->conn = new Memcached();
   $this->setOption(Memcached::OPT_COMPRESSION,$this->compress);
  }
 }
 public function setOption($k,$v)
 {
  return $this->conn->setOption($k,$v);
 }
 public function getStats()
 {
  return $this->conn->getStats();
 }
 public function addServer($host,$port,$weight = NULL)
 {
  if ($this->conn instanceof Memcache) {return $this->conn->addServer($host,$port);}
  return $this->conn->addServer($host,$port,$weight);
 }
 public function addServers($a)
 {
  if ($this->conn instanceof Memcache)
  {
   foreach ($a as $s) {$this->conn->addServer($s[0],$s[1],isset($s[2])?$s[2]:NULL);}
   return TRUE;
  }
  return $this->conn->addServers($a);
 }
 public function get($k)
 {
  if ($this->trace) {$this->trace_stack[] = array('get',$k);}
  return $this->conn->get($this->prefix.$k);
 }
 public function flush($delay = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('flush',$delay);}
  if ($this->conn instanceof Memcache) {return $this->conn->flush();}
  return $this->conn->flush($delay);
 }
 public function getMulti($k = array())
 {
  if (is_string($k)) {$k = func_get_args();}
  if ($this->trace) {$this->trace_stack[] = array('getMulti',implode(',',$k));}
  foreach ($k as &$v) {$v = $this->prefix.$v;}
  $r = $this->conn->getMulti($k);
  $l = strlen($this->prefix);
  foreach ($r as $s => $v)
  {
   $r[substr($s,$l)] = $v;
   unset($r[$s]);
  }
  return $r;
 }
 public function delete($k,$t = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('delete',$k);}
  if ($t < 0) {$t = 0;}
  if (!is_array($k)) {return $this->conn->delete($this->prefix.$k,$t);}
  foreach ($k as $kk)
  {
   $tt = $t;
   if (is_array($kk))
   {
    if (isset($kk[1])) {$tt = $kk[1];}
    $kk = $kk[0];
   }
   return $this->conn->delete($this->prefix.$kk,$tt);
  }
 }
 public function set($k,$v,$e = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('set',$k,$e);}
  if ($this->conn instanceof Memcache) {return $this->conn->set($this->prefix.$k,$v,0,$e);}
  if ($e < 0) {$e = 0;}
  return $this->conn->set($this->prefix.$k,$v,$e);
 }
 public function add($k,$v,$e = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('add',$k);}
  if ($this->conn instanceof Memcache) {return $this->conn->add($this->prefix.$k,$v,0,$e);}
  if ($e < 0) {$e = 0;}
  return $this->conn->add($this->prefix.$k,$v,$e);
 }
 public function put($k,$v,$e = 0)
 {
  $this->set($k,$v,$e);
 }
 public function append($k,$v)
 {
  if ($this->trace) {$this->trace_stack[] = array('append',$k);}
  return $this->conn->append($this->prefix.$k,$v);
 }
 public function prepend($k,$v)
 {
  if ($this->trace) {$this->trace_stack[] = array('prepend',$k);}
  return $this->conn->prepend($this->prefix.$k,$v);
 }
 public function increment($k,$v = 1)
 {
  if ($this->trace) {$this->trace_stack[] = array('increment',$k);}
  return $this->conn->increment($this->prefix.$k,$v);
 }
 public function decrement($k,$v = 1)
 {
  if ($this->trace) {$this->trace_stack[] = array('decrement',$k);}
  return $this->conn->decrement($this->prefix.$k,$v);
 }
 public function queue($id,$exclusiveRead = NULL,$defaultItemTTL = NULL)
 {
  return new IMemcacheClient_Queue($this,$id,$exclusiveRead,$defaultItemTTL);
 }
 public function lock($id,$time = NULL,$repeats = NULL,$interval = NULL)
 {
  return new IMemcacheClient_Lock($this,$id,$time,$repeats,$interval);
 }
}
class IMemcacheClient_Lock
{
 public $id;
 public $memcache;
 public $time;
 public $repeats;
 public $interval;
 public function __construct($time = NULL,$repeats = NULL,$interval = NULL)
 {
  if ($time === NULL) {$time = 10;}
  if ($repeats === NULL) {$repeats = 0;}
  if ($interval === NULL) {$interaval = 1;}
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
