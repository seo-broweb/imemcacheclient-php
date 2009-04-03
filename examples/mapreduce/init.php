<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../../IMemcacheClient.class.php';
include dirname(__FILE__).'/../../MapReduce.class.php';
include dirname(__FILE__).'/../../MapReduce_Job.class.php';
include dirname(__FILE__).'/MyJob.class.php';
$memcache = new IMemcacheClient;
$memcache->addServer('81.177.151.81','11211');
$mapreduce = new MapReduce($memcache,'myfirstcluster');
$job = $mapreduce->addJob(new MyJob);
