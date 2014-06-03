<?php
namespace Sophrosyne\Controller;

class Asset extends \Base\Controller {
  public static $mapping = array(
    'scripts' => '\Base\JavascriptAssetView',
    'scriptMap' => '\Base\SourceMapAssetView'
  );
  public function run() {
    $args = func_get_args();
    $section = array_shift($args);
    $fullfilename = array_pop($args);
    $args[] = preg_replace('/\.[a-z]+$/i', '', $fullfilename);
    $path = implode('/', $args);

    if (!array_key_exists($section, static::$mapping)) {
      $this->content = $section;
      // return $this->show_404();
    }

    // Exception ... source maps
    if (preg_match('/\.map$/', $fullfilename)) {
      $section = 'scriptMap';
      if (isProduction()) {
        return $this->content = '{}';
      }
    }

    $viewClass = static::$mapping[$section];
    header('Content-Type: ' . $viewClass::$mime);

    $view = new $viewClass($path);
    echo $view->render();
  }
}