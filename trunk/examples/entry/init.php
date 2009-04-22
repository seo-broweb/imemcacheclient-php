<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../../IMemcacheClient.class.php';
$memcache = new IMemcacheClient;
$memcache->addServer('127.0.0.1','11211');
$memcache->trace = TRUE;
//function shutdown() {global $memcache; var_dump($memcache->trace_stack);}
//register_shutdown_function('shutdown');
$entry = $memcache->Entry('test',3600);
$entry->setTags(array('tag1','tag2'));
