<?php
class IMemcacheClient_Collection
{
 public $id;
 public $memcache;
 public $lock;
 public $obj;
 public $TTL;
 public $interval = 0.3;
 public $repeats = 10;
 public $rewritable = TRUE;
 public function __construct($memcache,$id,$TTL = NULL,$rewritable = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  if ($rewriteable === NULL) {$rewriteable = TRUE;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
  $this->rewriteable = $rewriteable;
  $this->lock = $this->memcache->Lock('shc.'.$this->id,$this->TTL,$this->repeats,$this->interval);
 }
}
