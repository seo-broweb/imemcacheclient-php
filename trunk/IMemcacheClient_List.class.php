<?php
/*
    @class IMemcacheClient_List
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_List
{
 public $id;
 public $memcache;
 public $list = NULL;
 public $TTL;
 public function __construct($memcache,$id,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  if ($rewriteable === NULL) {$rewriteable = TRUE;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
 }
 public function fetch($offset = NULL,$limit = NULL)
 {
  if (strpos($this->id,'*') !== FALSE)
  {
   $keys = $this->memcache->keys($this->id);
   $this->list = array();
   foreach ($keys as &$k) {$this->list[$k] = $this->memcache->get($k);}
  }
  return $this;
 }
 public function join($id,$type = 'left')
 {
  if ($this->list === NULL) {$this->fetch();}
  $e = explode(' ',$id,2);
  $id_pattern = trim($e[0]);
  $extra = explode(' ',trim($e[1]));
  if (($extra[0] == 'AS') && isset($extra[1])) {$as = $extra[1];}
  else {$as = FALSE;}
  $tr = array();
  $sp = 0;
  while ((($sp = strpos($id_pattern,'${',$sp+1)) !== FALSE) && (($ep = strpos($id_pattern,'}')) !== FALSE) && ($ep > $sp))
  {
   $tr[substr($id_pattern,$sp,$ep-$sp+1)] = '';
  }
  $rightkeys = array();
  $leftright = array();
  foreach ($this->list as $lk => &$item)
  {
   foreach ($tr as $k => &$v) {$v = $item[substr($k,2,-1)];}
   $rk = strtr($id_pattern,$tr);
   if (!in_array($rk,$rightkeys)) {$rightkeys[] = $rk;}
   $leftright[$lk] = $rk;
  }
  $rightget = $this->memcache->getMulti($rightkeys);
  foreach ($this->list as $lk => &$item)
  {
   $v = isset($rightget[$kk = $leftright[$lk]])?$rightget[$kk]:FALSE;
   if ($v === FALSE)
   {
    if ($type == 'inner') {unset($this->list[$lk]);}
    elseif ($type == 'left') {if ($as !== FALSE) {$item[$as] = NULL;}}
    continue;
   }
   if ($as !== FALSE) {$item[$as] = $v;}
   else {$item = array_merge($item,$v);}
  }
  return $this;
 }
 public function getArray()
 {
  return $this->list;
 }
}
