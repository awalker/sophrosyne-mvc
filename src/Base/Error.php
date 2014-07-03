<?php
namespace Base;

class Error {
  public static $found = FALSE;

  public static function header()
  {
    headers_sent() OR header('HTTP/1.0 500 Internal Server Error');
  }

  public static function fatal()
  {
    if($e = error_get_last())
    {
      if(stripos($e['message'], 'magic_quotes')) {return;}
      Error::exception(new \ErrorException($e['message'], $e['type'], 0, $e['file'], $e['line']));
    }
  }

  public static function handler($code, $error, $file = 0, $line = 0)
  {
    // Ignore errors less than the current error setting
    if((error_reporting() & $code) === 0) return TRUE;
    if(strpos($error, 'magic_quotes_gpc') !== false) return true;
    if($code == E_WARNING && strpos($file, '/vendor/') !== false) {
      if (strpos($file, '/vendor/awalker/') === false) {
        return true;
      }
    }
    global $IN_CONTROLLER;
    $controller = null;
    $brokenView = null;
    if (isset($IN_CONTROLLER)) {
      $controller = $IN_CONTROLLER;
    }
    if (array_key_exists('IN_VIEW', $GLOBALS)) {
      $brokenView = $GLOBALS['IN_VIEW'];
    }
    $user = IS_COMMAND ? 'COMMAND' : ($controller && @$controller->loggedInUserId ? $controller->loggedInUserId : 'user not known');
    $view = new View('system/error');
    $view->error = $error;
    $view->code = $code;
    $view->isProduction = false;
    $strview = (string)$view;
    if ($controller) {
      $strview .= '<h2>Controller: ' .get_class($controller) . '</h2>' . dump_truncated_array((array)$controller);
    }
    if ($brokenView) {
      $strview .= '<h2>View: ' . get_class($brokenView) . '</h2>' . dump_truncated_array((array)$brokenView);
    }
    if (\Base\Model::$last_query) {
      $strview .= '<h2>Last SQL:</h2>' . dump(\Base\Model::$last_query);
    }
    send_error_email("[$code] $error [$file] ($line)", $strview, $user);

    log_message("[$code] $error [$file] ($line)");
    if(IS_COMMAND) {
      print "[$code] $error [$file] ($line)\n";
      return TRUE;
    }

    self::$found = TRUE;
    self::header();

    $view->isProduction = isProduction();
    print $view;

    return TRUE;
  }


  public static function exception(\Exception $e)
  {
    self::$found = TRUE;
    $msg = $e->getMessage();
    if(stripos($msg, 'magic_quotes')) {return;}

    // If the view fails, at least we can print this message!
    $message = "{$e->getMessage()} [{$e->getFile()}] ({$e->getLine()})";

    try
    {
      $user = IS_COMMAND ? 'COMMAND' : 'Not a command';
      log_message($message);
      $view = new View('system/exception');
      $view->exception = $e;
      $view->isProduction = false;
      send_error_email($message, (string)$view, $user);
      if(IS_COMMAND) {
        print $message .  "\n";
        exit(1);
      }
      self::header();


      $view->isProduction = isProduction();
      print $view;
    }
    catch(\Exception $e)
    {
      print $message;
    }

    exit(1);
  }


  /**
   * Fetch and HTML highlight serveral lines of a file.
   *
   * @param string $file to open
   * @param integer $number of line to highlight
   * @param integer $padding of lines on both side
   * @return string
  */
  public static function source($file, $number, $padding = 5)
  {
    // Get lines from file
    $lines = array_slice(file($file), $number-$padding-1, $padding*2+1, 1);

    $html = '';
    foreach($lines as $i => $line)
    {
      $html .= '<b>' . sprintf('%' . mb_strlen($number + $padding) . 'd', $i + 1) . '</b> '
        . ($i + 1 == $number ? '<em>' . h($line) . '</em>' : h($line));
    }
    return $html;
  }


  /**
   * Fetch a backtrace of the code
   *
   * @param int $offset to start from
   * @param int $limit of levels to collect
   * @return array
   */
  public static function backtrace($offset, $limit = 5)
  {
    $trace = array_slice(debug_backtrace(), $offset, $limit);

    foreach($trace as $i => &$v)
    {
      if( ! isset($v['file']))
      {
        unset($trace[$i]);
        continue;
      }
      $v['source'] = self::source($v['file'], $v['line']);
    }

    return $trace;
  }

}