<?php
define('SP', './tests/');
require('vendor/autoload.php');

use Base\TemplateAssetView;

$v = new TemplateAssetView('template', null);
echo $v->render();

echo "\n\n=============\n\n";
$v = new TemplateAssetView('complex', null);
echo $v->render(1);
// var_dump($v->process('hi {{name}}! I say foo, you say {{ params.foo }} my fav steve is {{params.steve}}. Rand is {{rand.get}}. This should not be found {{not.found}}'));

// var_dump($v->process('Test follows: {{for items view testView}}'));
// var_dump($v->process('Test follows: {{for items2 view testView notfound notFoundView}}'));
// var_dump($v->process('partial test: {{partial testView}}'));
// var_dump($v->process('partial 2 test: {{partial june testView}}'));
// var_dump($v->process('ifpartial test: {{ifpartial name testView}}'));
// var_dump($v->process('ifpartial 2 test: {{ifpartial nope testView}}'));
// var_dump($v->process('ifpartial 3 test: {{ifpartial name testViewNotThere}}'));
// var_dump($v->process('filter control test: {{enabled}} {{STOP FILTERING}} {{enabled}} {{START FILTERING}} {{enabled}}'));
// var_dump($v->process('processor test: {{dollar|amt}}'));