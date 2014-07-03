<?php
namespace Base;
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

  /**
   * Renders the view without trapping exceptions
   * @return [type] [description]
   */
  public function render()
  {
    if ($this->__view != 'system/exception') {
      $GLOBALS['IN_VIEW'] = $this;
    }
    ob_start();
    extract((array) $this);
    require $this->getViewFilename();
    return ob_get_clean();
  }


}