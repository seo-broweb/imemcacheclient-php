<?php
setlocale(LC_ALL,'ru_RU.UTF-8');
include dirname(__FILE__).'/../../IMemcacheClient.class.php';
$memcache = new IMemcacheClient('redis');
$memcache->addServer('127.0.0.1');
$memcache->prefix = 'test.';
$memcache->trace = TRUE;
// filling Users
for ($i = 1; $i <= 10; ++$i)
{
 $memcache->set('users.'.$i,array('username' => 'user-number-'.$i));
}
// filling Messages
for ($i = 1; $i <= 100; ++$i)
{
 $memcache->set('messages.'.$i,array('uid' => mt_rand(1,10), 'message' => 'message-number-'.$i));
}
// joining...
$list = $memcache->getList('messages.*')
					->join('users.${uid} AS user','left')
					->getArray();
var_dump($list);
