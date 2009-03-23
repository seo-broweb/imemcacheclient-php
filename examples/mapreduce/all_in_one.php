<?php
include dirname(__FILE__).'/init.php';
while (TRUE)
{
 $mapreduce->masterIteration();
 $m = $mapreduce->mapIteration();
 $r = $mapreduce->reduceIteration();
 if (!$m && !$r) {break;}
}
asort($mapreduce->reduceResult);
var_dump($mapreduce->reduceResult);
