<?php
include dirname(__FILE__).'/init.php';
header('Content-Type: text/plain');
echo "Running unique-job.\n";
$object = $memcache->SharedInteger('myinteger',0,10);
echo 'Counter: '.$object->increment()."\n";