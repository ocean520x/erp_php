<?php
namespace App\Admin;

use Model\SysMenus as ModelSysMenus;
use Service\AdminToken;
use Service\Base;

class SysMenus extends Base{

  private static $menus = [];        // 全部菜单
  private static $permAll = [];      //用户权限
  /* 菜单列表 */
  static function List(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 统计
    $m = new ModelSysMenus();
    $m->Columns('count(*) AS total');
    $total = $m->FindFirst();
    // 全部菜单
    self::_getMenus();
    return self::GetJSON(['code'=>0, 'msg'=>'成功', 'menus'=>self::_getMenusAll('0')]);
  }

  /* 新增菜单 */
  static function Add(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $title = isset($data['title'])?trim($data['title']):'';
    if($title=='') return self::GetJSON(['code'=>4000, 'msg'=>'菜单名称不能为空!']);
    // 模型
    $m = new ModelSysMenus();
    $m->Values([
      'fid'=> isset($data['fid'])&&!empty($data['fid'])?trim($data['fid']):0,
      'title'=> $title,
      'en'=> isset($data['en'])?trim($data['en']):'',
      'url'=> isset($data['url'])?trim($data['url']):'',
      'ico'=> isset($data['ico'])?trim($data['ico']):'',
      'sort'=> isset($data['sort'])?trim($data['sort']):0,
      'controller'=> isset($data['controller'])?trim($data['controller']):'',
      'ctime'=> time()
    ]);
    if($m->Insert()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'添加失败!']);
    }
  }

  /* 删除菜单 */
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
    $m = new ModelSysMenus();
    $m->Where('id in (' .$ids. ')');
    if($m->Delete()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'删除失败!']);
    }
  }

  /* 编辑菜单 */
  static function Edit(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $id = self::JsonName($json, 'id');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($id) || empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $title = isset($data['title'])?trim($data['title']):'';
    if($title=='') return self::GetJSON(['code'=>4000, 'msg'=>'菜单名称不能为空!']);
    // 模型
    $m = new ModelSysMenus();
    $m->Set([
      'fid'=> isset($data['fid'])&&!empty($data['fid'])?trim($data['fid']):0,
      'title'=> $title,
      'en'=> isset($data['en'])?trim($data['en']):'',
      'url'=> isset($data['url'])?trim($data['url']):'',
      'ico'=> isset($data['ico'])?trim($data['ico']):'',
      'sort'=> isset($data['sort'])?trim($data['sort']):0,
      'controller'=> isset($data['controller'])?trim($data['controller']):'',
      'utime'=> time()
    ]);
    $m->Where('id=?', $id);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 动作权限 */
  static function Perm(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $id = self::JsonName($json, 'id');
    $data = self::JsonName($json, 'data');
    // 验证权限
    $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($id) || empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 模型
    $m = new ModelSysMenus();
    $m->Set(['action'=> $data]);
    $m->Where('id=?', $id);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 获取全部菜单 */
  static function GetMenusAll(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    // 验证权限
    $msg = AdminToken::Verify($token);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 全部菜单
    self::_getMenus();
    return self::GetJSON(['code'=>0, 'msg'=>'成功', 'menus'=>self::_getMenusAll('0')]);
  }

  /* 获取菜单权限 */
  static function GetMenusPerm(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $path = self::JsonName($json, 'path');
    // 验证权限
    $msg = AdminToken::Verify($token);
    if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 全部菜单
    self::_getMenus();
    // 用户权限
    self::$permAll = AdminToken::getPerm($token);
    $menus = $path?self::_getMenusPerm('0', $path):self::_getMenusPerm('0');
    return self::GetJSON(['code'=>0, 'msg'=>'成功', 'data'=>$menus, 'menus'=>$menus]);
  }


  /* 全部菜单 */
  private static function _getMenus(){
    $model = new ModelSysMenus();
    $model->Columns('id', 'fid', 'title', 'en', 'url', 'ico', 'controller', 'action', 'sort', 'FROM_UNIXTIME(ctime) as ctime', 'FROM_UNIXTIME(utime) as utime');
    $model->Order('sort, id');
    $data = $model->Find();
    foreach($data as $val){
      $fid = $val['fid'];
      self::$menus[$fid][] = $val;
    }
  }

  /* 递归菜单 */
  private static function _getMenusAll(string $fid){
    $data = [];
    $M = isset(self::$menus[$fid])?self::$menus[$fid]:[];
    foreach($M as $val){
      $id = $val['id'];
      $tmp = ['label'=> $val['title'], 'value'=> $id];
      $menu = self::_getMenusAll($id);
      if(!empty($menu)) $tmp['children'] = $menu;
      $data[] = $tmp;
    }
    return $data;
  }

  // 递归菜单
  private static function _getMenusPerm(string $fid, $path=''){
    $data = [];
    $M = isset(self::$menus[$fid])?self::$menus[$fid]:[];
    foreach($M as $val){
      $id = (string)$val['id'];
      if(!isset(self::$permAll[$id])) continue;
      $perm = self::$permAll[$id];
      // 动作权限
      $action = [];
      $actionArr = [];
      $actionStr = (string)$val['action'];
      if($actionStr!='') $actionArr = json_decode($actionStr, true);
      foreach($actionArr as $v){
        $permVal = (int)$v['perm'];
        if(($perm&$permVal)>0) $action[] = $v;
      }
      // 数据
      $value = ['url'=> $val['url']?$path.$val['url']:$val['url'], 'controller'=> $val['controller'], 'action'=> $action];
      $tmp = ['icon'=> $val['ico'], 'label'=> $val['title'], 'en'=> $val['en'], 'value'=> $value, 'display'=> true, 'show'=> true];
      $menu = self::_getMenusPerm($id, $path);
      if(!empty($menu)) $tmp['children'] = $menu;
      $data[] = $tmp; 
    }
    return $data;
  }
}
