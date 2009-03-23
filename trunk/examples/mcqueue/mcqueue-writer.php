<?php
include dirname(__FILE__).'/init.php';

echo "Running writer. Strategy ".$queue->strategy.".\n";
for ($i = 0; $i < 100; ++$i)
{
 $id = $queue->push(str_repeat($i,1024));
 echo 'Pushed item with id = '.$id.".\n";
 flush();
 sleep(1);
}
