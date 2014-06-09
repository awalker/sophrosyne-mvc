<?php
/**
 * Fetch a config value from a module configuration file
 *
 * @param string $file name of the config
 * @param boolean $clear to clear the config object
 * @return object
 */
function config($file = 'config', $clear = FALSE, $array = FALSE)
{
  static $configs = array();

  if($clear)
  {
    unset($configs[$file]);
    return;
  }

  if(empty($configs[$file]))
  {
    //$configs[$file] = new \Micro\Config($file);
    require(SP . 'config/' . $file . EXT);
    if($array) {
      $configs[$file] = $config;
    } else {
      $configs[$file] = (object) $config;
    }
    // print dump($configs);
  }

  return $configs[$file];
}

function partialRoutes($file) {
  return config('proutes.' . $file, FALSE, TRUE);
}

function contentHasRows($content) {
  if($content && stripos($content, 'class="row"') !== false) {
    return true;
  }
  return false;
}

function isId($id) {
  if($id && is_numeric($id)) return true;
  if($id && is_string($id) && ($id!=='delete' && $id!=='create')) return true;
  return false;
}

function isProduction() {
  $mode = getenv('SMVC_MODE');
  if ($mode) {
    switch ($mode) {
      case 'PRODUCTION': return true; break;
      case 'TEST': return false; break;
      case 'DEV': return false; break;
      case 'DEBUG': return false; break;
    }
  }
  return true;
}

function isTest() {
  $mode = getenv('SMVC_MODE');
  return $mode == 'TEST';
}

/**
 * Return an HTML safe dump of the given variable(s) surrounded by "pre" tags.
 * You can pass any number of variables (of any type) to this function.
 *
 * @param mixed
 * @return string
 */
function dump()
{
  $string = '';
  foreach(func_get_args() as $value)
  {
    if(IS_COMMAND) {
      $string .= ($value === NULL ? 'NULL' : (is_scalar($value) ? $value : print_r($value, TRUE))) . "\n";
    } else {
      $string .= '<pre>' . h($value === NULL ? 'NULL' : (is_scalar($value) ? $value : print_r($value, TRUE))) . "</pre>\n";
    }
  }
  return $string;
}

/**
 * For times with dump consumes too much memory.
 * @param  mixed $array an array or object dump
 * @return String        HTML
 */
function dump_truncated_array($array) {
  $string = '<br>';
  if (is_array($array) && count($array) === 0) {
    return $string .= h('<empty array>');
  }
  if(is_object($array) || is_array($array)) {
    foreach ($array as $key => $value) {
      $string .= "<b>$key</b>: " . dump_truncated($value) . "<br>";
    }
  } else {
    $string .= dump_truncated($array) . '<br>';
  }
  return $string;
}

/**
 * An even more compact dump of any value. Takes multiple parameters and dumps them all.
 */
function dump_truncated() {
  $string = array();
  foreach (func_get_args() as $value)
  {
    if (is_object($value)) {
      $string[] = h("<" . get_class($value) . ">");
    } else if (is_array($value)) {
      $string[] = h("<array of " . count($value) . " elements>");
    } elseif (is_null($value)) {
      $string[] = h("<NULL>");
    } else if (is_string($value)) {
      $string[] = h(truncate($value, 80));
    } else if (is_bool($value)) {
      $string[] = ($value ? 'TRUE' : 'FALSE');
    } else if (is_numeric($value)) {
      $string[] = $value;
    } else {
      $string[] = h('<other>');
    }
  }
  return implode(', ', $string);
}

/**
 * Write to the application log file using error_log
 *
 * @param string $message to save
 * @return bool
 */
function log_message($message)
{
  $prefix = IS_COMMAND ? str_replace('/', '-', COMMAND_NAME)  . '-' : '';
  $path = SP . 'storage/log/' . $prefix . date('Y-m-d') . '.log';

  // Append date and IP to log message
  $msg = date('H:i:s ') . getenv('REMOTE_ADDR') . " $message\n";
  if (IS_COMMAND) {
    print $msg;
  }
  return error_log($msg, 3, $path);
}
/**
 * Send a HTTP header redirect using "location" or "refresh".
 *
 * @param string $url the URL string
 * @param int $c the HTTP status code
 * @param string $method either location or redirect
 */
