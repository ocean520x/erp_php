<?php
namespace Data;

use Model\ErpGoods;
use Service\Base;
use Service\Data as sData;

class Goods extends Base{

  /* 是否相同SKU */
  static function IsSkuSame(array $sku): string {
    $tmp = array_count_values($sku);
    foreach($tmp as $k=>$num){
      if($num>1) return $k;
    }
    return '';
  }

  /* 商品-信息多条 */
  static function GoodsInfoAll(array $sku, string $type = 'data'): array | string {
    $tmp = [];
    $sku = array_unique($sku);
    foreach($sku as $sku_id){
      $pname = sData::PartitionSku($sku_id, [-1, 3]);
      if(!$pname){
        $tmp=[];
        break;
      }
      $tmp = array_merge($tmp, explode(',', $pname));
    }
    // 分区
    if($tmp){
      $tmp = array_unique($tmp);
      sort($tmp);
      $tmp = array_values($tmp);
    }
    $pname = implode(',', $tmp);
    if($type=='pname') return $pname;
    // 数据
    $list = self::getGoodsData($sku, $pname);
    $data = [];
    foreach($list as $v) $data[$v['sku_id']] = $v;
    return $data;
  }

  private static function getGoodsData(array $sku, string $partition = '') {
    $m = new ErpGoods();
    if($partition) $m->Partition($partition);
    $m->Columns(
      'id', 'img', 'sku_id', 'i_id', 'owner', 'name', 'short_name', 'properties_value', 'cost_price', 'purchase_price', 'supply_price', 'supplier_price', 'sale_price', 'market_price', 'unit', 'weight', 'num', 'category', 'labels', 'brand', 'supplier_id', 'supplier_name',
      'FROM_UNIXTIME(ctime) as ctime', 'FROM_UNIXTIME(utime) as utime',
    );
    $m->Where('sku_id in("' .implode('","', $sku). '")');
    $m->Order('id DESC');
    return $m->Find();
  }
}
