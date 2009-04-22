<?php
/*
    @class IMemcacheClient_Entry
    @package IMemcacheClient
    @author kak.serpom.po.yaitsam@gmail.com
*/
class IMemcacheClient_Entry
{
 public $id;
 public $memcache;
 public $TTL;
 public $tags = array();
 /*
    @method __construct
    @param object $memcache parent object.
    @param string $id ID of entry.
    @param string $TTL time to timelife.
    @description set option.
    @return object.
 */
 public function __construct($memcache,$id,$TTL = NULL)
 {
  if ($TTL === NULL) {$TTL = 0;}
  $this->memcache = $memcache;
  $this->id = $id;
  $this->TTL = $TTL;
 }
 public function get()
 {
  return $this->memcache->get($this->id.$this->getTagsID());
 }
 public function set($value)
 {
  return $this->memcache->set($this->id.$this->getTagsID(),$value,$this->TTL);
 }
 public function getTagsID()
 {
  if (sizeof($this->tags) == 0) {return '';}
  $p = 'tag.';
  $keys = array();
  foreach ($this->tags as &$v) {$keys[] = $p.$v;}
  $kk = $this->memcache->getMulti($keys);
  foreach ($this->tags as &$v)
  {
   if (!isset($kk[$p.$v]))
   {
    if (!$this->memcache->add($p.$v,$kk[$p.$v] = microtime(TRUE)))
    {
     $kk[$p.$v] = $this->memcache->get($p.$v);
    }
   }
  }
  return '*'.sprintf('%x',crc32(implode('|',$kk)));
 }
 public function delete()
 {
  return $this->memcache->delete($this->id);
 }
 public function setTags($tags)
 {
  $this->tags = $tags;
 }
 public function removeTags($tags)
 {
  $this->tags = array_diff($this->tags,$tags);
 }
 public function getTags($tags)
 {
  return $this->tags;
 }
}
