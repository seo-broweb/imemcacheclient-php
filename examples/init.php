<?php
include dirname(__FILE__).'/IMemcacheClient.php';
$queue = xE::$memcache->queue('test',TRUE,3600);
$queue->strategy = 1;