function redirect($url = NULL, $code = 302, $method = 'location')
{
  if(strpos($url, '://') === FALSE)
  {
    $url = site_url($url);
  }

  //print dump($url);

  header($method == 'refresh' ? "Refresh:0;url = $url" : "Location: $url", TRUE, $code);
}

/**
 * Return the full URL to a location on this site
 *
 * @param string $path to use or FALSE for current path
 * @param array $params to append to URL
 * @return string
 */
function site_url()
{
  // Get parameters
  $args = func_get_args();
  $path = null;
  $params = null;
  $n = count($args);
  if ($n > 1) {
    $last = $args[$n - 1];
    if (is_array($last) || is_null($last)) {
      $params = array_pop($args);
      $n = count($args);
    }
  }
  if ($n == 1) {
    $path = $args[0];
  } else {
    $path = join_url($args);
  }
  // In PHP 5.4, http_build_query will support RFC 3986
  return DOMAIN . BASEPATH . ($path ? '/'. trim($path, '/') : PATH)
    . ($params ? '?'. str_replace('+', '%20', http_build_query($params, TRUE, '&')) : '');
}

$package = null;
function get_package() {
  global $package;
  if ($package) return $package;
  return $package = json_decode(file_get_contents(SP . 'package.json'));
}
/**
 * Should JavaScript assets should used minified?
 */
function js_asset_min() {
  return true;
}

/**
 * Get a url to a JavaScript asset.
 */
function js_asset_url($path = NULL, array $params = NULL)
{
  $isJs = true;
  // $useMin = isProduction();
  $useMin = js_asset_min();
  $prefix = 'scripts/';
  if ($useMin) {
    $ext = '.' . get_package()->version . '.min.js';
  } else {
    $ext = '.js';
  }
  $filename = $path;


  return site_url('assets/' . $prefix . $filename . $ext, $params);
}

/**
 * Convert special characters to HTML safe entities.
 *
 * @param string $string to encode
 * @return string
 */
function h($string)
{
  return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
}

/**
 * Like h but has special handling for missing values and highlighting dangerous item.
 * FIXME: too specific to one project
 */
function hm($string, $missing = 'missing', &$isOk)
{
  if($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
  } else {
    $isOk = false;
    return '<span class="text-danger">' . h($missing) . '</span>';
  }
}

/**
 * Truncate $string to at most $limit characters.
 * @param  String  $string Target string
 * @param  integer $limit  desired limit
 * @param  String $more  The text to add to the the string
 * @param  integer $moreLength The length of $more which could be HTML
 * @return String          shorted string
 */
function truncate($string, $limit = 80, $more = '…', $moreLength = 1) {
  if(!$string) return '';
  if(strlen($string) > $limit) {
    return h(trim(substr($string, 0, $limit-$moreLength)) . $more);
  }
  return h(trim($string));
}

/**
 * Truncate to 40 characters.
 */
function truncate40($string) {
  return truncate($string, 40);
}

/**
 * Filter a valid UTF-8 string so that it contains only words, numbers,
 * dashes, underscores, periods, and spaces - all of which are safe
 * characters to use in file names, URI, XML, JSON, and (X)HTML.
 *
 * @param string $string to clean
 * @param bool $spaces TRUE to allow spaces
 * @return string
 */
function sanitize($string, $spaces = TRUE)
{
  $search = array(
    '/[^\w\-\. ]+/u',     // Remove non safe characters
    '/\s\s+/',          // Remove extra whitespace
    '/\.\.+/', '/--+/', '/__+/' // Remove duplicate symbols
  );

  $string = preg_replace($search, array(' ', ' ', '.', '-', '_'), $string);

  if( ! $spaces)
  {
    $string = preg_replace('/--+/', '-', str_replace(' ', '-', $string));
  }

  return trim($string, '-._ ');
}


/**
 * Create a SEO friendly URL string from a valid UTF-8 string.
 *
 * @param string $string to filter
 * @return string
 */
function sanitize_url($string)
{
  return urlencode(mb_strtolower(sanitize($string, FALSE)));
}


