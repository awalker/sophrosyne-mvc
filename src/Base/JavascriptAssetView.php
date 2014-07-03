<?php
namespace Base;
define('JS_ASSET_DIR', SP . 'assets/scripts/');
class JavascriptAssetView extends AssetView {
  public static $directory = JS_ASSET_DIR;
  public static $prefix = '%%';
  public static $suffix = '%%';

  public static $ext = '.js';
  public static $mime = 'text/javascript; charset=utf-8';

}