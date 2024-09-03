<?php
namespace Config;

class Redis{

  /* 默认 */
  static function Default(): array{
    return [
      'host'=> 'panel.ocean520x.icu',
      'port'=> 6379,
      'password'=> 'redis_JXaFsT',
      'db'=> 0,
      'timeout'=> 10,
    ];
  }

  /* 其他 */
  static function Other(): array{
    return [
      'host'=> '127.0.0.1',           // 主机
      'port'=> 6379,                  // 端口
      'password'=> '123456',          // 密码
      'db'=> 0,                       // 硬盘
      'timeout'=> 10,                 // 阻塞时间（秒）
    ];
  }
}
