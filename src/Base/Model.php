<?php
namespace Base;

/**
 * Model class for database backed model object.
 *
 * When making your model classes add, at a minimum, a static property
 * for $_tablename. Declaring $_alias is a good idea if you will ever use the model
 * in joins. Declare $_id if your id field is named something other than "id". Use
 * $allowedFields to resist which model properties are saved to the database.
 */
class Model {
  /*
        ____                             __  _
       / __ \_________  ____  ___  _____/ /_(_)__  _____
      / /_/ / ___/ __ \/ __ \/ _ \/ ___/ __/ / _ \/ ___/
     / ____/ /  / /_/ / /_/ /  __/ /  / /_/ /  __(__  )
    /_/   /_/   \____/ .___/\___/_/   \__/_/\___/____/
                    /_/
   */
  protected static $pdo;
  public static $_tablename = null;
  public static $_alias = null;
  public static $_tablenames = array();
  public static $_aliases = array();
  public static $_tables = array();
  public static $_pk = 'id';
  public static $_pkIsGuid = false;

  public static $queries = array();
  public static $last_query;
  public static $allowedFields = null;

  /*
       ______                 __                  __
      / ____/___  ____  _____/ /________  _______/ /_____  _____
     / /   / __ \/ __ \/ ___/ __/ ___/ / / / ___/ __/ __ \/ ___/
    / /___/ /_/ / / / (__  ) /_/ /  / /_/ / /__/ /_/ /_/ / /
    \____/\____/_/ /_/____/\__/_/   \__,_/\___/\__/\____/_/

    FIXME: remove global $pdo dependency.
   */
  public function __construct($connection = null) {
    global $pdo;
    if(!$connection)
      static::$pdo = $pdo;
    if(static::$_pk != 'id' && static::$_pk) {
      $this->id = $this->getId();
    }
  }

  /*
        ____        __    ___         __  ___     __  __              __
       / __ \__  __/ /_  / (_)____   /  |/  /__  / /_/ /_  ____  ____/ /____
      / /_/ / / / / __ \/ / / ___/  / /|_/ / _ \/ __/ __ \/ __ \/ __  / ___/
     / ____/ /_/ / /_/ / / / /__   / /  / /  __/ /_/ / / / /_/ / /_/ (__  )
    /_/    \__,_/_.___/_/_/\___/  /_/  /_/\___/\__/_/ /_/\____/\__,_/____/

   */

  public function getId() {
    $pk = static::$_pk;
    return @$this->$pk;
  }

  public function setId($v) {
    $pk = static::$_pk;
    $this->$pk = $v;
  }

  public function __get($name) {
    if(method_exists($this, $name)) {
      return $this->$name();
    }
    $meth = 'get_' . $name;
    if (method_exists($this, $meth)) {
      return $this->$meth();
    }
    $meth = camelCase($meth, false);
    if(method_exists($this, $meth)) {
      return $this->$meth();
    }
  }

  public function setAll(array $fields) {
    foreach ($fields as $key => $value) {
      if(is_null($value))
        $this->$key = null;
      else
        $this->$key = trim($value);
    }
  }

  public function getField($field, $def = null) {
    $n = strpos($field, '.');
    if($n !== false) {
      $parts = explode('.', $field);
      $f = $parts[1];
    } else {
      $f = $field;
    }

    if(property_exists($this, $f)) {
      return $this->$f;
    } else {
      $getmeth = 'get' . camelCase($f, true);
      if(method_exists($this, $getmeth)) {
        return $this->$getmeth($def);
      }
    }
    return $def;
  }

  public function update($data = NULL, $where = null, $params = null) {
    $this->update_impl($data, $where, $params);
  }

  public function insert($data = NULL, $params = null) {
    $this->preModHooks();
    $this->preInsertHooks();
    $this->preUpdateHooks();
    $table = static::getTablename();
    $pk = static::$_pk;
    // if(!$this->$pk) throw new \Exception(get_called_class() . ' has no primary key');
    if(!$params) $params = array();
    // $params[$pk] = $this->$pk;
    if(!$data) {
      $data = (array)$this;
    } else if(!is_array($data)) {
      $data = (array)$data;
    }

    $fields = array();
    $values = array();
    foreach ($data as $key => $value) {
      $k = $this->filterServerFields($key, true);
      if($k) {
        $fields[] = "$k";
        $values[] = ":$k";
        $params[$k] = $value;
      }
    }

    // if(!in_array($pk, $fields))

    if(static::$_pkIsGuid) {
      if(!$this->$pk) {
        $this->$pk = guid_bin();
      }
      $fields[] = $pk;
      $params['id'] = $this->$pk;
      $values[] = ':id';//'UNHEX(:id)';
    }

    $fields = implode(', ', $fields);
    $values = implode(', ', $values);
    $sql = "INSERT INTO $table ($fields) VALUES ($values)";

    return static::execute($sql, $params);
  }

