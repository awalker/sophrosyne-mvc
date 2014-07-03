<?php
namespace Base;
/**
 * Sometimes helps with debugging
 */
class NullView extends View {

  /**
   * Returns a new view object for the given view.
   *
   * @param string $file the view file to load
   * @param string $module name (blank for current theme)
   */
  public function __construct($file, $values = null)
  {
  }


  /**
   * Set an array of values
   *
   * @param array $array of values
   */
  public function set($array)
  {
  }


  /**
   * Return the view's HTML
   *
   * @return string
   */
  public function __toString()
  {
    return '';
  }

}