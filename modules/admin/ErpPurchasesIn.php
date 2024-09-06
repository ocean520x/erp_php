<?php
namespace App\Admin;

use Data\Goods;
use Model\ErpPurchaseIn as ModelErpPurchaseIn;
use Model\ErpPurchaseShowIn;
use Service\AdminToken;
use Service\Base;
use Service\Logs;

class ErpPurchasesIn extends Base{

  /* 新增 */
  static function Add(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 校验数据
    $validate = self::VerifyData($data);
    if(!empty($validate)) return $validate;
    $remark = isset($data['remark'])?trim($data['remark']):'';
    // 模型
    $m = new ModelErpPurchaseIn();
    $admin = AdminToken::Token($token);
    $m->Values(['type'=> $data['type'], 'wms_co_id'=> $data['wms_co_id'], 'creater_id'=> $admin->uid, 'creater_name'=> $admin->name, 'operator_id'=> $admin->uid, 'operator_name'=> $admin->name, 'remark'=> $remark, 'ctime'=> time(), 'utime'=> time()]);
    if($m->Insert()){
      $id = $m->GetID();
      Logs::Goods([
        'ctime'=> time(),
        'operator_id'=> $admin->uid,
        'operator_name'=> $admin->name,
        'sku_id'=> $id,
        'content'=> '创建入库单: ' .$id
      ]);
      return self::GetJSON(['code'=> 0, 'msg'=> '成功']);
    } else{
      return self::GetJSON(['code'=> 5000, 'msg'=> '添加失败!']);
    }
  }

  /* 编辑 */  
  static function Edit(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    $id = $data['id'];
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 校验数据
    $validate = self::VerifyData($data, 'edit');
    if(!empty($validate)) return $validate;
    // 模型
    $m = new ModelErpPurchaseIn();
    $admin = AdminToken::Token($token);
    $m->Set(['type'=> $data['type'], 'wms_co_id'=> $data['wms_co_id'], 'operator_id'=> $admin->uid, 'operator_name'=> $admin->name, 'remark'=> $data['remark'], 'utime'=> time()]);
    $m->Where('id=? AND state="0"', $id);
    if($m->Update()) {
      // 明细
      $m = new ErpPurchaseShowIn();
      $m->Set(['wms_co_id'=> $data['wms_co_id']]);
      $m->Where('pid=?', $id);
      $m->Update();
      // 日志
      Logs::Goods([
        'ctime'=>time(),
        'operator_id'=> $admin->uid,
        'operator_name'=> $admin->name,
        'sku_id'=> $id,
        'content'=> '更新入库单: '.$id
      ]);
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 删除 */
  static function Del(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $ids = implode(',', $data);
    // 模型
    // 明细
    $m1 = new ErpPurchaseShowIn();
    $m1->Where('pid in(' .$ids. ')');
    // 单据
    $m2 = new ModelErpPurchaseIn();
    $m2->Where('id in(' .$ids. ') AND state="0"');
    if($m1->Delete() && $m2->Delete()){
      // 日志
      $admin = AdminToken::Token($token);
      $pids = explode(',', $ids);
      foreach($pids as $pid){
        Logs::Goods([
          'ctime'=> time(),
          'operator_id'=> $admin->uid,
          'operator_name'=> $admin->name,
          'sku_id'=> $pid,
          'content'=> '删除入库单: ' .$pid 
        ]);
      }
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    }
    return self::GetJSON(['code'=>5000,'msg'=>'删除失败!']);
  }

  /* 确认入库 */
  static function Push(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $ids = implode(',', $data);
    $total = self::GoodsTotal($ids);
    if(!is_array($total)) return self::GetJSON(['code'=>4000, 'msg'=>$total]);
    // 更新
    $admin = AdminToken::Token($token);
    $m = new ModelErpPurchaseIn();
    $m->Set([
      'brand'=> implode(',', $total['brand']),
      'cost_price'=> $total['cost_price'],
      'sale_price'=> $total['sale_price'],
      'num'=> $total['num'],
      'total'=> $total['total'],
      'state'=> '1',
      'operator_id'=> $admin->uid,
      'operator_name'=> $admin->name,
      'utime'=> time()
    ]);
    $m->Where('id=? AND state="0"', $ids);
    if($m->Update()){
      // 日志
      Logs::Goods([
        'ctime'=> time(),
        'operator_id'=> $admin->uid,
        'operator_name'=> $admin->name,
        'sku_id'=> $ids,
        'content'=> '推送入库单: ' .$ids. ' 数量: ' .$total['num']
      ]);
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else{
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  // 验证参数 
  private static function VerifyData(array $data, $mode = 'add'){
    if(empty($data)){
      return self::GetJSON(['code' => 4000, 'msg' => '参数错误!']);
    }
    if($mode == 'edit' && empty($data['id'])){
      return self::GetJSON(['code' => 4000, 'msg' => 'id不能为空!']);
    }
    if(empty($data['type'])&&$data['type'] != 0) {
      return self::GetJSON(['code' => 4000, 'msg' => '类型不能为空!']);
    }
    if(empty($data['wms_co_id'])) {
      return self::GetJSON(['code' => 4000, 'msg' => '仓库不能为空!']);
    }
  }

  // 商品统计
  private static function GoodsTotal(int $pid): array | string{
    // SKU是否相同
    $m = new ErpPurchaseShowIn();
    $m->Columns('sku_id', 'num');
    $m->Where('pid=?', $pid);
    $all = $m->Find();
    $sku = [];
    foreach($all as $v) $sku[] = $v['sku_id'];
    if(!$sku) return '请扫码商品!';
    $res = Goods::IsSkuSame($sku);
    if($res) return '[' .$res. ' ]商品编码重复!';
    // 商品资料
    $info = Goods::GoodsInfoAll($sku);
    // 统计
    $total = ['total'=> 0, 'num'=> 0, 'cost_price'=> 0, 'sale_price'=> 0, 'brand'=> []];
    foreach($all as $v){
      if(!isset($info[$v['sku_id']])) continue;
      $total['total']++;
      $total['num'] += $v['num'];
      $total['cost_price'] += $info[$v['sku_id']]['cost_price'] * $v['num'];
      $total['sale_price'] += $info[$v['sku_id']]['sale_price'] * $v['num'];
      if(!in_array($info[$v['sku_id']]['brand'], $total['brand'])) $total['brand'][] = $info[$v['sku_id']]['brand'];
    }
    return $total;
  }
}
