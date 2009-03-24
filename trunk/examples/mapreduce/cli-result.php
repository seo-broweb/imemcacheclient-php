<?php
include dirname(__FILE__).'/init.php';
echo "Simple script for monitoring results.\n";
$job = $mapreduce->getJobByName('MyJob');
$job->defInstanceName();
$object = $job->getReducedObject($job->instanceName);
$object->fetch();
var_dump($object->obj);