<?php
namespace App\Admin;

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

  /* 验证参数 */
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
}
