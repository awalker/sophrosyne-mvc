<?php
namespace Base;
define('TEMPLATE_ASSET_DIR', SP . 'views/');
class TemplateAssetView extends AssetView {
  public static $directory = TEMPLATE_ASSET_DIR;

  public static $ext = '.php';
  public static $mime = 'text/javascript; charset=utf-8';
  public $viewEnableFiltering = true;
  public $__template;
  public $indent = 1;
  public $viewNum = 1;
  public $vars = array();

  public $sections = array();

  protected function processSingle($matches) {
    if (!$this->viewEnableFiltering and trim($matches[1]) == 'START FILTERING') {
      $this->viewEnableFiltering = true;
      return '';
    }
    if (!$this->viewEnableFiltering) {return $matches[0];}
    if (trim($matches[1]) == 'STOP FILTERING') {
      $this->viewEnableFiltering = false;
      return '';
    }
    try {
      $parts = explode(' ', trim($matches[1]));
      $path = $parts[0];
      switch($parts[0]) {
        case 'for':
          return $this->processFor($matches, $parts); break;
        case 'partial':
          return $this->processPartial($matches, $parts); break;
        case 'ifpartial':
          return $this->processIfPartial($matches, $parts); break;
        default:
          if(count($parts) > 1) {
            return '<strong style="color: red;">' . h($matches[0]) . '</strong>';
          }
      }
      $processor = 'h';
      $ps = explode('|', $path);
      if(count($ps) > 1) {
        $processor = $ps[0];
        $path = $ps[1];
      }
      $out = $this->getFromContext($path);
      if (is_object($out) && is_a($out, 'Base\View')) {
        return (string)$out;
      }
      if($processor) {
        $out = 'ctx.helper.' . $processor . '(' . $out . ')'; //$processor($out);
      }
      return "');". $this->newline('// ' . $matches[0]) . $this->newline("out.append($out);") . $this->newline("out.append('");
    } catch(\Exception $e) {
      return '<strong style="color: red;">' . h($matches[0]) . '<br>' . h($e->getMessage()) . '</strong>';
    }

  }

  protected function getFromContext($path) {
    return 'ctx.ctx.' . $path;
  }

  protected function processFor($matches, $parts) {
    $collection = $this->getFromContext($parts[1]);
    $notFoundViewPath = null;
    if(count($parts) > 4) {
      $notFoundViewPath = array_pop($parts);
    }
    if(count($parts) > 4) { array_pop($parts); }
    $viewPath = array_pop($parts);
    // $view = $this->getFromContext($viewPath);
    $rendered = array();
    // TODO: handle the empty collection
    $out = array("');", $this->newline('// ' . $matches[0]), $this->newline('_.each('), $collection, ', function(item, index, list) {');
    $this->indent++;
    $out[] = $this->doView($viewPath);
    $this->indent--;
    $out[] = $this->newline("});");
    $out[] = $this->newline("out.append('");

    return implode('', $out);
  }

  protected function doView($viewPath, $var = 'child') {
    $out = array();
    $out[] = $this->newline("// create new context");
    $this->vars[$var] = $var;
    $out[] = $this->newline("$var = {ctx: {}, helpers: ctx.helpers, controller: ctx.ctx, views: ctx.views};");
    $out[] = $this->newline("// apply new values");
    $out[] = $this->newline("$var.ctx = item;");
    $out[] = $this->newline("// execute new view");
    $out[] = $this->newline("out.append(ctx.views." . $viewPath . "($var));");
    $out[] = $this->newline("$var = null;");

    return implode('', $out);
  }

  protected function processPartial($matches, $parts) {
    $viewPath = array_pop($parts);
    $varName = 'viewCtx' . $this->viewNum++;
    return "');" . $this->newline('// ' . $matches[0]) . $this->doView($viewPath, $varName) . $this->newline("out.append('");
  }

  protected function processIfPartial($matches, $parts) {
    $cond = $parts[1];
    $out = array();
    $out[] = "');";
    $out[] = $this->newline('// ' . $matches[0]);
    $out[] = $this->newline("if (ctx.ctx.");
    $out[] = $cond;
    $out[] = ") {";
    $this->indent++;
    $viewPath = array_pop($parts);
    $varName = 'viewCtx' . $this->viewNum++;
    $out[] = $this->doView($viewPath, $varName);
    $this->indent--;
    $out[] = $this->newline("}");
    $out[] = $this->newline("out.append('");
    return implode('', $out);
  }

  protected function embedInJS($str) {
    return ($str);
  }

  protected function newline($str) {
    return "\n" . str_repeat("  ", $this->indent) . $str;
  }

  public function render($fnStyle = 0, $startingIndent = 0)
  {
    $this->indent = $startingIndent;
    if(!$this->__template) {
      $this->__template = file_get_contents($this->getViewFilename());
    }
    $pattern = '/\{\{(.+?)\}\}/';
    $sections = &$this->sections;
    $lines = explode("\n", $this->__template);
    $tmp = explode('/', $this->__view);
    $fn = array_pop($tmp);
    $name = '';
    $prefix = '';
    $suffix = '';
    if($fnStyle == 0 ) {
      $name = $fn;
    } elseif ($fnStyle == 1) {
      $prefix = "$fn: ";
    } elseif ($fnStyle == 2) {
      $prefix = "$fn = ";
      $suffix = ";";
    } elseif($fnStyle == 3) {
      $prefix = "var $fn = ";
      $suffix = ";";
    }
    $prefix = array($prefix, "function $name(ctx) {");
    $this->indent++;
    $prefix[] = $this->newline("var ");
    $this->vars = array('out = []');
    foreach ($lines as $line) {
      $s = $this->newline ("out.append('");
      $s .= $this->embedInJS(preg_replace_callback($pattern,
        array($this, 'processSingle'), $line));
      $sections[] = $s . "\\n');";
    }
    $sections[] = $this->newline("return out.join('');");
    $this->indent--;
    $sections[] = $this->newline("}");
    if($suffix) {
      $sections[] = $suffix;
    }
    $sections[] = $this->newline("");
    $prefix[] = implode(', ', $this->vars) . ';';
    $str = implode("", $prefix). implode("", $sections);
    return str_replace("out.append('');", '', $str);
  }
}