<?php
class MyJob extends MapReduce_Job
{
 public $masterdone;
 public $masterfp;
 public $path;
 public $name = 'MyJob';
 public function __construct()
 {
  $this->path = dirname(__FILE__).'/example.txt';
  echo "Job 'MyJob' started.\n";
 }
 public function getMapValue($key)
 {
  preg_match_all('~\S+~u',$key,$w);
  $c = array();
  foreach ($w[0] as $v)
  {
   if (!isset($c[$v])) {$c[$v] = 1;}
   else {++$c[$v];}
  }
  $s = '';
  foreach ($c as $k => $v) {$s .= ($s !== ''?"\n":'').$k."\t".$v;}
  return $s;
 }
 public function masterIteration()
 {
  if ($this->masterdone) {return FALSE;}
  if ($this->masterfp === NULL) {$this->masterfp = fopen($this->path,'r');}
  if (($line = fgets($this->masterfp)) !== FALSE)
  {
   $id = $this->input(sprintf('%x',crc32($this->path)),$line);
   return $id;
  }
  $this->masterdone = TRUE;
  fclose($this->masterfp);
  return TRUE;
 }
 public function reduceIteration($instance, $key, $value)
 {
  $a = explode("\n",$value);
  $object = $this->getReducedObject($instance);
  while (!$status = $object->fetchInter()) {sleep(1);}
  if ($status == 2)
  {
   $object->obj = new StdClass;
   $object->obj->table = array();
   $object->flush();
  }
  if ($object->fetchWrite())
  {
   $o = $object->obj->table;
   foreach ($a as &$s)
   {
    if ($s === '') {continue;}
    list ($k,$v) = explode("\t",$s);
    if (!isset($o[$k])) {$o[$k] = (int) $v;}
    else {$o[$k] += (int) $v;}
   }
   $object->flush();
  }
 }
}
