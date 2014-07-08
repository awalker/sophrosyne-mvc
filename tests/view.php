<?php
require('util/common.php');
require('src/Base/View.php');

class Rand {
  public function get() {
    return rand();
  }
};

$obj = new \stdClass();
$a = array('foo'=>'bar', 'steve'=>'dula');
$obj->name = 'Adam';
$obj->params = $a;
$obj->rand = new Rand();

$v = new \Base\View('', $obj);

var_dump($v->process('hi {{name}}! I say foo, you say {{params.foo}} my fav steve is {{params.steve}}. Rand is {{rand.get}}.'));