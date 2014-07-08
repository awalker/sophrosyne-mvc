<?php
namespace Base;
$IN_VIEW = null;
class View {
  public static $directory = NULL;

  public static $ext = '.php';

  private $__view = NULL;

  /**
   * Returns a new view object for the given view.
   *
   * @param string $file the view file to load
   * @param string $module name (blank for current theme)
   */
  public function __construct($file, $values = null)
  {
    $this->__view = $file;
    if(!is_null($values)) {
      $this->set((array)$values);
    }
  }


  /**
   * Set an array of values
   *
   * @param array $array of values
   */
  public function set($array)
  {
    foreach($array as $k => $v)
    {
      if(strpos($k, "\\") === false) {
        $this->$k = $v;
      }
    }
    return $this;
  }

  public function getViewFilename() {
    return static::$directory . $this->__view . static::$ext;
  }


  /**
   * Return the view's HTML
   *
   * @return string
   */
  public function __toString()
  {
    try {
      return $this->render();
    }
    catch(\Exception $e)
    {
      Error::exception($e);
      return '';
    }
  }

  public function process($clean) {
    $pattern = '/\{\{(.+?)\}\}/';
    return preg_replace_callback($pattern, array($this, 'processSingle'), $clean);
  }

  public function processSingle($matches) {
    $parts = explode(' ', trim($matches[1]));
    $path = $parts[0];
    if($parts[0] == 'for') {
      return $this->processFor($matches, $parts);
    }elseif ($parts[0] == 'partial') {
      return $this->processPartial($matches, $parts);
    }
    $processor = 'h';
    $out = getFromContext($this, $path);
    if (is_object($out) && is_a($out, 'Base\View')) {
      return (string)$out;
    }
    return $processor($out);
  }

  public function processPartial($matches, $parts) {
    $view = getFromContext($this, array_pop($parts));
    $that = $this;
    if(count($parts) > 1) {
      $that = getFromContext($this, array_pop($parts));
    }
    $view->set((array)$that);
    if(property_exists($this, 'controller')) {
      $view->controller = $this->controller;
    }
    return (string)$view;
  }

  public function processFor($matches, $parts) {
    $collection = getFromContext($this, $parts[1]);
    $notFoundViewPath = null;
    if(count($parts) > 4) {
      $notFoundViewPath = array_pop($parts);
    }
    if(count($parts) > 4) { array_pop($parts); }
    $viewPath = array_pop($parts);
    $view = getFromContext($this, $viewPath);
    $rendered = array();
    if ($collection && is_object($view) && is_a($view, 'Base\View')) {
      foreach($collection as $item) {
        $view->set((array)$item);
        if(property_exists($this, 'controller')) {
          $view->controller = $this->controller;
        }
        $rendered[] = (string)$view;
      }
    } elseif($notFoundViewPath) {
      return (string)getFromContext($this, $notFoundViewPath);
    }
    return implode('', $rendered);
  }

  /**
   * Renders the view without trapping exceptions
   * @return [type] [description]
   */
  public function render()
  {
    if ($this->__view != 'system/exception' && $this->__view != 'system/error') {
      $GLOBALS['IN_VIEW'] = &$this;
    }
    ob_start();
    extract((array) $this);
    require $this->getViewFilename();
    $clean = ob_get_clean();

    return $this->process($clean);
  }


}