<?php
include dirname(__FILE__).'/init.php';
echo "[mapd] started.\n";
while (TRUE)
{
 if (!$mapreduce->mapIteration())
 {
  echo "[mapd] nothing to do. sleep.\n";
  sleep(1);
 }
}