  public function setWithDateTime($field, $date = null) {
    if(!$date) {
      $date = time();
    }
    $this->$field = date('Y-m-d H:i:s', $date);
  }

  public function setWithDate($field, $date = null) {
    if(!$date) {
      $date = time();
    }
    $this->$field = date('Y-m-d', $date);
  }

  public function destroy() {
    return $this->destroyById($this->id);
  }

  public static function select($fields = "*") {
    return new \Relation\Select(static::getConnection(), get_called_class(),
      null, null, static::getTable(), $fields);
  }

  public static function where($where = null) {
    if($where && !is_array($where) && func_num_args()>1) {
      throw new \Exception('This should be an array');
    }
    $select = static::select();
    return $select->where($where);
  }

  /*
  FIXME: This is just too complex when looking at code that uses it.
   */
  public function manyToMany($dstClass, $fk, $joinTable, $jtfk) {
    $dstAlias = $dstClass::getAlias();
    $dstPk = $dstClass::getPkField();
    return $dstClass::select()->
    join($joinTable)->on(sprintf('%s.%s = %s.%s', $dstAlias, $dstPk, $joinTable->alias(), $jtfk))->
    fields($dstAlias . '.*')->
    where(sprintf('%s.%s', $joinTable->alias(), $fk), $this->id);
  }

  public function __toString() {
    return get_class($this) . ' #' . @$this->id;
  }

  /*
        ____             __            __           __   __  ___     __  __              __
       / __ \_________  / /____  _____/ /____  ____/ /  /  |/  /__  / /_/ /_  ____  ____/ /____
      / /_/ / ___/ __ \/ __/ _ \/ ___/ __/ _ \/ __  /  / /|_/ / _ \/ __/ __ \/ __ \/ __  / ___/
     / ____/ /  / /_/ / /_/  __/ /__/ /_/  __/ /_/ /  / /  / /  __/ /_/ / / / /_/ / /_/ (__  )
    /_/   /_/   \____/\__/\___/\___/\__/\___/\__,_/  /_/  /_/\___/\__/_/ /_/\____/\__,_/____/

   */

  protected function filterServerFields($key, $isInsert = FALSE) {
    if($key == 'id') return FALSE;
    $allowedFields = static::$allowedFields;
    if($allowedFields) {
      return in_array($key, $allowedFields);
    }
    return $key;
  }

  protected function preUpdateHooks() {}
  protected function preInsertHooks() {}
  protected function preModHooks() {}

  protected function update_impl($data = NULL, $where = null, $params = null) {
    $this->preModHooks();
    $this->preUpdateHooks();
    $table = static::getTablename();
    $pk = static::$_pk;
    if(!$this->$pk) throw new \Exception(get_called_class() . ' has no primary key');
    if(!$params) $params = array();
    $params[$pk] = $this->$pk;
    if(!$data) {
      $data = (array)$this;
    } else if(!is_array($data)) {
      $data = (array)$data;
    }

    $sql = "UPDATE $table SET ";
    $setparts = array();
    foreach ($data as $key => $value) {
      $k = $this->filterServerFields($key);
      if($k) {
        $setparts[] = "`$k` = :$k";
        $params[$k] = $value;
      }
    }

    if(!$where) {
      $where = "`$pk` = :$pk";
    } else {
      $where = "`$pk` = :$pk AND $where";
    }

    $sql .= implode(', ', $setparts) . " WHERE $where";

    return static::execute($sql, $params);
  }


  /*
       ______________  ________________
      / ___/_  __/   |/_  __/  _/ ____/
      \__ \ / / / /| | / /  / // /
     ___/ // / / ___ |/ / _/ // /___
    /____//_/ /_/  |_/_/ /___/\____/
              BELOW HERE
   */

  public static function getConnection() {
    if(!static::$pdo) {
      static::$pdo = config('connect')->local;
    }
    return static::$pdo;
  }

  public static function prepare($sql) {
    $sql = str_replace('SECDB', SECDB, $sql);
    $sql = str_replace('BEERDB', BEERDB, $sql);
    static::$last_query = $sql;
    $stmt = in_array($sql, static::$queries) ? static::$queries[$sql] : null;
    if(!$stmt) {
      $stmt = static::getConnection()->prepare($sql);
      static::$queries[$sql] = $stmt;
    }
    return $stmt;
  }

  public static function execute($sql, $params = null, $use_class = true) {
    $stmt = static::prepare($sql);
    if($use_class === true) {
      $stmt->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
    } else if(is_string($use_class)) {
      $stmt->setFetchMode(\PDO::FETCH_CLASS, $use_class);
    }
    if($params) {
      $stmt->execute($params);
    } else {
      $stmt->execute();
    }
    return $stmt;
  }

