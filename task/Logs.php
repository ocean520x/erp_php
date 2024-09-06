<?php
namespace Task;

use Library\Redis;
use Model\ErpGoodsLogs;
use Service\Base;

class Logs extends Base{

  /* 商品日志 */
  static function Goods(){
    $n = 1000;
    $t = 10;
    $key = 'logs_goods';
    while(true){
      $redis = new Redis();
      self::Print('Logs', $redis->Gets($key));
      $data = $redis->LRange($key, 0, $n);
      if(!$data){
        $redis->Close();
        sleep($t);
        continue;
      }
      $msg = [];
      foreach($data as $v){
        $res = $redis->LPop($key);
        $msg[] = json_decode($res, true);
      }
      $redis->Close();
      self::goodsWrite($msg);
    }
  }

  /* 商品-写入日志 */
  private static function goodsWrite(array $data) {
    $m = new ErpGoodsLogs();
    $m->ValuesAll($data);
    $m->Insert();
  }
}
