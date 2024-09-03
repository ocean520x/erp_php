<?php
namespace Utils;

class Type{

  /* 转换string、int、float */
  static function ToType(string $type, $val) {
    switch($type) {
      case 'string':
        return (string)$val;
      case 'int':
        return (int)$val;
      case 'float':
        return (float)$val;
      default :
        return $val;
    }
  }
}
