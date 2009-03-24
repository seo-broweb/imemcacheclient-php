<?php
include dirname(__FILE__).'/init.php';
echo "Running unique-job.\n";
$lock = $memcache->lock('uniqjob',300,10);
if ($lock->acquire())
{
 echo "We are unique! Sleep!\n";
 sleep(5);
 $lock->release();
 echo "We have done our unique job and have released lock!\n";
}
else
{
 echo "Couldn't acquire the mutex. Quit...\n";
}
