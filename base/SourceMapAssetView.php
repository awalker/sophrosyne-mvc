<?php
namespace Base;
define('MAP_ASSET_DIR', SP . 'assets/scripts/');
class SourceMapAssetView extends AssetView {
  public static $directory = MAP_ASSET_DIR;
  public static $prefix = '%%';
  public static $suffix = '%%';

  public static $ext = '.map';
  public static $mime = 'text/plain; charset=utf-8';

}