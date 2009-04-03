<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../../IMemcacheClient.class.php';
$memcache = new IMemcacheClient;
$memcache->addServer('127.0.0.1','11211');
