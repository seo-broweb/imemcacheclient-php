<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../IMemcacheClient.php';
include dirname(__FILE__).'/../IMemcacheClient_Queue.class.php';
$memcache = new IMemcacheClient;
$queue = $memcache->queue('test',TRUE,3600);
$queue->addServer('127.0.0.1','11211');
$queue->strategy = 1;
