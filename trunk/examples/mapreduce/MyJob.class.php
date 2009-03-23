<?php
class MyJob extends MapReduce_Job
{
 public $masterdone;
 public $masterfp;
 public $path;
 public function init()
 {
  $this->path = dirname(__FILE__).'/example.txt';
  echo "Job started.\n";
 }
 public function getMapValue($key)
 {
  preg_match_all('~\S+~u',$key,$w);
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
  if ($this->masterdone) {return;}
  if ($this->masterfp === NULL) {$this->masterfp = fopen($this->path,'r');}
  if (($line = fgets($this->masterfp)) !== FALSE) {return $this->input($line);}
  $this->masterdone = TRUE;
  fclose($this->masterfp);
 }
 public function reduceIteration($key,$value)
 {
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
