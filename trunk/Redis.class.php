<?php
/*
    @class Redis
    @package IMemcacheClient
    @url http://code.google.com/p/imemcacheclient-php/
    @author kak.serpom.po.yaitsam@gmail.com
    @description Connector for Redis (http://code.google.com/p/redis/)
    @license LGPL, BSD-compabible. Adding to the Redis repository permitted.
*/
class Redis
{
 public $servers = array();
 public $default_port = 6379;
 public $dtags_enabled = TRUE;
 public $pool = array();
 public function __construct() {}
 public function addServer($host,$port = NULL,$weight = NULL)
 {
  if ($port === NULL) {$port = $this->default_port;}
  $this->servers[$host.':'.$port] = $weight;
 }
 private function getConnection($addr)
 {
  if (isset($this->pool[$addr])) {return $this->pool[$addr];}
  if (strpos($addr,'://') === FALSE) {$path = 'tcp://'.$addr;}
  else {$path = $addr;}
  if ($conn = fsockopen($path))
  {
   $this->pool[$addr] = $conn;
   return $addr;
  }
  return FALSE;
 }
 private function getConnectionByKey($key)
 {
  if (($this->dtags_enabled) && (($sp = strpos($key,'[')) !== FALSE) && (($ep = strpos($key,']')) !== FALSE) && ($ep > $sp))
  {
   $key = substr($key,$sp+1,$ep-$sp-1);
  }
  srand(crc32($key));
  $addr = array_rand($this->servers);
  srand();  
  $this->getConnection($addr);
  return $addr;
 }
 private function read($k,$len = NULL)
 {
  if ($len === 0) {return '';}
  if ($len === NULL) {return fgets($this->pool[$k]);}
  $r = '';
  for (;;)
  {
   $l = strlen($r);
   if ($l >= $len) {break;}
   $r .= fread($this->pool[$k],min($len-$l,1024));
  }
  return $r;
 }
 private function write($k,$s)
 {
  return fwrite($this->pool[$k],$s);
 }
 public function requestByServer($k,$s)
 {
  if ($k == '*')
  {
   $result = array();
   foreach ($this->servers as $k => $v)
   {
    $this->getConnection($k);
    $this->write($k,$s."\r\n");
    $result[$k] = $this->getResponse($k);
   }
   return $result;
  }
  if ($k === NULL)
  {
   srand();
   $k = array_rand($this->servers);
  }
  $this->getConnection($k);
  $this->write($k,$s."\r\n");
  return $this->getResponse($k);
 }
 public function requestByKey($k,$s)
 {
  $k = $this->getConnectionByKey($k);
  $this->write($k,$s."\r\n");
  $r = $this->getResponse($k);
  return $r;
 }
 private function disconnect($k)
 {
  if (!isset($this->pool[$k])) {return FALSE;}
  fclose($this->pool[$k]);
  unset($this->pool[$k]);
  $this->pool = array_values($this->pool);
  return TRUE;
 }
 private function getResponse($k)
 {
  if (($data = $this->read($k)) === FALSE) {return FALSE;}
  $c = $data[0];
  $data = substr($data,1);
  if (substr($data,-2) == "\r\n") {$data = substr($data,0,-2);}
  switch ($c)
  {
   case '-':
    trigger_error($data, E_USER_WARNING);
    return FALSE;
   case '+':
    return $data;
   case ':':
    return strpos($data, '.') !== FALSE ? (int)$data : (float)$data;
   case '$':
    return $this->getBulkReply($k,$c.$data);
   case '*':
    $num = (int)$data;
    $result = array();
    for ($i = 0; $i < $num; ++$i) {$result[] = $this->getResponse($k);}
    return $result;
   default:
    trigger_error("Invalid reply type byte: '$c'");
    return FALSE;
  }
 }
 private function getBulkReply($k,$data)
 {
  if ($data === NULL) {$data = rtrim($this->read($k));}
  if ($data == '$-1') {return NULL;}
  if ($data[0] != '$') {trigger_error('Unknown response prefix for \''.$c.$data.'\'', E_USER_WARNING); return FALSE;}
  $data = $this->read($k,(int) substr($data,1));
  $end = $this->read($k,2);
  if ($end != "\r\n") {trigger_error('Unknown response end: \''.$end.'\'', E_USER_WARNING); return FALSE;}
  return $data;
 }
 public function ping($server = NULL)
 {
  return $this->requestByServer($server,'PING');
 }
 public function get($key,$plain = FALSE)
 {
  $r = $this->requestByKey($key,'GET '.$key);
  if ($r === NULL) {return FALSE;}
  return $plain?$r:json_decode($r,TRUE);
 }
 public function set($key,$value,$TTL = NULL)
 {
  if (!is_scalar($value)) {$value = json_encode($value);}
  $r = $this->requestByKey($key,'SET '.$key.' '.strlen($value)."\r\n".$value);
  if ($TTL !== NULL) {$this->expire($key,$TTL);}
  if ($r === NULL) {return FALSE;}
  return $r;
 }
 public function expire($key,$TTL = 0)
 {
  return $this->requestByKey($key,'EXPIRE '.$key.' '.$TTL);
 }
 public function getTTL($key)
 {
  return $this->requestByKey($key,'TTL '.$key);
 }
 public function add($key,$value,$TTL = 0)
 {
  if (!is_scalar($value)) {$value = json_encode($value);}
  $r = $this->requestByKey($key,'SETNX '.$key.' '.strlen($value)."\r\n".$value);
  if ($TTL > 0) {$this->expire($key,$TTL);}
  return $r;
 }
 public function replace($key,$value,$TTL = 0) // not complete atomic
 {
  if (!$this->exists($key)) {return FALSE;}
  $this->set($key,$value,$TTL);
  return TRUE;
 }
 public function sendEcho($server = NULL,$s)
 {
  return $this->requestByServer($server,'ECHO '.strlen($s)."\r\n".$s);
 }
 public function getMultiByKey($keys,$bykey)
 {
  return $this->getMulti($keys,$this->getConnectionByKey($bykey));
 }
 public function getMulti($keys,$byserver = NULL)
 {
  if ($byserver !== NULL)
  {
   $result = array();
   $values = $this->requestByServer($byserver,'MGET '.implode(' ',$keys));
   $i = 0;
   foreach ($keys as &$k) {$result[$k] = json_decode($values[$i++],TRUE);}
   return $result;
  }
  elseif (sizeof($this->servers) <= 0) {return $this->getMulti($keys,end(array_keys($this->servers)));}
  else
  {
   $result = array();
   $batch = array();
   foreach ($keys as $k)
   {
    $addr = $this->getConnectionByKey($k);
    if (!isset($batch[$addr])) {$batch[$addr] = array();}
    $batch[$addr][] = $k;
   }
   foreach ($batch as $s => $b) {$result = array_merge($result,$this->getMulti($b,$s));}
   return $result;
  }
 }
 public function increment($key,$number = 1)
 {
  if ($number == 1) {return $this->requestByKey($key,'INCR '.$key);}
  return $this->requestByKey($key,'INCRBY '.$key.' '.$number);
 }
 public function decrement($key,$number = 1)
 {
  if ($number == 1) {return $this->requestByKey($key,'DECR '.$key);}
  return $this->requestByKey($key,'DECRBY '.$key.' '.$number);
 }
 public function exists($key)
 {
  return $this->requestByKey($key,'EXISTS '.$key);
 }
 public function delete($key)
 {
  return $this->requestByKey($key,'DEL '.$key);
 }
 public function type($key)
 {
  return $this->requestByKey($key,'TYPE '.$key);
 }
 public function keys($pattern,$server = '')
 {
  if ($server === '') {$r = $this->requestByKey($pattern,'KEYS '.$pattern);}
  else {$r = $this->requestByServer($server,'KEYS '.$pattern);}
  return explode(' ',$r);
 }
 public function randomKey($server = NULL)
 {
  return $this->requestByServer($server,'RANDOMKEY');
 }
 public function rename($key,$newkey)
 {
  // need multi-server support
  return $this->requestByKey($key,'RENAME '.$key.' '.$newkey);
 }
 public function renamenx($key,$newkey)
 {
  // need multi-server support
  return $this->requestByKey($key,'RENAMENX '.$key.' '.$newkey);
 }
 public function push($key,$value,$right = TRUE)
 {
  return $this->requestByKey($key,($right?'RPUSH':'LPUSH').' '.$key.' '.strlen($value)."\r\n".$value);
 }
 public function lpush($key,$value)
 {
  return $this->push($key,$value,FALSE);
 }
 public function rpush($key,$value)
 {
  return $this->push($key,$value,TRUE);
 }
 public function cpush($maxsize,$key,$value,$right = TRUE)
 {
  return $this->requestByKey($key,($right?'CRPUSH':'CLPUSH').' '.$maxsize.' '.$key.' '.strlen($value)."\r\n".$value);
 }
 public function clpush($maxsize,$key,$value)
 {
  return $this->cpush($maxsize,$key,$value,FALSE);
 }
 public function crpush($maxsize,$key,$value)
 {
  return $this->cpush($maxsize,$key,$value,TRUE);
 }
 public function ltrim($key,$start,$end)
 {
  return $this->requestByKey($key,'LTRIM '.$key.' '.$start.' '.$end);
 }  
 public function lindex($key,$index)
 {
  return $this->requestByKey($key,'LINDEX '.$key.' '.$index);
 }
 public function pop($key,$right = TRUE)
 {
  return $this->requestByKey($key,($right?'RPOP':'LPOP').' '.$key);
 }
 public function lpop($key)
 {
  return $this->pop($key,FALSE);
 }
 public function rpop($key)
 {
  return $this->pop($key,TRUE);
 }
 public function llen($key)
 {
  return $this->requestByKey($key,'LLEN '.$key);
 }
 public function lrange($key,$start,$end)
 {
  return $this->requestByKey($key,'LRANGE '.$key.' '.$start.' '.$end);
 }
 public function sort($key,$query = NULL)
 {
  return $this->requestByKey($key,'SORT '.$key.($query === NULL?'':' '.$query));
 }
 public function lset($key,$value,$index)
 {
  return $this->requestByKey($key,'LSET '.$key.' '.$index.' '.strlen($value)."\r\n".$value);
 }
 public function lrem($key,$count,$value)
 {
  return $this->requestByKey($key,'LREM '.$key.' '.$count.' '.strlen($value)."\r\n".$value);
 }
 