  public static function executeUpdate($sql) {
    $params = null;
    $num = func_num_args();
    $tmp;

    if($num == 2 && is_array($tmp = func_get_arg(1))) {
      $params = $tmp;
    } else if($num > 1) {
      for ($i=1; $i < $num; $i++) {
        $params[] = func_get_arg($i);
      }
    }
    return static::execute($sql, $params);
  }

  public static function getTablename() {
    $str = static::$_tablename;
    $class = get_called_class();
    if(!$str && array_key_exists($class, static::$_tablenames)) {
      $str = static::$_tablenames[$class];
    }
    if(!$str) {
      $parts = explode('\\', $class);
      $str = snakeCase(trim(array_pop($parts)));
      static::$_tablenames[$class] = $str;
    }
    $str = str_replace('SECDB', SECDB, $str);
    $str = str_replace('BEERDB', BEERDB, $str);
    return $str;
  }

  public static function getAlias() {
    $class = get_called_class();
    $str = static::$_alias;
    if(!$str && array_key_exists($class, static::$_aliases)) {
      $str = static::$_aliases[$class];
    }
    if(!$str) {
      $parts = explode('\\', get_called_class());
      $str = snakeCase(trim(array_pop($parts)));
      static::$_aliases[$class] = $str;
    }
    return $str;
  }

  public static function getTable() {
    $class = get_called_class();
    if(array_key_exists($class, static::$_tables)) return static::$_tables[$class];
    return static::$_tables[$class] = new \Relation\Table(static::getTablename(), static::getAlias());
  }

  public static function getPkField() {
    $str = static::$_pk;
    if(!$str) {
      $str = strtolower('pk_' . static::getTablename());
      static::$_pk = $str;
    }
    return $str;
  }

  public static function getFieldList() {
    return '*';
  }

  public static function findById($pk, $fields=null) {
    if(!$fields) $fields = static::getFieldList();
    $sql = "SELECT " . $fields . " from " . static::getTablename() . " where " . static::getPkField() . " = :pk";
    $stmt = static::execute($sql, array('pk'=>$pk));
    return $stmt->fetchObject(get_called_class());
  }

  public static function getById($pk, $fields=null) {
    $obj = static::findById($pk, $fields);
    if(!$obj) {
      throw new \Base\NotFound(get_called_class() . ' #' . $pk);
    }
    return $obj;
  }

  public static function fetchAll($sql, $params = null) {
    $stmt = static::execute($sql, $params);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_called_class());
  }

  public static function fetchObject($sql, $params = null) {
    $stmt = static::execute($sql, $params);
    return $stmt->fetchObject(get_called_class());
  }

  public static function findAll($fields = null, $orderby = null) {
    if(!$fields) $fields = static::getFieldList();
    $sql = "SELECT " . $fields . " from " . static::getTablename();
    if($orderby) {
      $sql .= " ORDER BY " . $orderby;
    }
    return static::fetchAll($sql);
  }

  public static function fetchAllAsLookup($sql, $params) {
    $all = static::fetchAll($sql, $params);
    $pk = static::getPkField();
    // We could use array_reduce here, but PHP inner functions suck.
    $lookup = array();
    foreach ($all as $obj) {
      $id = $obj->$pk;
      $lookup[$id] = $obj;
    }
    return $lookup;
  }

  public static function fetchAsLookup($orderby = null, $fields = null, $addDefault = false,
    $pk = NULL, $where = NULL, $params = array()
    ) {
    $single = false;
    if(!$pk) $pk = static::getPkField();
    if($fields && strpos($fields, ',') === FALSE) {
      $single = $fields;
      $fields = $pk . ", " . $single;
      if(!$orderby) $orderby = $single;
    }
    if(!$fields) $fields = static::getFieldList();
    $sql = "SELECT " . $fields . " from " . static::getTablename();
    if ($where) {
      $sql .= " WHERE " . $where;
    }
    if($orderby) {
      $sql .= " ORDER BY " . $orderby;
    }
    $all = static::fetchAll($sql, $params);
    // We could use array_reduce here, but PHP inner functions suck.
    $lookup = array();
    if($addDefault) {
      $lookup[''] = "Please Select";
    }
    foreach ($all as $obj) {
      $id = $obj->$pk;
      if($single)
        $v = $obj->$single;
      else
        $v = $obj;
      $lookup[$id] = $v;
    }
    return $lookup;
  }

  public static function deleteById($id) {
    return static::destroyById($id);
  }

  public static function destroyById($id) {
    $sql = "DELETE FROM " . static::getTablename() . " WHERE " . static::getPkField() . " = :pk";
    return static::execute($sql, array('pk' => $id));
  }

  public static function truncate($t = null) {
    if(is_null($t)) {
      $t = static::getTablename();
    }
    static::execute("truncate `$t`", null, false);
  }

}
