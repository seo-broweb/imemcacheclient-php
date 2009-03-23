<?php
include dirname(__FILE__).'/IMemcacheClient.php';
$queue = xE::$memcache->queue('test',TRUE,3600);
$queue->addServer('127.0.0.1','11211');
$queue->strategy = 1;
