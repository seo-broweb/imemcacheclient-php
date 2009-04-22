<?php
/*
    @class IMemcacheClient
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
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
 /*
    @method setOption
    @param string $name name of option
    @param string $value value of option
    @description set option.
    @return boolean
 */
 public function setOption($k,$v)
 {
  return $this->conn->setOption($k,$v);
 }
 /*
    @method getStats
    @return array
    @description get statistics.
 */
 public function getStats()
 {
  return $this->conn->getStats();
 }
 /*
    @method addServer
    @param string $host host
    @param int $port port
    @param $weight weight of server (for multi-server distribution)
    @description add server.
    @return boolean
 */
 public function addServer($host,$port,$weight = NULL)
 {
  if ($this->conn instanceof Memcache) {return $this->conn->addServer($host,$port);}
  return $this->conn->addServer($host,$port,$weight);
 }
 /*
    @method addServers
    @param array $servers arguments for addServer.
    @description add bunch of servers.
    @return boolean
 */
 public function addServers($a)
 {
  if ($this->conn instanceof Memcache)
  {
   foreach ($a as $s) {$this->conn->addServer($s[0],$s[1],isset($s[2])?$s[2]:NULL);}
   return TRUE;
  }
  return $this->conn->addServers($a);
 }
 /*
    @method get
    @param string $key the key to get.
    @description get key from server.
    @return mixed
 */
 public function get($k)
 {
  if ($this->trace) {$this->trace_stack[] = array('get',$k);}
  return $this->conn->get($this->prefix.$k);
 }
 /*
    @method flush
    @param int $delay=0 delay of this operation in seconds
    @description invalidates all existing cache items.
 */
 public function flush($delay = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('flush',$delay);}
  if ($this->conn instanceof Memcache) {return $this->conn->flush();}
  return $this->conn->flush($delay);
 }
 /*
    @method getMulti
    @param array $keys
    @description get bunch of keys.
 */
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
 /*
    @method delete
    @param array|string $keys
    @param $time=0 time to block after invalidation (in seconds).
    @description delete one or bunch of keys.
 */
 public function delete($k,$t = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('delete',$k,$t);}
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
   $this->conn->delete($this->prefix.$kk,$tt);
  }
 }
 /*
    @method set
    @param $key key to store
    @param $value value to store
    @param $exp=0 expiration time in seconds.
    @description store the key unconditional.
 */
 public function set($k,$v,$e = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('set',$k,$e);}
  if ($this->conn instanceof Memcache) {return $this->conn->set($this->prefix.$k,$v,0,$e);}
  if ($e < 0) {$e = 0;}
  return $this->conn->set($this->prefix.$k,$v,$e);
 }
 /*
    @method add
    @param $key key to store
    @param $value value to store
    @param $exp=0 expiration time in seconds.
    @description store the key unconditional if it does not exists.
 */
 public function add($k,$v,$e = 0)
 {
  if ($this->trace) {$this->trace_stack[] = array('add',$k,$e);}
  if ($this->conn instanceof Memcache) {return $this->conn->add($this->prefix.$k,$v,0,$e);}
  if ($e < 0) {$e = 0;}
  return $this->conn->add($this->prefix.$k,$v,$e);
 }
 /*
   @alias set
 */
 public function put($k,$v,$e = 0)
 {
  $this->set($k,$v,$e);
 }
 /*
    @method append
    @param $key appeding key.
    @param $value string to append.
    @description append value to specified key.
 */
 public function append($k,$v)
 {
  if ($this->trace) {$this->trace_stack[] = array('append',$k);}
  return $this->conn->append($this->prefix.$k,$v);
 }
 /*
    @method prepend
    @param $key prepeding key.
    @param $value string to prepend
    @description prepend value to specified key.
 */
 public function prepend($k,$v)
 {
  if ($this->trace) {$this->trace_stack[] = array('prepend',$k);}
  return $this->conn->prepend($this->prefix.$k,$v);
 }
 /*
    @method increment
    @param $key incrementing key.
    @param $value=1 value to increment.
    @description increment specified key with value.
 */
 public function increment($k,$v = 1)
 {
  if ($this->trace) {$this->trace_stack[] = array('increment',$k);}
  return $this->conn->increment($this->prefix.$k,$v);
 }
 /*
    @method decrement
    @param $key decrementing key.
    @param $value=1 value to decrement.
    @description decrement specified key with value.
 */
 public function decrement($k,$v = 1)
 {
  if ($this->trace) {$this->trace_stack[] = array('decrement',$k);}
  return $this->conn->decrement($this->prefix.$k,$v);
 }
 /*
    @method Queue
    @description get instance of Queue.
 */
 public function Queue($id,$exclusiveRead = NULL,$defaultItemTTL = NULL)
 {
  static $loaded = FALSE;
  if (!$loaded)
  {
   require_once dirname(__FILE__).'/IMemcacheClient_Queue.class.php';
   $loaded = TRUE;
  }
  return new IMemcacheClient_Queue($this,$id,$exclusiveRead,$defaultItemTTL);
 }
 /*
    @method Lock
    @description get instance of Lock.
 */
 public function Lock($id,$time = NULL,$repeats = NULL,$interval = NULL)
 {
  static $loaded = FALSE;
  if (!$loaded)
  {
   require_once dirname(__FILE__).'/IMemcacheClient_Lock.class.php';
   $loaded = TRUE;
  }
  return new IMemcacheClient_Lock($this,$id,$time,$repeats,$interval);
 }
 /*
    @method SharedObject
    @description get instance of SharedObject
 */
 public function SharedObject($id,$TTL = NULL,$rewritable = NULL)
 {
  static $loaded = FALSE;
  if (!$loaded)
  {
   require_once dirname(__FILE__).'/IMemcacheClient_SharedObject.class.php';
   $loaded = TRUE;
  }
  return new IMemcacheClient_SharedObject($this,$id,$TTL,$rewritable);
 }
 /*
    @method SharedObject
    @description get instance of SharedInteger
 */
 public function SharedInteger($id,$initvalue = NULL,$TTL = NULL,$rewritable = NULL)
 {
  static $loaded = FALSE;
  if (!$loaded)
  {
   require_once dirname(__FILE__).'/IMemcacheClient_SharedInteger.class.php';
   $loaded = TRUE;
  }
  return new IMemcacheClient_SharedInteger($this,$id,$initvalue,$TTL,$rewritable);
 }
}
