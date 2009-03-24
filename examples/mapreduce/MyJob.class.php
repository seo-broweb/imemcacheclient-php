<?php
class MyJob extends MapReduce_Job
{
 public $masterdone;
 public $masterfp;
 public $path; // master
 public $instanceName; // master
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
 public function defInstanceName()
 {
  if ($this->instanceName === NULL) {$this->instanceName = sprintf('%x',crc32($this->path));}
 }
 public function masterIteration()
 {
  if ($this->masterdone) {return FALSE;}
  if ($this->masterfp === NULL) {$this->masterfp = fopen($this->path,'r');}
  $this->defInstanceName();
  if (($line = fgets($this->masterfp)) !== FALSE)
  {
   $id = $this->input($this->instanceName,$line);
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
   foreach ($a as &$s)
   {
    if ($s === '') {continue;}
    list ($k,$v) = explode("\t",$s);
    if (!isset($object->obj->table[$k])) {$object->obj->table[$k] = (int) $v;}
    else {$object->obj->table[$k] += (int) $v;}
   }
   $object->flush();
  }
 }
}
