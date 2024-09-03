<?php
namespace App\Admin;

use Config\Env;
use Library\Captcha;
use Library\Redis;
use Library\Safety;
use Model\User;
use Model\UserInfo;
use Service\AdminToken;
use Service\Base;
use Service\Data;

class Login extends Base{

  /* 验证码 */
  static function Vcode(string $tel){
    return;
    // 编码
    $code = Captcha::Vcode(4);
    // 缓存
    $redis = new Redis();
    $redis->Set('admin_vcode_' .$tel, strtolower($code));
    $redis->Expire('admin_vcode_' .$tel, 24*3600);
    $redis->Close();
  }

  /* 登陆 */
  static function Login(){
    // 参数
    $json = self::Json();
    $uname = self::JsonName($json, 'tel');
    $passwd = self::JsonName($json, 'passwd');
    $vcode = self::JsonName($json, 'vcode');
    $vcode_url = Env::BaseUrl('admin/user/vcode').'/'.$uname.'?'.time();
    // 验证用户名
    if(!Safety::IsRight('uname',$uname) && !Safety::IsRight('tel',$uname) && !Safety::IsRight('email',$uname)) return self::GetJSON(['code'=>4000, 'msg'=>'请输入用户名/手机/邮箱!']);
    // 登陆方式
    $where = '';
    $vcode = strtolower(trim($vcode));
    if($passwd){
      // 密码长度
      if(!Safety::IsRight('passwd', $passwd)) return self::GetJSON(['code'=> 4000, 'msg'=> '请输入6～16位密码!']);
      // 验证码
      $redis = new Redis();
      $code = $redis->Gets('admin_vcode_' .$uname);
      $redis->Close();
      if($code){
        self::Print('code', $code);
        if(strlen($vcode)!=4) return self::GetJSON(['code'=> 4001, 'msg'=> '请输入验证码!', 'vcode_url'=> $vcode_url]);
        elseif($vcode!=$code) return self::GetJSON(['code'=> 4002, 'msg'=> '验证码错误!', 'vcode_url'=> $vcode_url]);
      }
      // 条件
      $where = '(a.uname="' .$uname. '" OR a.tel="' .$uname. '" OR a.email="' .$uname. '") AND a.password="' .md5($passwd). '"';
    } else{
      // 验证码
      $redis = new Redis();
      $code = $redis->Gets('admin_vcode_' .$uname);
      $redis->Close();
      if(!$code || $code!=$vcode ) return self::GetJSON(['code'=> 4000, 'msg'=> '验证码错误!']);
      // 清除验证码
      $redis = new Redis();
      $redis->Expire('admin_vcode_' .$uname, 1);
      $redis->Close();
      // 条件
      $where = 'a.tel="' .$uname. '"';
    }
    // 模型
    $m = new User();
    $m->Table('user AS a');
    $m->LeftJoin('user_info AS b', 'a.id=b.uid');
    $m->LeftJoin('sys_perm AS c', 'a.id=c.uid');
    $m->LeftJoin('sys_role AS d', 'c.role=d.id');
    $m->Columns(
      'a.id', 'a.state', 'a.password', 'a.tel', 'a.email',
      'b.type', 'b.nickname', 'b.department', 'b.position', 'b.name', 'b.gender', 'b.birthday', 'b.img', 'b.signature',
      'c.perm', 'c.brand', 'c.shop', 'c.partner', 'c.partner_in',
      'd.perm as role_perm'
    );
    $m->Where($where);
    $data = $m->FindFirst();
    // 不存在
    if(empty($data)){
      // 缓存
      $redis = new Redis();
      $redis->Set('admin_vcode_' .$uname, time());
      $redis->Expire('admin_vcode_' .$uname, 24*3600);
      $redis->Close();
      return self::GetJSON(['code'=> 4000, 'msg'=> '账号或密码错误!', 'vcode_url'=> $vcode_url]);
    }
    // 是否禁用
    if($data['state']!='1') return self::GetJSON(['code'=> 4000, 'msg'=> '该用户已被禁用!']);
    // 清除验证码
    $redis = new Redis();
    $redis->Expire('admin_vcode_' .$uname, 1);
    $redis->Close();
    // 默认密码
    $isPasswd = $data['password']==md5('a123456');
    // 权限
    $perm = $data['role_perm'];
    if($data['perm']) $perm = $data['perm'];
    if(!$perm) return self::GetJSON(['code'=> 4000, 'msg'=> '该用户不允许登陆!']);
    AdminToken::savePerm($data['id'], $perm);
    // 登陆时间
    $ltime = time();
    $m->Table('user');
    $m->Set(['ltime'=> $ltime]);
    $m->Where('id=?', $data['id']);
    $m->Update();
    // Token
    $token = AdminToken::Create([
      'uid'=> $data['id'],
      'uname'=> $uname,
      'name'=> $data['name'],
      'type'=> $data['type'],
      'isPasswd'=> $isPasswd,
      'brand'=> $data['brand'],
      'shop'=> $data['shop'],
      'partner'=> $data['partner'],
      'partner_in'=> $data['partner_in']
    ]);
    // 用户信息
    $uinfo = [
      'uid'=> $data['id'],
      'uname'=> $uname,
      'tel'=> $data['tel'],
      'email'=> $data['email'],
      'ltime'=> date('Y-m-d H:i:s', $ltime),
      'type'=> $data['type'],
      'nickname'=> $data['nickname'],
      'department'=> $data['department'],
      'position'=> $data['position'],
      'name'=> $data['name'],
      'gender'=> $data['gender'],
      'birthday'=> $data['birthday'],
      'img'=> Data::Img($data['img'], false),
      'signature'=> $data['signature'],
    ];
    return self::GetJSON(['code'=> 0, 'msg'=> '成功', 'data'=> ['token'=> $token, 'uinfo'=> $uinfo, 'isPasswd'=> $isPasswd]]);

  }

