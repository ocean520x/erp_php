<?php
namespace App\Admin;

use Config\Env;
use Library\Redis;
use Library\Safety;
use Model\SysPerm;
use Model\SysRole;
use Model\User as ModelUser;
use Model\UserInfo;
use Service\AdminToken;
use Service\Base;
use Service\Data;

class User extends Base{

  /* 列表 */
  static function List(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    $page = self::JsonName($json, 'page');
    $limit = self::JsonName($json, 'limit');
    $order = self::JsonName($json, 'order');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($page) || empty($limit)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $where = self::getWhere($data);
    // 模型
    $m = new ModelUser();
    $m->Columns('count(*) AS num');
    $m->Table('user AS a');
    $m->LeftJoin('user_info AS b', 'a.id=b.uid');
    $m->LeftJoin('sys_perm AS c', 'a.id=c.uid');
    $m->Where($where);
    $total = $m->FindFirst();
    // 查询
    $m->Columns(
      'a.id AS uid', 'a.uname', 'a.email', 'a.tel', 'a.state', 'FROM_UNIXTIME(a.rtime) AS rtime', 'FROM_UNIXTIME(a.ltime) AS ltime', 'FROM_UNIXTIME(a.utime) AS utime',
      'b.type', 'b.nickname', 'b.department', 'b.position', 'b.name', 'b.gender', 'b.img', 'b.remark', 'FROM_UNIXTIME(b.birthday,"%Y-%m-%d") AS birthday',
      'c.role AS sys_role', 'c.perm AS sys_perm', 'c.brand AS sys_brand', 'c.shop AS sys_shop', 'c.partner AS sys_partner', 'c.partner_in AS sys_partner_in'
    );
    $m->Where($where);
    $m->Order($order?:'a.id DESC');
    $m->Page($page, $limit);
    $list = $m->Find();
    $m = new SysRole();
    $m->Columns('id', 'name');
    $all = $m->Find();
    $role = [];
    foreach($all as $v) $role[$v['id']] = $v['name'];
    // 处理数据
    foreach($list as $k=> $v){
      $list[$k]['state'] = $v['state']?true:false;
      $list[$k]['sys_role_name'] = $v['sys_role']?$role[$v['sys_role']]:'';
      $list[$k]['img'] = Data::Img($v['img']);
      if(!$v['sys_role']) $list[$k]['sys_role']='';
      if(!$v['sys_perm']) $list[$k]['sys_perm']='';
      if(!$v['sys_brand']) $list[$k]['sys_brand']='';
      if(!$v['sys_partner']) $list[$k]['sys_partner']='';
    }
    return self::GetJSON(['code'=>0, 'msg'=>'成功', 'time'=>date('Y/m/d H:i:s'), 'total'=>(int)$total['num'], 'list'=>$list]);
  }

  /* 注册用户 */
  static function Add(){
    // 参数
    $json = self::Json();
    $data = self::JsonName($json, 'data');
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $tel = isset($data['tel'])?trim($data['tel']):'';
    $passwd = isset($data['password'])?trim($data['password']):'';
    // 验证手机号和密码
    if(!Safety::IsRight('tel', $tel)) return self::GetJSON(['code'=>4000, 'msg'=>'手机号码有误!']);
    if(!Safety::IsRight('passwd', $passwd)) return self::GetJSON(['code'=>4000, 'msg'=>'密码为6～16位!']);
    // 查询当前用户是否存在
    $m = new ModelUser();
    $m->Columns('id');
    $m->Where('tel=?', $tel);
    $user = $m->FindFirst();
    if(!empty($user)) return self::GetJSON(['code'=>4000, 'msg'=>'该用户已存在!']);
    // 用户
    $m1 = new ModelUser();
    $m1->Values(['tel'=>$tel, 'password'=>md5($passwd), 'rtime'=>time()]);
    $m1->Insert();
    $uid = $m1->GetID();
    // 用户信息
    $m2 = new UserInfo();
    $m2->Values(['uid'=>$uid]);
    if($m2->Insert()){
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    }else{
      return self::GetJSON(['code'=>5000,'msg'=>'添加失败!']);
    }
  }

