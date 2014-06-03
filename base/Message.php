<?php

namespace Base;

class Message {
  public $error = array();
  public $success = array();
  public $warning = array();

  public function __construct($type, $msg) {
    $tmp = $this->$type;
    $tmp[] = $msg;
    $this->$type = $tmp;
  }

  public function __toString() {
    $out = '';
    if($this->error) {
      foreach($this->error as $item) {
        $out .= '<div class="alert alert-danger">' . h($item) . '</div>';
      }
    }
    if($this->warning) {
      foreach($this->warning as $item) {
        $out .= '<div class="alert alert-warning">' . h($item) . '</div>';
      }
    }
    if($this->success) {
      foreach($this->success as $item) {
        $out .= '<div class="alert alert-success">' . h($item) . '</div>';
      }
    }
    return $out;
  }
}