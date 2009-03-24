<?php
include dirname(__FILE__).'/init.php';
header('Content-Type: text/plain');
echo "Running unique-job.\n";
$object = $memcache->SharedObject('myobject',300);
$object->lock->time = 3;
$object->lock->repeats = -1;

if ($status = $object->fetchInter())
{
 if ($status == 2)
 {
  $object->obj = new StdClass;
  $object->obj->counter = 1;
  $object->obj->lastPid = getmypid();
  $object->obj->lastMtime = time();
  $object->flush();
 }
 //elseif (time()-10 > $object->obj->lastMtime)
 else
 {
  if ($object->fetchWrite())
  {
   ++$object->obj->counter;
   $object->obj->lastPid = getmypid();
   $object->obj->lastMtime = time();
   $object->flush();
  }
 }
 var_dump($object->obj);
}
else {echo "Couldn't fetch object or acquire the mutex.\n";}