/**
 * Filter a valid UTF-8 string to be file name safe.
 *
 * @param string $string to filter
 * @return string
 */
function sanitize_filename($string)
{
  return sanitize($string, FALSE);
}


/**
 * Return a SQLite/MySQL/PostgreSQL datetime string
 *
 * @param int $timestamp
 */
function sql_date($timestamp = NULL)
{
  return date('Y-m-d H:i:s', $timestamp ?: time());
}

/**
 * Color output text for the CLI
 *
 * @param string $text to color
 * @param string $color of text
 * @param string $background color
 */
function colorize($text, $color, $bold = FALSE)
{
  // Standard CLI colors
  $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black'));

  // Escape string with color information
  return"\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . "m$text\033[0m";
}

/**
 * Convert $s to title case.
 * @param  String $s input
 * @return String    Converted
 */
function titleCase($s) {
  return mb_convert_case($s, MB_CASE_TITLE);
}

/**
 * Convert $s to lowercase. Eg. "Hi" -> "hi"
 * @param  String $s input
 * @return String    Converted
 */
function lowerCase($value) {
  return mb_convert_case($value, MB_CASE_LOWER);
}

/**
 * Convert $s to uppercase. Eg. "Hi" -> "HI"
 * @param  String $s input
 * @return String    Converted
 */
function upperCase($value) {
  return mb_convert_case($value, MB_CASE_UPPER);
}

/**
 * Convent $s to camelCase. Eg. "Hi Bob" -> "HiBob" and "foo_bar" -> 'FooBar'
 * @param  String $s input
 * @param  Boolean $initalCap should the first letter be capitalized?
 * @return String    Converted
 */
function camelCase($s, $initalCap = true) {
  if(strpos($s, '_') !== FALSE) {
    // is foo_bar style
    $parts = explode('_', $s);
  } else if(strpos($s, ' ') !== FALSE) {
    // is "foo bar" style
    $parts = explode(' ', $s);
  } else if (preg_match("/^[A-Z]+[a-z]+/", $s)) {
    // if already camel cased with initalCap
    if($initalCap) return $s;
    return lowerCase(substr($s, 0, 1)) . substr($s, 1);
  } else if(preg_match("/^[a-z][a-z]+[A-Z]/", $s)) {
    // camel cased with no initalCap
    if(!$initalCap) return $s;
    return upperCase(substr($s, 0, 1)) . substr($s, 1);
  } else {
    // default (single lowercase word)
    $parts = array($s);
  }

  foreach ($parts as $index => $value) {
    if(!$initalCap and $index===0) {
      $parts[$index] = lowerCase($value);
    } else {
      $parts[$index] = titleCase($value);
    }
  }

  return implode('', $parts);
}

/**
 * Convert $s to snakeCase. Eg. 'FooBar' -> 'foo_bar'
 * @param  String $s input
 * @return String    Converted
 */
function snakeCase($s) {
  if(strpos($s, ' ') !== FALSE) {
    // is "foo bar" style
    $parts = explode(' ', $s);
  } else if (preg_match("/^[a-z0-9]+/i", $s)) {
    $parts = preg_split("/([A-Z0-9]+[a-z0-9]+)/", $s, -1, PREG_SPLIT_DELIM_CAPTURE);
  } else if(strpos($s, '_') !== FALSE) {
    // is foo_bar style
    return $s;
  } else {
    // default (single lowercase word)
    $parts = array($s);
  }

  foreach ($parts as $index => $value) {
    $parts[$index] = lowerCase($value);
  }

  $out = preg_replace('/[^0-9A-Z]+/i', '_', implode('_', $parts));
  $out = preg_replace('/_+/', '_', $out);
  $out = preg_replace('/^_/', '', $out);
  $out = preg_replace('/_$/', '', $out);
  return $out;
}

/**
 * Make $s more pleasing to humans. Eg. 'FooBar' -> 'Foo Bar'
 * @param  String $s input
 * @return String    Converted
 */
function humanize($s) {
  $parts = preg_split("/([A-Z0-9]+[a-z0-9]+)/", $s, -1, PREG_SPLIT_DELIM_CAPTURE);
  // foreach ($parts as $index => $p) {

  // }
  return trim(implode(' ', $parts));
}

/**
 * Make $s more pleasing to humans. Eg. 'FooBar' -> 'Foo bar'
 * @param  String $s input
 * @return String    Converted
 */
function humanize2($s) {
  $parts = preg_split("/[^0-9A-Z]/i", $s);
  return trim(titleCase(implode(' ', $parts)));
}

/**
 * Formats a number into a dollar amount.
 * @param  [type] $num    [description]
 * @param  string $prefix [description]
 * @return [type]         [description]
 */
function dollar($num, $prefix = "$") {
  if($num < 0 ) {
    return sprintf("-%s%0.02f", $prefix, -1 * $num);
  }
  return sprintf("%s%0.02f", $prefix, $num);
}

function labelify($s) {
  $out = str_replace('_', ' ', $s);
  $out = titleCase($out);
  return $out;
}

function pluralize($s, $count=2) {
  if($count == 1) {
    return $s;
  } else {
    if(in_array(strtolower($s), (array)config('plural_exceptions'))) {
      return $s;
    } else {
      return $s . 's';
    }
  }
}

function pluralizeWithString($s, $count=2) {
  return sprintf("%s %s", number_format($count), pluralize($s, $count));
}

function request($field, $default = null) {
  if(array_key_exists($field, $_REQUEST)) {
    return $_REQUEST[$field];
  }
  return $default;
}

function requestSessionDefault($field, $default = null) {
  if (IS_COMMAND) return $default;
  $v = $default;
  // GET
  if(array_key_exists($field, $_REQUEST)) {
    $v = $_REQUEST[$field];
  } else {
    $v = session($field, $default);
  }
  // SET
  if($v == $default) {
    unset($_SESSION[$field]);
  } else {
    $_SESSION[$field] = $v;
  }
  return $v;
}

function get($field, $default = null) {
  if(array_key_exists($field, $_GET)) {
    return $_GET[$field];
  }
  return $default;
}

function post($field, $default = null) {
  if(array_key_exists($field, $_POST)) {
    return $_POST[$field];
  }
  return $default;
}

function cookie($field, $default = null) {
  if(array_key_exists($field, $_COOKIE)) {
    return $_COOKIE[$field];
  }
  return $default;
}

function uploadfile($field, $default = null) {
  if(array_key_exists($field, $_FILES)) {
    return $_FILES[$field];
  }
  return $default;
}

function session($field, $default = null) {
  if($_SESSION && array_key_exists($field, $_SESSION)) {
    return $_SESSION[$field];
  }
  return $default;
}

function theme($theme, $object) {
  $config = config($theme);
  if(!$config) return $object;
  foreach ($config as $key => $value) {
    $object->$key = $value;
  }
  return $object;
}

function formMixin($base, $new) {
  return $new + $base;
}

function mixin(&$dst, $src) {
  foreach ($src as $key => $value) {
    if(!property_exists($dst, $key)) {
      $dst->$key = $value;
    }
  }
  return $dst;
}

/**
 * Create a RecursiveDirectoryIterator object
 *
 * @param string $dir the directory to load
 * @param boolean $recursive to include subfolders
 * @return object
 */
function directory($dir, $recursive = TRUE)
{
  $i = new \RecursiveDirectoryIterator($dir);

  if( ! $recursive) return $i;

  return new \RecursiveIteratorIterator($i, \RecursiveIteratorIterator::SELF_FIRST);
}

function guid_bin()
{
  $data = openssl_random_pseudo_bytes(16);

  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

  return $data;
}

function guid_str($data = null) {
  if(is_null($data)) {
    $data = guid_bin();
  } else if(is_string($data)) {
    return $data;
  }
  return bin2hex($data);
}

function format_guid($data) {
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(guid_str($data), 4));
}

