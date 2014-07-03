<?php
namespace Base;
$IN_CONTROLLER = null;
/**
 * The Base Controller class.
 */
class Controller {

  public $route = NULL;
  public $dispatch = NULL;
  public $_debug = false;
  public $template = "layouts/layout";
  public $outputType = "Html";
  public $cssTemplates = array();
  public $clientCss = '';
  public $menu = '<div><h1>Project</h1></div>';
  public $menuView = false;
  public $scripts = array();
  public $advancedScripts = array();
  public $title = null;
  public $seoDescription = null;
  public $shortcut_icon = false;
  public $theme = NULL;
  public $frameworkPackage = "";
  public $pjax = false;
  public $layoutVersion = '_l1';

  public function __construct($route, \Base\Dispatch $dispatch) {
    global $IN_CONTROLLER;
    $IN_CONTROLLER = $this;
    $this->route = $route;
    $this->dispatch = $dispatch;
    if (!isTest()) {
      $this->frameworkPackage = get_package();
      $this->frameworkPackage->tag = isProduction() ? '' : ' Dev';
      $this->layoutVersion = 'v' . $this->frameworkPackage->version . (isProduction() ? '' : '_dev') . $this->layoutVersion;
    }
  }

  public function flash($msg = null, $clear = false) {
    if($msg) {
      if(is_string($msg)) {
        $msg = new \Base\Message('error', $msg);
      }
      $_SESSION['flash_message'] = $msg;
    } else {
      $msg = @$_SESSION['flash_message'];
      if($clear) $_SESSION['flash_message'] = NULL;
    }
    return $msg;
  }

  public function success($msg) {
    if($msg) {
      if(is_string($msg)) {
        $msg = new \Base\Message('success', $msg);
      }
    }
      return $this->flash($msg);
  }

  public function setupCss() {
    $clientCss = '';
    foreach ($this->cssTemplates as $template) {
      $view = new \Base\View($template, $this);
      $clientCss .= (string) $view;
    }
    $this->clientCss = $clientCss;
  }

  public function initialize($method) {
    global $IN_CONTROLLER;
    $IN_CONTROLLER = $this;
    $this->session_start();
  }

  public function session_start() {
    if (IS_COMMAND) return;
    if(session_id() === "") {
      session_start();
    }
  }

  /**
   * Not authorized.
   * @return [type] [description]
   */
  public function show_403()
  {
    headers_sent() OR header('HTTP/1.0 403 Not authorized');
    $this->content = new \Base\View('403');
  }

  /**
   * Page not found.
   * @return [type] [description]
   */
  public function show_404()
  {
    headers_sent() OR header('HTTP/1.0 404 Page Not Found');
    $this->content = new \Base\View('404');
  }

  public function sendHtml() {
    if(!isset($this->content)) return;
    if (!headers_sent()) {
      header('Content-Type: text/html; charset=utf-8');
      if ($this->layoutVersion) {
        header('X-PJAX-Version: ' . $this->layoutVersion);
      }
    }
    $pjax = is_pjax_request();
    if($pjax) {
      if($this->title) {
        print \Base\HTML::tag('title', h($this->title));
      }
      print $this->content;
      return;
    }
    if($this->menuView) {
      $this->menu = new \Base\View($this->menuView, $this);
    }
    $this->setupCss();
    if(!isset($this->footer)) {
      $this->footer = new \Base\View('layouts/footer', $this);
    }
    if(!isset($this->header)) {
      $header = new \Base\View('layouts/header', $this);
      $header->set((array) $this);
      $this->header = $header;
    }
    $layout = new \Base\View($this->template);
    $layout->set((array) $this);
    print $layout;

    $layout = NULL;

    if($this->_debug) {
      print new \Base\View('system/debug', $this);
    }
  }

  public function redirectUri($uri, $params = null) {
    if($params) {
      $uri = '?';
      foreach ($params as $key => $value) {
        $uri .= $key . '=' . urlencode($value);
      }
    }
    $this->redirect_raw($uri);
  }

  public function redirect_raw($uri) {
    $this->outputType = 'Redirect';
    $this->_redirect_uri = $uri;
  }

  public function sendJson() {
    headers_sent() OR header('Content-Type: application/json; charset=utf-8');
    print json_encode($this->json);
  }

  public function sendRedirect() {
    redirect($this->_redirect_uri);
  }

  public function sendNop() {}

  public function send() {
    $meth = 'send' . $this->outputType;
    $this->$meth();
  }
}