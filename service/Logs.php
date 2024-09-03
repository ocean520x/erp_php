<?php
namespace Service;

use Library\Redis;

class Logs extends Base{

  /* 商品-日志 */
  static function Goods(array $data){
    $redis = new Redis();
    $redis->RPush('logs_goods', json_encode($data));
    $redis->Close();
  }
}