function guidv4()
{
  return format_guid(guid_bin());
}

if(!function_exists('hex2bin')) {
  function hex2bin($hex_string) {
    return pack("H*" , $hex_string);
  }
}

function mandril_vars($in) {
  $out = array();
  foreach ($in as $key => $value) {
    $out[] = array('name'=>$key, 'content'=>$value);
  }
  return $out;
}


function csvheaders($filename) {
  header("Content-type: application/binary");
  header("Content-Disposition: attachment; filename=" . $filename);
  header("Pragma: public");
  header("Cache-Control: max-age=0");
  return fopen('php://output', 'w');
}


function getLockName($name = null) {
  if(!$name) $name = str_replace('/', '-', COMMAND_NAME);
  if( IS_COMMAND) {
    $path = SP . 'storage/locks/' . $name . '.lock';
    return $path;
  }
  return false;
}

function getLock() {
  $lock = getLockName();
  if($lock) {
    if(file_exists($lock)) {
      log_message('Lock exists');
      die('Lock exists.');
      return false;
    } else {
      file_put_contents($lock, date('Y-m-d h:i:s'));
      log_message(COMMAND_NAME . ' started');
      return true;
    }
  }
  return false;
}

function clearLock() {
  $lock = getLockName();
  if($lock) {
    if(file_exists($lock)) {
      log_message(COMMAND_NAME . ' clearing lock');
      unlink($lock);
      return true;
    } else {
      log_message(COMMAND_NAME . ' missing lock');
    }
  }
  return false;
}

