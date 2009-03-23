<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../IMemcacheClient.php';
include dirname(__FILE__).'/../IMemcacheClient_Queue.class.php';
include dirname(__FILE__).'/../IMemcacheClient_MapReduce.class.php';
$memcache = new IMemcacheClient;
$mapreduce = $memcache->MapReduce('test');
function mapcallback()
{
 preg_match_all('~\S+~u',$key,$w);
 $c = array();
 //var_dump(array('preg',$key,$w[0]));
 foreach ($w[0] as $v)
 {
  if (!isset($c[$v])) {$c[$v] = 1;}
  else {++$c[$v];}
 }
 $s = '';
 foreach ($c as $k => $v) {$s .= ($s !== ''?"\n":'').$k."\t".$v;}
 return $s;
}
$mapreduce->mapcallback = 'mapcallback';