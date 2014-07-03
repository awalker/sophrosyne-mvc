<?php

namespace Base;

class ActiveModel extends Model {
  static public $_active_field = 'is_active';
    /*
       ______________  ________________
      / ___/_  __/   |/_  __/  _/ ____/
      \__ \ / / / /| | / /  / // /
     ___/ // / / ___ |/ / _/ // /___
    /____//_/ /_/  |_/_/ /___/\____/

   */
  public static function deleteById($id) {
    try {
      $obj = static::findById($id);
      if(!$obj) return false;
      $field = static::$_active_field;
      $obj->$field = 0;
      return $obj->update();
    } catch(\Exception $e) {
      return false;
    }
  }

}
