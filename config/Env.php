<?php
namespace Config;

class Env{

  static $key = '3b6807a07d907c5f7ee3d363718ceae7';            // KEY

  /* 资源 */
  static $img_url = 'https://cszbvip.oss-cn-guangzhou.aliyuncs.com/';   // 图片目录

  /* Token */
  static $admin_token_prefix = 'Admin';                        // 前缀
  static $admin_token_time = 2*3600;                          // 有效时间（2小时）
  static $admin_token_sso = true;                             // 单点登录
  static $admin_token_auto = true;                            // 自动续期
  /* 资源地址 */
  static function BaseUrl($url='', $host=''){
    if($host) return $host.$url;
    $str = isset($_SERVER['HTTPS'])?'https':'http';
    return $str. '://' .$_SERVER['HTTP_HOST']. '/' . $url;
  }
}
