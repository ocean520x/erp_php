<?php
namespace Service;

use Config\Env;
use Library\Redis;
use Library\Safety;
use Model\SysMenus;

class AdminToken extends Base{

  /* 验证 token */
  static function Verify(string $token, string $urlPerm = ''): string{
    //Token
    if($token=='') return '请重新登陆!';
    $tData = Safety::Decode($token);
    if(!$tData) return 'Token验证失败!';
    // 是否过期
    $uid = (string)$tData->uid;
    $key = Env::$admin_token_prefix .'_token_' .$uid;
    $redis = new Redis();
    $access_token = $redis->Gets($key);
    $time = $redis->Ttl($key);
    $redis->Close();
    if(Env::$admin_token_sso && md5($token)!=$access_token) return '强制退出!';
    if($time<1) return 'Token已过期!';
    // 续期
    if(Env::$admin_token_auto){
      $redis = new Redis();
      $redis->Expire($key, Env::$admin_token_time);
      $redis->Expire(Env::$admin_token_prefix .'_perm_' .$uid, Env::$admin_token_time);
      $redis->Close();
    }
    // URL 权限
    if($urlPerm=='') return '';
    $arr = explode('/', $urlPerm);
    $action = end($arr);
    array_pop($arr);
    $controller = implode('/', $arr);
    // 模型
    $menu = new SysMenus();
    $menu->Columns('id', 'action');
    $menu->Where('controller=?', $controller);
    $menuData = $menu->FindFirst();
    if(empty($menuData)) return '菜单验证无效!';
    // 验证-菜单
    $id = (string)$menuData['id'];
    $permData = self::getPerm($token);
    if(!isset($permData[$id])) return '无权访问菜单!';
    // 验证-动作
    $actionVal = (int)$permData[$id];
    $permArr = json_decode($menuData['action']);
    $permVal = 0;
    foreach($permArr as $val){
      if($action==$val->action){
        $permVal = (int)$val->perm;
        break;
      }
    }
    if(($actionVal&$permVal)==0) return '无权访问动作!';
    return '';
  }

  /* 权限-拆分 */
  static function getPerm(string $token): array{
    $permAll = [];
    // Token
    $tData = Safety::Decode($token);
    if(!$tData) return $permAll;
    // 权限
    $redis = new Redis();
    $permStr = $redis->Gets(Env::$admin_token_prefix . '_perm_' .$tData->uid);
    $redis->Close();
    // 拆分
    $arr = !empty($permStr)?explode(' ', $permStr):[];
    foreach($arr as $val){
      $s = explode(':', $val);
      $permAll[$s[0]] = (int)$s[1];
    }
    return $permAll;
  }
  
  /* 权限-保存 */
  static function savePerm($uid, string $perm): bool{
    $redis = new Redis();
    $key = Env::$admin_token_prefix .'_perm_' .$uid;
    $redis->Set($key, $perm);
    $redis->Expire($key, Env::$admin_token_time);
    $redis->Close();
    return true;
  }

  /* 生成token */
  static function Create(array $data): ?string{
    $data['l_time'] = date('Y-m-d H:i:s');
    $token = Safety::Encode($data);
    // 缓存
    $redis = new Redis();
    $key = Env::$admin_token_prefix .'_token_' .$data['uid'];
    $redis->Set($key, md5($token));
    $res = $redis->Expire($key, Env::$admin_token_time);
    $redis->Close();
    return $token;
  }

  /* 解析 */
  static function Token(string $token){
    $tData = Safety::Decode($token);
    if($tData){
      $redis = new Redis();
      $tData->time = $redis->Ttl(Env::$admin_token_prefix .'_token_' .$tData->uid);
      $redis->Close();
    }
    return $tData;
  }
}
