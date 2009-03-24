<?php
include dirname(__FILE__).'/init.php';
echo "[masterd] started.\n";
while (TRUE)
{
 if (!$id = $mapreduce->masterIteration())
 {
  echo "[masterd] nothing to do sleep.\n";
  sleep(1);
 }
 else {echo "[masterd] iteration done (id = ".$id.").\n";}
}
