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
$june = new \stdClass();
$june->name = "June";
$obj->june = $june;
$obj->items = array(array('name'=>'1'), array('name'=>'2'), array('name'=>'3'));

$v = new \Base\View('doesnotexist', $obj);

$vs = new \Base\View('tests/template', $obj);
$v->testView = $vs;
$v->notFoundView = new \Base\View('tests/notfound', $obj);

var_dump($v->process('hi {{name}}! I say foo, you say {{ params.foo }} my fav steve is {{params.steve}}. Rand is {{rand.get}}. This should not be found {{not.found}}'));

var_dump($v->process('Test follows: {{for items view testView}}'));
var_dump($v->process('Test follows: {{for items2 view testView notfound notFoundView}}'));
var_dump($v->process('partial test: {{partial testView}}'));
var_dump($v->process('partial 2 test: {{partial june testView}}'));
var_dump($v->process('ifpartial test: {{ifpartial name testView}}'));
var_dump($v->process('ifpartial 2 test: {{ifpartial nope testView}}'));
// var_dump($v->process('ifpartial 3 test: {{ifpartial name testViewNotThere}}'));