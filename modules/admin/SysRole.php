<?php
namespace App\Admin;

use Model\SysMenus;
use Model\SysRole as ModelSysRole;
use Service\Base;

class SysRole extends Base{

  private static $menus = [];           // 全部菜单
  private static $permAll = [];        // 用户权限
  /* 角色列表 */
  static function List(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    $page = self::JsonName($json, 'page');
    $limit = self::JsonName($json, 'limit');
    $order = self::JsonName($json, 'order');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data) || empty($page) || empty($limit)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 条件
    $where = self::getWhere($data);
    // 统计
    $m = new ModelSysRole();
    $m->Columns('count(*) AS total');
    $m->Where($where);
    $total = $m->FindFirst();
    // 查询
    $m->Columns('id', 'name', 'perm', 'FROM_UNIXTIME(ctime) as ctime', 'FROM_UNIXTIME(utime) as utime');
    $m->Where($where);
    $m->Order($order?:'id DESC');
    $m->Page($page, $limit);
    $list = $m->Find();
    return self::GetJSON(['code'=>0, 'msg'=>'成功', 'time'=> date('Y/m/d H:i:s'), 'total'=> (int)$total['total'], 'list'=> $list]);
  }

  /* 新增角色 */
  static function Add(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $name = isset($data['name'])?trim($data['name']):'';
    if($name=='') return self::GetJSON(['code'=>4000, 'msg'=>'名称不能为空!']);
    // 模型
    $m = new ModelSysRole();
    $m->Values(['name'=> $name, 'ctime'=> time()]);
    if($m->Insert()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'添加失败!']);
    } 
  }

  /* 删除角色 */
  static function Del(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $data = self::JsonName($json, 'data');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $ids = implode(',', $data);
    // 模型
    $m = new ModelSysRole();
    $m->Where('id in (' .$ids. ')');
    if($m->Delete()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'删除失败!']);
    }
  }

  /* 编辑角色 */
  static function Edit(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $id = self::JsonName($json, 'id');
    $data = self::JsonName($json, 'data');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($id) || empty($data)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    $name = isset($data['name'])?trim($data['name']):'';
    if($name=='') return self::GetJSON(['code'=>4000, 'msg'=>'名称不能为空!']);
    // 模型
    $m = new ModelSysRole();
    $m->Set(['name'=> $name, 'utime'=> time()]);
    $m->Where('id=?', $id);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 编辑权限 */
  static function Perm(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $id = self::JsonName($json, 'id');
    $perm = self::JsonName($json, 'perm');
    // 验证权限
    // $msg = AdminToken::Verify($token, $_SERVER['REQUEST_URI']);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    if(empty($id)) return self::GetJSON(['code'=>4000, 'msg'=>'参数错误!']);
    // 模型
    $m = new ModelSysRole();
    $m->Set(['perm'=> $perm, 'utime'=> time()]);
    $m->Where('id=?', $id);
    if($m->Update()) {
      return self::GetJSON(['code'=>0,'msg'=>'成功']);
    } else {
      return self::GetJSON(['code'=>5000,'msg'=>'更新失败!']);
    }
  }

  /* 权限列表 */
  static function PermList(){
    // 参数
    $json = self::Json();
    $token = self::JsonName($json, 'token');
    $perm = self::JsonName($json, 'perm');
    // 验证权限
    // $msg = AdminToken::Verify($token);
    // if($msg!='') return self::GetJSON(['code'=>4001, 'msg'=>$msg]);
    // 全部菜单
    $m = new SysMenus();
    $m->Columns('id', 'fid', 'title', 'url', 'ico', 'controller', 'action');
    $m->Order('sort, id');
    $data = $m->Find();
    foreach($data as $val){
      $fid = (string)$val['fid'];
      self::$menus[$fid][] = $val;
    }
    // 用户权限
    self::$permAll = self::permArr($perm);
    return self::GetJSON(['code'=>0,'msg'=>'成功', 'list'=>self::_getMenu('0')]);
  }

  // 权限拆分
  private static function permArr(string $perm): array{
    $permAll = [];
    $arr = !empty($perm)?explode(' ', $perm):[];
    foreach($arr as $val){
      $s = explode(':', $val);
      $permAll[$s[0]] = (int)$s[1];
    }
    return $permAll;
  }

  // 递归菜单
  private static function _getMenu(string $fid){
    $data = [];
    $M = isset(self::$menus[$fid])?self::$menus[$fid]:[];
    foreach($M as $val){
      // 菜单权限
      $id = (string)$val['id'];
      $perm = isset(self::$permAll[$id])?self::$permAll[$id]:0;
      // 动作权限
      $action = [];
      $actionArr = [];
      $actionStr = (string)$val['action'];
      if($actionStr!='') $actionArr = json_decode($actionStr, true);
      foreach($actionArr as $v){
        $permVal = (int)$v['perm'];
        $checked = ($perm&$permVal)>0?true:false;
        $action[] = [
          'id'=> $val['id'] .'_'. $v['perm'],
          'label'=> $v['name'],
          'checked'=> $checked,
          'perm'=> $v['perm']
        ];
      }
      // 数据
      $checked = isset(self::$permAll[$id])?true:false;
      $tmp = ['id'=> $val['id'], 'label'=> $val['title'], 'checked'=> $checked];
      if($val['fid']==0) $tmp['show'] = true;
      // children
      $menu = self::_getMenu($id);
      if(!empty($menu)) $tmp['children'] = $menu;
      elseif(!empty($action)){
        $tmp['action'] = true;
        $tmp['children'] = $action;
      }
      $data[] = $tmp;
    }
    return $data;
  }

  // 搜索条件 
  private static function getWhere(array $data): string{
    $where = [];
    // 关键词
    $key = isset($data['key'])?trim($data['key']):'';
    if($key){
      $arr = [
        'name like "%' .$key. '%"'
      ];
      $where[] = implode(' OR ', $arr);
    }
    // 角色
    $name = isset($data['name'])?trim($data['name']):'';
    if($name) $where[] = 'name like "%' .$name. '%"';
    return implode(' AND ', $where);
  }
}
