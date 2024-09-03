<?php
namespace Config;

class Db{
  /* 默认数据库 */
  static function Default(): array{
    return [
      'driver'=> 'mysql',
      'host'=> '123.207.248.197',
      'port'=> 3306,
      'username'=> 'erp_db',
      'password'=> '123456',
      'dbname'=> 'erp_db',
      'charset'=> 'utf8mb4',
      'persistent'=> false
    ];
  }

  /* 其他数据库 */
  static function Other(): array{
    return [
      'driver'=> 'mysql',
      'host'=> '127.0.0.1',
      'port'=> 3306,
      'username'=> 'root',
      'password'=> '123456',
      'dbname'=> 'erp_db',
      'charset'=> 'utf8mb4',
      'persistent'=> false
    ];
  }
}
