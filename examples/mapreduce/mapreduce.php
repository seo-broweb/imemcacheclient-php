<?php
class MapReduce
{
 public $mapqueue;
 public $reducequeue;
 public $memcache;
 public $id = 'test';
 public $reduceResult = array();
 public $masterfp;
 public $masterdone = FALSE;
 public function __construct()
 {
  $this->memcache = new IMemcacheClient;
  $this->memcache->addServers(array(array('81.177.151.81','11211')));
  $this->mapqueue = $this->memcache->queue('mr.m.'.$this->id,TRUE,3600);
  $this->reducequeue = $this->memcache->queue('mr.r.'.$this->id,TRUE,3600);
 }
 public function getMapValue($key) {return call_user_func($this->mapcallback,$key);}
 public function input($string) {return $this->mapqueue->push($string);}
 public function masterIteration()
 {
  if ($this->masterdone) {return;}
  if ($this->masterfp === NULL) {$this->masterfp = fopen(dirname(__FILE__).'/rules.txt','r');}
  if (($line = fgets($this->masterfp)) !== FALSE) {return $this->input($line);}
  $this->masterdone = TRUE;
  fclose($this->masterfp);
 }
 public function mapIteration()
 {
  $key = $this->mapqueue->getNext();
  if ($key)
  {
   $value = $this->getMapValue($key);
   //var_dump(array('mapIteration',$key,$value));
   $json = json_encode(array($key,$value));
   $this->reducequeue->push($json);
   return TRUE;
  }
  return FALSE;
 }
 public function reduceIteration()
 {
  if ($item = $this->reducequeue->getNext())
  {
   list ($key, $value) = json_decode($item);
   $a = explode("\n",$value);
   foreach ($a as &$s)
   {
    if ($s === '') {continue;}
    list ($k,$v) = explode("\t",$s);
    if (!isset($this->reduceResult[$k])) {$this->reduceResult[$k] = 0;}
    $this->reduceResult[$k] += (int) $v;
   }
   return TRUE;
  }
  return FALSE;
 }
}
