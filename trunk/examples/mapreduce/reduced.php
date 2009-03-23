<?php
include dirname(__FILE__).'/init.php';
echo "[reduced] started.\n";
while (TRUE)
{
 if (!$mapreduce->reduceIteration())
 {
  echo "[reduced] nothing to do sleep.\n";
  sleep(1);
 }
}
