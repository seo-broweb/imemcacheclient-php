<?php
include dirname(__FILE__).'/init.php';
echo "Running unique-job.\n";
$object = $memcache->SharedObject('myobject',300);
$object->lock->time = 20;
$object->lock->timeout = 0;
$object->lock->repeats = 0;
if ($object->lock() && )
{
 $object->flush();
}
else
{
 echo "Couldn't acquire the mutex. Quit...\n";
}
