<?php
include dirname(__FILE__).'/init.php';
echo "Getting entry 'test'.\n";
var_dump($entry->memcache->deleteTag('tag1'));