<?php
namespace Service;

use Config\Env;

class Data extends Base{
  
  // 分区时间
  static public $partition = [
    'p2208'=> 1661961600,
    'p2209'=> 1664553600,
    'p2210'=> 1667232000,
    'p2211'=> 1669824000,
    'p2212'=> 1672502400,
    'p2301'=> 1675180800,
    'p2302'=> 1677600000,
    'p2303'=> 1680278400,
    'p2304'=> 1682870400,
    'p2305'=> 1685548800,
    'p2306'=> 1688140800,
    'p2307'=> 1690819200,
    'p2308'=> 1693497600,
    'p2309'=> 1696089600,
    'p2310'=> 1698768000,
    'p2311'=> 1701360000,
    'p2312'=> 1704038400,
    'p2401'=> 1706716800,
    'p2402'=> 1709222400,
    'p2403'=> 1711900800,
    'p2404'=> 1714492800,
    'p2405'=> 1717171200,
    'p2406'=> 1719763200,
    'p2407'=> 1722441600,
    'plast'=> 1722441600,
  ];
  /* 图片地址 */
  static function Img(string $img, bool $isTmp = true): string {
    if(!$img) return '';
    return $isTmp?Env::$img_url .$img:Env::$img_url . $img. '?' .time();
  }

  /* 分区- SKU定位 */
  static function PartitionSku(string $sku_id, array $limt = []): string {
    $pname = '';
    // 排除
    if(substr($sku_id, 0, 2)=='19' || substr($sku_id, 0, 3)=='P19' || substr($sku_id, 0, 6)=='230100') return $pname;
    // 是否日期
    $day = self::PartitionSkuData($sku_id);
    if($day) $pname = 'p' .substr($day, 0, 4);
    if(!$pname) return $pname;
    // 分区
    $keys = array_keys(self::$partition);
    if(!in_array($pname, $keys)) $pname = 'plast';
    // 容差
    if(!$limt) return $pname;
    $index = array_search($pname, $keys);
    $partition = array_slice($keys, $index+$limt[0], $limt[1]);
    return implode(',', $partition);
  }

  /* 分区-SKU日期 */
  static function PartitionSkuData(string $sku_id): string {
    // 是否日期
    $str0 = substr($sku_id, 0, 6);
    $str1 = substr($sku_id, 1, 6);
    $str2 = substr($sku_id, 2, 6);
    $str0_1 = substr($sku_id, 0, 2) .'0'. substr($sku_id, 2, 3);
    $str1_1 = substr($sku_id, 1, 2) .'0'. substr($sku_id, 3, 3);
    $str2_1 = substr($sku_id, 2, 2) .'0'. substr($sku_id, 4, 3);
    // 日期
    if(self::isDate($str0)) return $str0;
    elseif(self::isDate($str1)) return $str1;
    elseif(self::isDate($str2)) return $str2;
    elseif(self::isDate($str0_1)) return $str0_1;
    elseif(self::isDate($str1_1)) return $str1_1;
    elseif(self::isDate($str2_1)) return $str2_1;
    return '';
  }

  // 是否日期
  private static function isDate($dateStr): bool {
    $res = substr(date('Ymd', strtotime('20'. $dateStr)), -6);
    return $dateStr==$res;
  }
}
