<?php
include dirname(__FILE__).'/init.php';

class MapReduce
{
 public $mapqueue;
 public $reducequeue;
 public $memcache;
 pubic $id = 'test';
 public __construct()
 {
  $this->memcache = new IMemcacheClient;
  $this->mapqueue = $this->memcache->queue('mr.'.$this->id.'.m');
  $this->reducequeue = $this->memcache->queue('mr.'.$this->id.'.r');
 }
 public function 
}

echo "Running writer. Strategy ".$queue->strategy.".\n";
for ($i = 0; $i < 100; ++$i)
{
 $id = $queue->push(str_repeat($i,1024));
 echo 'Pushed item with id = '.$id.".\n";
 flush();
 sleep(1);
}
