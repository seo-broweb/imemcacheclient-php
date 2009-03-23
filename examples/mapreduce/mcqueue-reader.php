<?php
include dirname(__FILE__).'/init.php';
echo "Running cycle reader. Strategy ".$queue->strategy.".\n";
$fails = 0;
while (TRUE)
{
 if ($value = $queue->getNext())
 {
  echo 'Fetched item with id = '.$queue->lastItemId.' (value = '.$value.").\n";
 }
 else
 {
  echo "No new items queued.\n";
  if ($queue->strategy >= 2) {usleep(500000);}
  elseif ($queue->strategy == 1)
  {
   ++$fails;
   if ($fails > 5)
   {
    echo "Checking for dead streams.\n";
    $queue->checkDeadStreams();
    $fails = 0;
   }
  }
 }
 if (!$queue->passedCorrupted)
 {
  if ($queue->strategy == 1) {usleep(500000);}
 }
}