function fsi_address_to_html($o) {
  if(!$o) return '';
  $out = h($o->Name) . "<br>";
  if($o->Attention) $out .= '<strong>Attn:</strong>' . h($o->Attention) . "<br>";
  if($o->Department) $out .= '<strong>Dept:</strong>' . h($o->Department) . "<br>";
  $out .= h($o->Address1) . '<br>';
  if($o->Address2) $out .= h($o->Address2) . '<br>';
  $out .= h($o->City) . ', ' . h($o->State) . ' '. h($o->PostalCode);
  $out .= ' ' . h($o->CountryCode);
  $out .= "<br><strong>Phone:</strong> " . h($o->Phone);
  return $out;
}

function fsi_address_to_txt($o) {
  if(!$o) return '';
  $nl = "\n";
  $out = h($o->Name) . $nl;
  if($o->Attention) $out .= 'Attn:' . ($o->Attention) . $nl;
  if($o->Department) $out .= 'Dept:' . ($o->Department) . $nl;
  $out .= ($o->Address1) . $nl;
  if($o->Address2) $out .= ($o->Address2) . $nl;
  $out .= ($o->City) . ', ' . ($o->State) . ' '. ($o->PostalCode);
  $out .= ' ' . ($o->CountryCode);
  $out .= $nl . "Phone: " . ($o->Phone);
  return $out;
}

function _or($a, $b) {
  return $a ? $a : $b;
}

function wsDateStrFormatted($in) {
  try {
    $date = new DateTime($in);
    $out = $date->format('m/d/Y');
  } catch (\Exception $e) {
    return $in;
  }
  return $out;
}

function ago($in, $aboutStr = 'About ') {
  $utc = new DateTimeZone('UTC');
  $now = new DateTime('now');
  $date = new DateTime($in);
  $now->setTimezone($utc);
  $date->setTimezone($utc);
  $diff = $now->diff($date);
  $out = array();
  $days = (int)$diff->format('%d');
  $hours = (int)$diff->format('%h');
  $mins = (int)$diff->format('%i');
  $seconds = (int)$diff->format('%s');
  $totalSeconds = $seconds + ($mins + ((($days *24) + $hours) * 60)) * 60;
  $about = false;
  if($totalSeconds < 5) {
    return 'Just now';
  }
  $seconds = round($seconds / 15) * 15;
  if($totalSeconds > 30) {
    $seconds = 0;
    if($mins < 1) {
      $mins++;
      $about = true;
    }
  }
  if($mins > 44) {
    $mins = 0;
    $hours++;
    $about = true;
  }
  if($hours > 0) {
    $mins = round($mins / 15) * 15;
    if($mins > 0) {
      $hours += round($mins / 60, 2);
      $mins = 0;
      $about = true;
    }
  }
  if($hours > 4.1) {
    $about = true;
    $hours = round($hours);
  }
  if($days > 0) {
    $about = true;
    $hours = round($hours / 4) * 4;
    if($hours > 0 && $days < 3) {
      $days += round($hours / 24, 2);
      $hours = 0;
    }
  }
  if($days > 1) {
    $hours = 0;
  }
  if($days > 0) {
    $out[] = $days . ' ' . pluralize('day', $days);
  }
  if($hours > 0) {
    $out[] = $hours . ' ' . pluralize('hour', $hours);
  }
  if($mins > 0) {
    $out[] = $mins . ' ' . pluralize('min', $mins);
  }
  if($seconds > 0) {
    $out[] = $seconds . ' ' . pluralize('second', $seconds);
  }
  return ($about ? $aboutStr : '') . implode(', ', $out) . ' ago';
}

