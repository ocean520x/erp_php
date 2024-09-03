<?php
namespace Service;

use Config\Env;

class Data extends Base{
  
  /* 图片地址 */
  static function Img(string $img, bool $isTmp = true): string{
    if(!$img) return '';
    return $isTmp?Env::$img_url .$img:Env::$img_url . $img. '?' .time();
  }
}
