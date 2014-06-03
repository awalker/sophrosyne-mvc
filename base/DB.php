<?php
namespace Base;

class DB
{
  public $pdo;
  public $prefix;
  public static $queries = array();
  public static $lastSql;

  public function DB($pdo, $prefix = '') {
    $this->pdo = $pdo;
    $this->prefix = $prefix;
  }

  public function __call($name, $args) {
    return call_user_func_array(array($this->pdo, $name), $args);
  }

  public function prepare($sql2, $options = array()) {
    $sql = $this->prefix . $sql2;
    static::$lastSql = $sql;
    if (array_key_exists($sql, static ::$queries)) {
      $info = static ::$queries[$sql];
    } else {
      $info = array($sql, 0);
    }
    static::$queries[$sql] = $info;
    return $this->pdo->prepare($sql, $options);
  }
}
