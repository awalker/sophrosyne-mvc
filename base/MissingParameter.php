<?php
namespace Base;

class MissingParameter extends \Exception {
  public $url;
  public $params;
  function __construct($message, $url, $params = null) {
    $this->url = $url;
    $this->params = $params;
    parent::__construct($message);
  }
}