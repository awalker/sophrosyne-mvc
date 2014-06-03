<?php
namespace Base;

class NotFound extends \Exception {
  function __construct($message = "Page") {
    $ref = '';
    if($_SERVER && array_key_exists('HTTP_REFERER', $_SERVER)) {
      $ref = ' Referrer ' . $_SERVER['HTTP_REFERER'];
    }
    parent::__construct($message . ' was not found.' . $ref);
  }
}