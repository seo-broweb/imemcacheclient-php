<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../,./IMemcacheClient.php';
include dirname(__FILE__).'/../../IMemcacheClient_Queue.class.php';
include dirname(__FILE__).'/../../MapReduce.class.php';
include dirname(__FILE__).'/../../MapReduce_Job.class.php';
include dirname(__FILE__).'/MyJob.class.php';
$memcache = new IMemcacheClient;
$mapreduce = new MapReduce($memcache);
$job = $mapreduce->addJob(new MyJob);
