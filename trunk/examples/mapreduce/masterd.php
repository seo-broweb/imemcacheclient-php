<?php
include dirname(__FILE__).'/init.php';
echo "[masterd] started.\n";
while (TRUE)
{
 $ids = $mapreduce->masterIteration();
 if (!sizeof($ids))
 {
  echo "[masterd] nothing to do sleep.\n";
  sleep(1);
 }
 else {echo "[masterd] iteration done (ids = ".implode(',',$ids).").\n";}
}
