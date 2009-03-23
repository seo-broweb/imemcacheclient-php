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
 public function Queue($id,$exclusiveRead = NULL,$defaultItemTTL = NULL)
 {
  return new IMemcacheClient_Queue($this,$id,$exclusiveRead,$defaultItemTTL);
 }
 public function Lock($id,$time = NULL,$repeats = NULL,$interval = NULL)
 {
  return new IMemcacheClient_Lock($this,$id,$time,$repeats,$interval);
 }
 public function MapReduce($id)
 {
  return new IMemcacheClient_MapReduce($this,$id);
 }
}
