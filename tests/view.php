<?php
require('vendor/autoload.php');

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
$obj->items = array(array('name'=>'1'), array('name'=>'2'), array('name'=>'3'));

$v = new \Base\View('doesnotexist', $obj);

$vs = new \Base\View('tests/template', $obj);
$v->testView = $vs;
$v->notFoundView = new \Base\View('tests/notfound', $obj);

var_dump($v->process('hi {{name}}! I say foo, you say {{ params.foo }} my fav steve is {{params.steve}}. Rand is {{rand.get}}. This should not be found {{not.found}}'));

var_dump($v->process('Test follows: {{for items view testView}}'));
var_dump($v->process('Test follows: {{for items2 view testView notfound notFoundView}}'));