  /* 编辑用户 */
  static function Edit(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $uid = self::JsonName($json, 'uid');
    $data = self::JsonName($json, 'data');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($uid) || empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $tel = isset($data['tel'])?trim($data['tel']):'';
    $passwd = isset($data['password'])?trim($data['password']):'';
    if(!Safety::IsRight('tel', $tel)) return self::GetJSON(['code'=>4000, 'msg'=>'手机号码有误!']);
    if(!Safety::IsRight('passwd', $passwd)) return self::GetJSON(['code'=>4000, 'msg'=>'密码为6～16位!']);
    // 查询当前用户是否存在
    $m = new ModelUser();
    $m->Columns('id');
    $m->Where('tel=?', $tel);
    $user = $m->FindFirst();
    if(!empty($user)&&$user['id']!=$uid) return self::GetJSON(['code'=>4000, 'msg'=>'该用户已存在!']);
    // 模型
    $m->Set(['tel'=>$tel, 'password'=>md5($passwd)]);
    $m->Where('id=?', $uid);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 删除用户 */
  static function Del(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 数据
    $ids = implode(',', $data);
    // 模型
    $m1 = new ModelUser();
    $m1->Where('id in('.$ids.')');
    $m2 = new UserInfo();
    $m2->Where('uid in('.$ids.')');
    // $m3 = new SysPerm();
    // $m3->Where('uid in('.$ids.')');
    // $m4 = new UserSupplierVerify();
    // $m4->Where('uid in('.$ids.')');
    // if($m1->Delete() && $m2->Delete() && $m3->Delete() && $m4->Delete()) {
    //   return self::GetJSON(['code'=>0,'msg'=>'成功']);
    // } else {
    //   return self::GetJSON(['code'=>5000,'msg'=>'删除失败!']);
    // }
    if($m1->Delete() && $m2->Delete()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'删除失败!']);
    }
  }

  /* 修改用户状态 */
  static function State(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $uid = self::JsonName($json, 'uid');
    $state = self::JsonName($json, 'state');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($uid)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 超级管理员
    // $admin = AdminToken::Token($token);
    // if($uid==1 && $admin->uid!=1) return self::GetJSON(['code'=>4000, 'msg'=>'您不是超级管理员!']);
    // 模型
    $state = $state=='1'?'1':'0';
    $m = new ModelUser();
    $m->Set(['state'=>$state]);
    $m->Where('id=?', $uid);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 设置用户权限 */
  static function Perm(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $type = self::JsonName($json, 'type');
    $uid = self::JsonName($json, 'uid');
    $role = self::JsonName($json, 'role');
    $perm = self::JsonName($json, 'perm');
    $brand = self::JsonName($json, 'brand');
    $shop = self::JsonName($json, 'shop');
    $partner = self::JsonName($json, 'partner');
    $partner_in = self::JsonName($json, 'partner_in');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($type) || empty($uid)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 超级管理员
    $tData = AdminToken::Token($token);
    if($uid==1 && $tData->uid!=1) return self::GetJSON(['code'=>4000, 'msg'=>'您不是超级管理员!']);
    // 类型
    if($type=='admin' && self::_permSys($uid, $role, $perm, $brand, $shop, $partner, $partner_in)) return self::GetJSON(['code'=> 0, 'msg'=> '成功']);
    // elseif($type=='api' && self::_permApi($uid, $role, $perm)) return self::GetJSON(['code'=> 0, 'msg'=> '成功']);
    else return self::GetJSON(['code'=> 5000, 'msg'=> '更新失败!']);
  }

  /* 修改用户信息 */
  static function Info(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $uid = self::JsonName($json, 'uid');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($uid) || empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $info = [
      'type'=> isset($data['type'])?trim($data['type']):'',
      'nickname'=> isset($data['nickname'])?trim($data['nickname']):'',
      'name'=> isset($data['name'])?trim($data['name']):'',
      'gender'=> isset($data['gender'])?trim($data['gender']):'',
      'birthday'=> isset($data['birthday'])?strtotime($data['birthday']):0,
      'department'=> isset($data['department'])?trim($data['department']):'',
      'position'=> isset($data['position'])?trim($data['position']):'',
      'remark'=> isset($data['remark'])?trim($data['remark']):'',
    ];
    // 模型
    $m = new UserInfo();
    $m->Set($info);
    $m->Where('uid=?', $uid);
    if($m->Update()){
      return self::GetJSON(['code'=> 0, 'msg'=> '成功']);
    } else{
      return self::GetJSON(['code'=> 5000, 'msg'=> '更新失败!']);
    }
  }

  /* 角色列表 */
  static function RoleList(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    // 验证权限
    $msg = AdminToken::Verify($token);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 模型
    $m = new SysRole();
    $m->Columns('id', 'name');
    $all = $m->Find();
    $list = [];
    foreach($all as $v) $list[] = ['label'=> $v['name'], 'value'=> $v['id']];
    return self::GetJSON(['code'=> 0, 'msg'=> '成功', 'data'=> $list]);
  }

  // 搜索条件
  private static function getWhere(array $param): string{
    $where = [];
    // 关键字
    $key = isset($param['key'])?trim($param['key']):'';
    if($key){
      $arr = [
        'a.tel like "%' .$key. '%"',
        'b.nickname like "%' .$key. '%"',
        'b.name like "%' .$key. '%"',
        'b.department like "%' .$key. '%"',
        'b.position like "%' .$key. '%"',
        'b.remark like "%' .$key. '%"'
      ];
      $where[] = '(' .implode(' OR ', $arr). ')';
    }
    // 状态
    $state = isset($param['state'])?trim($param['state']):'';
    if($state!='') $where[] = 'a.state="' .$state. '"';
    // 类型
    $type = isset($param['type'])?trim($param['type']):'';
    if($type!='') $where[] = 'b.type="' .$type. '"';
    // 角色
    $role = isset($param['role'])?$param['role']:[];
    if($role) $where[] = 'c.role in(' .implode(',', $role). ')';
    // 账号
    $uname = isset($param['uname'])?trim($param['uname']):'';
    if($uname) $where[] = '(a.uname LIKE "%' .$uname. '%" OR a.tel LIKE "%' .$uname. '%" OR a.email LIKE "%' .$uname. '%")';
    // 昵称
    $nickname = isset($param['nickname'])?trim($param['nickname']):'';
    if($nickname) $where[] = 'b.nickname LIKE "%' .$nickname. '%"';
    // 姓名
    $name = isset($param['name'])?trim($param['name']):'';  
    if($name) $where[] = 'b.name LIKE "%' .$name. '%"';
    // 部门
    $department = isset($param['department'])?trim($param['department']):'';  
    if($department) $where[] = 'b.department LIKE "%' .$department. '%"';
    // 职务
    $position = isset($param['position'])?trim($param['position']):'';
    if($position) $where[] = 'b.position LIKE "%' .$position. '%"';
    // 备注
    $remark = isset($param['remark'])?trim($param['remark']):'';
    if($remark) $where[] = 'b.remark LIKE "%' .$remark. '%"';
    return implode(' AND ', $where);
  }

  // 权限 System
  private static function _permSys($uid, $role, $perm, $brand, $shop, $partner, $partner_in){
    // 数据
    $uData = ['perm'=> $perm, 'role'=> $role, 'brand'=> $brand, 'shop'=> $shop, 'partner'=> $partner, 'partner_in'=> $partner_in, 'utime'=>time()];
    // 模型
    $m = new SysPerm();
    $m->Columns('uid');
    $m->Where('uid=?', $uid);
    $one = $m->FindFirst();
    if($one){
      $m->Set($uData);
      $m->Where('uid=?', $uid);
      $m->Update();
    } else{
      $uData['uid'] = $uid;
      $uData['utime'] = time();
      $m->Values($uData);
      $m->Insert();
    }
    // 角色权限
    if(empty($perm)){
      $m1 = new SysRole();
      $m1->Columns('perm');
      $m1->Where('id=?', $role);
      $data = $m1->FindFirst();
      $perm = isset($data['perm'])?$data['perm']:'';
    }
    return self::_setPerm(Env::$admin_token_prefix .'_perm_' .$uid, $perm);
  }

  // 权限 System
  // private static function _permApi($uid, $role, $perm){
  //   // 数据
  //   $uData = ['perm'=> $perm, 'role'=> $role, 'utime'=>time()];
  //   // 模型
  //   $m = new
  // }

  // 更新权限
  private static function _setPerm(string $key, string $perm): bool{
    $redis = new Redis();
    $redis->Set($key, $perm);
    $redis->Close();
    return true;
  } 
}
