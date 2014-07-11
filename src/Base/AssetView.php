<?php
namespace Base;
class AssetView {
  public static $directory = NULL;

  public static $ext = '.js';
  public static $mime = 'text/plain';
  public static $prefix = '{{';
  public static $suffix = '}}';

  protected $__view = NULL;
  protected $__template = NULL;
  protected $__rendered = NULL;
  protected $values = array();

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
    $this->__rendered = null;
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
      var_dump($e);
      return '';
    }
  }

  /**
   * Renders the view without trapping exceptions
   * @return [type] [description]
   */
  public function render()
  {
    if(!is_null($this->__rendered)) return $this->__rendered;
    if(!$this->__template) {
      $this->__template = file_get_contents($this->getViewFilename());
    }
    $s = $this->__template;
    if($this->values && count($this->values)) {
      foreach ($this->values as $key => $value) {
        $needle = static::$prefix . $key . static::$prefix;
        $s = str_replace($needle, $value, $s);
      }
    }
    $this->__rendered = $s;
    return $this->__rendered;
  }


}