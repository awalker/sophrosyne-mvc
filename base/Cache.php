<?php

namespace Base;

class Cache {
  public $base;
  public $ttl;
  public $key;
  public $filename;
  public $exists = null;
  public $serialize = 'serialize';
  public $unserialize = 'unserialize';

  public function __constuct($key, $ttl = 300) {
    $this->ttl = $ttl;
    $this->key = $key;
    $this->base = sys_get_temp_dir() . '/' . snake_case(DOMAIN) . '/';
    if(!file_exists($this->base)) {
      mkdir($this->base);
    }
    $this->filename = static::getFilename($key);
  }

  public function useJSON() {
    $this->serialize = 'json_encode';
    $this->unserialize = 'json_decode';
  }

  public static function getFilename($key) {
    return $this->base . md5($key) . '.cache';
  }

  public function exists() {
    if(is_null($this->exists)) {
      $this->exists = file_exists($this->filename);
    }
    return $this->exists;
  }

  public function read() {
    $fn = $this->unserialize;
    return $fn(@file_get_contents($this->filename));
  }

  public function write($contents) {
    $fn = $this->serialize;
    file_put_contents($this->filename, $fn($contents));
  }

  public function invalidate() {
    $this->exists = null;
    $this->content = null;
    @unlink($this->filename);
  }

  public function isFresh() {
    if($this->exists()) {
      $now = time();
      $mtime = filemtime($filename);
      return $mtime < $now + $this->ttl;
    } else {
      return false;
    }
  }

  public function isStale() {
    return !$this->isFresh();
  }

}