 public function sadd($key,$value)
 {
  return $this->requestByKey($key,'SADD '.$key.''.strlen($value)."\r\n".$value);
 }
 public function srem($key,$value)
 {
  return $this->requestByKey($key,'SREM '.$key.''.strlen($value)."\r\n".$value);
 }
 public function sismember($key,$value)
 {
  return $this->requestByKey($key,'SISMEMBER '.$key.''.strlen($value)."\r\n".$value);
 }
 public function sinter($keys,$bykey)
 {
  return $this->requestByKey($bykey,'SINTER '.implode(' ',$keys));
 }
 public function sinterstore($keys,$bykey)
 {
  return $this->requestByKey($bykey,'SINTERSTORE '.$bykey.' '.implode(' ',$keys));
 }
 public function sismembers($key)
 {
  return $this->requestByKey($key,'SMEMBERS '.$key);
 }
 public function scard($key)
 {
  return $this->requestByKey($key,'SCARD '.$key);
 }
 public function smove($srckey,$dstkey,$member)
 {
  return $this->requestByKey($key,'SMOVE '.$srckey.' '.$dstkey.' '.$member);
 }
 public function selectdb($dbname,$server = '*')
 {
  return $this->requestByServer($server,'SELECT '.$dbname);
 }
 public function move($key,$dbname)
 {
  return $this->requestByKey($key,'MOVE '.$key.' '.$dbname);
 }
 public function save($bg = FALSE,$server = '*')
 {
  return $this->requestByServer($server,($bg?'BGSAVE':'SAVE'));
 }
 public function lastsave($server = '*')
 {
  return $this->requestByServer($server,'LASTSAVE');
 }
 public function flush($all = FALSE,$server = '*')
 {
  return $this->requestByServer($server,($all?'FLUSHALL':'FLUSHDB'));
 }
 public function info($server = '*')
 {
  $r = $this->requestByServer($server,'INFO');
  if ($server !== '*') {$r = array($server => $r);}
  $result = array();
  foreach ($r as $srv => $reply)
  {
   $info = array();
   foreach (explode("\r\n",$reply) as $l)
   {
    if ($l === '') {continue;}
    list($k,$v) = explode(':',$l,2);
    $_v = strpos($v, '.') !== false ? (float)$v : (int)$v;
    $info[$k] = (string)$_v == $v ? $_v : $v;
   }
   $result[$srv] = $info;
  }
  return $result;
 }
}