  /* Token验证 */
  static function Token(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $is_uinfo = self::JsonName($json, 'uinfo');
    // 验证权限
    $msg = AdminToken::Verify($token);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    $tData = AdminToken::Token($token);
    // 用户信息
    $uinfo = (object)[];
    if($is_uinfo){
      $m = new User();
      $m->Table('user As a');
      $m->LeftJoin('user_info AS b', 'a.id=b.uid');
      $m->Columns(
        'FROM_UNIXTIME(a.ltime) AS ltime', 'a.tel', 'a.email',
        'b.type', 'b.nickname', 'b.department', 'b.position', 'b.name', 'b.gender', 'b.img', 'b.signature', 'FROM_UNIXTIME(b.birthday,"%Y-%m-%d") AS birthday'
      );
      $m->Where('id=?', $tData->uid);
      $uinfo = $m->FindFirst();
      $uinfo['uid'] = (string)$tData->uid;
      $uinfo['uname'] = $tData->uname;
      $uinfo['img'] = Data::Img($uinfo['img'], false);
    }
    // 返回
    return self::GetJSON(['code'=> 0, 'msg'=> '成功', 'data'=> ['token_time'=> $tData->time, 'uinfo'=> $uinfo, 'isPasswd'=> $tData->isPasswd]]);
  }

  /* 修改密码 */
  static function ChangePasswd(){
    // 参数
    $json = self::Json();
    $uname = self::JsonName($json, 'uname');
    $passwd = self::JsonName($json, 'passwd');
    // $vcode = self::JsonName($json, 'vcode');
    // 验证
    if(!Safety::IsRight('tel', $uname) && !Safety::IsRight('email', $uname)) return self::GetJSON(['code'=> 4000, 'msg'=> '无效账号!']);
    if(!Safety::IsRight('passwd', $passwd)) return self::GetJSON(['code'=> 4000, 'msg'=> '无效密码!']);
    // if(mb_strlen($vcode)!=4) return self::GetJSON(['code'=> 4000, 'msg'=> '无效验证码!']);
    // 验证码
    // $redis = new Redis();
    // $code = $redis->Gets('admin_vcode_' .$uname);
    // $redis->Close();
    // if($code!=$vcode) return self::GetJSON(['code'=> 4000, 'msg'=> '验证码错误!']);
    // 模型
    $m = new User();
    $m->Set(['password'=> md5($passwd)]);
    $m->Where('tel=? OR email=?', $uname, $uname);
    // 更新
    if($m->Update()){
      // 清除验证码
      $redis = new Redis();
      $redis->Expire('admin_vcode_' .$uname, 1);
      $redis->Close();
      return self::GetJSON(['code'=> 0, 'msg'=> '成功']);
    } else{
      return self::GetJSON(['code'=> 4000, 'msg'=> '更新失败']);
    }
  }

  /* 修改用户信息 */
  static function ChangeUinfo(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $uinfo = self::JsonName($json, 'uinfo');
    // 验证权限
    $msg = AdminToken::Verify($token);
    if($msg!='') return self::GetJSON(['code'=> 4001, 'msg'=> $msg]);
    if(empty($uinfo) || !is_array($uinfo)) return self::GetJSON(['code'=> 4000, 'msg'=> '参数错误!']);
    // 用户信息
    $data = [];
    if(isset($uinfo['nickname'])) $data['nickname'] = trim($uinfo['nickname']);
    if(isset($uinfo['gender'])) $data['gender'] = trim($uinfo['gender']);
    if(isset($uinfo['birthday'])) $data['birthday'] = strtotime($uinfo['birthday'])?:'';
    if(isset($uinfo['department'])) $data['department'] = trim($uinfo['department']);
    if(isset($uinfo['position'])) $data['position'] = trim($uinfo['position']);
    // 更新
    $admin = AdminToken::Token($token);
    $m = new UserInfo();
    $m->Set($data);
    $m->Where('uid=?', $admin->uid);
    if($m->Update()){
      return self::GetJSON(['code'=>0, 'msg'=>'成功']);
    }else{
      return self::GetJSON(['code'=>4000, 'msg'=>'更新失败!']);
    }
  }
}
