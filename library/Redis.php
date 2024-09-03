<?php
namespace Library;

use Service\Base;
use Config\Redis as Cfg;
class Redis extends Base{

  static $RedisDB = null;            //默认池
  static $RedisDBOther = null;      // 其他池

  private $db = '';                 // 数据库
  private $conn = null;             // 连接

  function __construct(string $db = ''){
    $this->db = $db;
    $this->RedisConn();
  }

  /* 连接 */
  function RedisConn(){
    if($this->db=='other'){
      if(!Redis::$RedisDBOther) Redis::$RedisDBOther = $this->RedisPool(Cfg::Other());
      $this->conn = self::$RedisDBOther;
    } else{
      if(!Redis::$RedisDB) Redis::$RedisDB = $this->RedisPool(Cfg::Default());
      $this->conn = self::$RedisDB;
    }
    return $this->conn;
  }

  /* 关闭连接 */
  function Close(){
    if(!$this->conn) return;
    $this->conn->close();
    $this->conn = null;
  }

  /* 获取 */
  function Gets(string $key){
    if(!$this->conn) return;
    return $this->conn->get($key);
  }

  /* 添加 */
  function Set(string $key, string $val){
    if(!$this->conn) return;
    return $this->conn->set($key, $val);
  }

  /* 获取过期时间（秒）*/
  function Ttl(string $key){
    if(!$this->conn) return;
    return $this->conn->ttl($key);
  }

  /* 设置过期时间（秒）*/
  function Expire(string $key, int $ttl){
    if(!$this->conn) return;
    return $this->conn->expire($key, $ttl);
  }

  /* 列表（List）写入 */
  function RPush(string $key, $val){
    if(!$this->conn) return;
    return $this->conn->rpush($key, $val);
  }

  /* 数据池 */
  function RedisPool(array $cfg){
    try{
      $redis = new \Redis();
      $redis->pconnect($cfg['host'], $cfg['port']);
      if($cfg['password']) $redis->auth($cfg['password']);
      $redis->select($cfg['db']);
      return $redis;
    }catch(\Exception $e){
      self::Print('[Redis] Conn: 请检测Redis是否启动!');
      return null;
    }
  }
}
