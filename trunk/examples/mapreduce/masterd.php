<?php
include dirname(__FILE__).'/init.php';
while (TRUE)
{
 echo "[masterd] started.\n";
 if (!$mapreduce->masterIteration())
 {
  echo "[masterd] nothing to do sleep.\n";
  sleep(1);
 }
}
