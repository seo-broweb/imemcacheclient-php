<?php
include dirname(__FILE__).'/init.php';

class MapReduce
{
 public $mapqueue;
 public $reducequeue;
 public $memcache;
 public $id = 'test';
 public $reduceResult = array();
 public $mapResult = array();
 public $masterfp;
 public $masterdone = TRUE;
 public function __construct()
 {
  $this->memcache = new IMemcacheClient;
  $this->mapqueue = $this->memcache->queue('mr.m.'.$this->id,TRUE,3600);
  $this->reducequeue = $this->memcache->queue('mr.r.'.$this->id,TRUE,3600);
 }
 public function getMapValue($key)
 {
  return 1;
  //if (!isset($this->mapResult[$key])) {$this->mapResult[$key] = 0;}
  //++$this->mapResult[$key];
  //return $value;
 }
 public function input($string)
 {
  $this->mapqueue->push($string);
 }
 public function masterIteration()
 {
  if ($this->masterdone) {return;}
  if ($this->masterfp === NULL) {$this->masterfp = fopen('rules.txt','r');}
  if (($line = fgets($fp)) !== FALSE) {return $this->input($string);}
  $this->masterdone = TRUE;
  fclose($this->masterfp);
 }
 public function mapIteration()
 {
  if ($key = $this->mapqueue->getNext())
  {
   $this->reducequeue->push(json_encode(array($key,$this->getMapValue($key))));
   return TRUE;
  }
  return FALSE;
 }
 public function reduceIteration()
 {
  if ($item = $this->reducequeue->getNext())
  {
   list ($key, $value) = json_decode($item);
   if (!isset($this->reduceResult[$key])) {$this->reduceResult[$key] = 0;}
   $this->reduceResult[$key] += (int) $value;
   return TRUE;
  }
  return FALSE;
 }
}
$mapreduce = new MapReduce;
$mapreduce->memcache = xE::$memcache;
while (TRUE)
{
 $this->masterIteration();
 if ((!$this->mapIteration()) && (!$this->reduceIteration()))
 {
  break;
 }
}
var_dump($this->reduceResult);
