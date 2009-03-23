<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../IMemcacheClient.php';
include dirname(__FILE__).'/MapReduce.class.php';
$queue = xE::$memcache->queue('test',TRUE,3600);
$queue->addServer('127.0.0.1','11211');
$queue->strategy = 1;
$mapreduce = new MapReduce;
$mapreduce->memcache = xE::$memcache;
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