<?php
namespace Base;
define('PNG_ASSET_DIR', SP . 'assets/images/');
class PngAssetView extends AssetView {
  public static $directory = PNG_ASSET_DIR;

  public static $ext = '.png';
  public static $mime = 'image/png';

}