function admin_item_beer_image($in) {
  if($in) {
    return '<img src="' . $in . '" width="32px">';
  }
  return '';
}

function boolean_column($in) {
  if($in === true || $in === 1 || $in == '1')
    return 'Yes';
  return 'No';
}

function mkbool($b, $default = false) {
  if(is_null($b)) return $default;
  if(is_bool($b)) return $b;
  if(is_string($b)) return $b === '1';
  if(is_numeric($b)) return $b === 1;
  return $default;
}

function check_column($in) {
  return boolean_column($in);
  if($in === true || $in === 1 || $in == '1')
    return '<span class="glyphicon glyphicon-ok"></span>';
  return '<span class="glyphicon glyphicon-remove"></span>';
}

function addRoutes(&$routes, $newRoutes, $prefix = '') {
  if (is_null($routes)) $routes = array();
  foreach ($newRoutes as $p => $c) {
      $routes[$prefix . $p] = $c;
  }
  return $routes;
}

/**
 * FIXME: Temp returning false until the other places where pjax is used can
 * be brought upto speed with our new way of doing things.
 * @return boolean [description]
 */
function is_pjax_request() {
  $pjax = false;
  // if(array_key_exists('HTTP_X_PJAX', $_SERVER)) {
  //   $pjax = $_SERVER['HTTP_X_PJAX'] == 'true';
  // }
  // if(!$pjax) {
  //   $pjax = request('_pjax');
  // }
  return $pjax;
}

function partial($view_path, $ctx = null, $extra = null) {
  if(!$view_path) return '';
  $opts = $ctx;
  if($extra) {
    $opts = array_merge((array)$ctx, (array)$extra);
  }
  $view = new \Base\View($view_path, $opts);
  return $view->render();
}

function send_error_email($message, $stacktrace, $user = 'unknown') {
  if (!isProduction()) {
    return;
  }
  $conf = config('mail');
  $mandril = new \Mandrill($conf->key);

  $tpcon = array();
  $merge_vars = array('message' => $message, 'stacktrace' => $stacktrace, 'user' => $user);

  $email_msg = array (
    'auto_text' => true,
    'auto_html' => true,
    'inline_css' => true,
    'merge' => true,
    'global_merge_vars' => mandril_vars($merge_vars),
    'to' => array(array('type' => 'to', 'email' =>'awalker@virtualmindset.com', 'name'=>'Adam Walker'))
    );

  $result = $mandril->messages->sendTemplate('error-template', mandril_vars($tpcon), $email_msg);
  return $result;
}

function controllerToBaseUrl($controller) {
  $c = $controller;
  if (is_object($controller)) {
    $c = get_class($controller);
  }
  $url = '';
  foreach ((array)config(ROUTE_FILE) as $urlfrag => $conClassName) {
    if ($conClassName == $c) {
      if (strpos($urlfrag, ':') === false && strlen($urlfrag) >= strlen($url)) {
        $url = $urlfrag;
      }
    }
  }
  if (!isProduction() && $url === '') {
    throw new \Exception('No route found for controller ' . $c);
  }
  return $url;
}

function site_controller($controller = NULL, array $params = NULL) {
  return site_url(controllerToBaseUrl($controller), $params);
}

function join_url() {
  $pieces = func_get_args();
  if (count($pieces) === 1 and is_array($pieces[0])) {
    $pieces = $pieces[0];
  }
  $url = implode('/', $pieces);
  return preg_replace('/\/+/', '/', $url);
}