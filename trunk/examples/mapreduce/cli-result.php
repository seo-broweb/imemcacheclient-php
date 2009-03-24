<?php
include dirname(__FILE__).'/init.php';
echo "Simple script for monitoring results.\n";
$job = $mapreduce->getJobByName('MyJob');
$job->defInstanceName();
$object = $job->getReducedObject($job->instanceName);
$object->fetch();
natsort($object->obj->table);
foreach ($object->obj->table as $key => $value)
{
 echo $key.' = '.$value."\n";
}