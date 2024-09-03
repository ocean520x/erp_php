<?php
namespace Service;

class Base{

  /* JSON参数 */
  static function Json(): array{
    if($_SERVER['REQUEST_METHOD']=='GET') return $_GET;
    if($_POST) return $_POST;
    $param = file_get_contents('php://input');
    $data = $param?@json_decode($param, true):[];
    return $data?:[];
  }

  static function JsonName(array $param, string $name){
    return isset($param[$name])?$param[$name]:'';
  }

  /* 打印到控制台 */
  static function Print(...$content): void{
    foreach($content as $val){
      fwrite(STDERR, self::toString($val). ' ');
    }
    fwrite(STDERR, PHP_EOL);
  }

  static private function toString($val): string {
    if(gettype($val)=='array') $val = json_encode($val, JSON_UNESCAPED_UNICODE);
    elseif(gettype($val)=='object') $val = json_encode($val, JSON_UNESCAPED_UNICODE);
    else $val = (string)$val;
    return $val;
  }

  static function GetJSON(array $data=[]): string {
    header('Content-type: application/json; charset=utf-8');
    return json_encode($data);
  }
}
