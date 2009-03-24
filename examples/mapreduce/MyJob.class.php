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
   $id = $this->input($line);
   return $id;
  }
  $this->masterdone = TRUE;
  fclose($this->masterfp);
  return TRUE;
 }
 public function reduceIteration($key,$value)
 {
 var_dump($value);
  $a = explode("\n",$value);
  foreach ($a as &$s)
  {
   if ($s === '') {continue;}
   list ($k,$v) = explode("\t",$s);
   if (!isset($this->reduceResult[$k])) {$this->reduceResult[$k] = 0;}
   $this->reduceResult[$k] += (int) $v;
  }
 }
}