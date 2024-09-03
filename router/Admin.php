<?php
namespace Router;

use Illuminate\Container\Container;
use Middleware\Cors;

class Admin{
  static function Init(){
    // 允许跨域请求
    Cors::Init();
    // 路由
    $app =Container::getInstance();
    $app['router']->group(['namespace' => 'App\Admin', 'prefix' => 'admin'], function($router){
      // 登陆
      $router->get('vcode/{uname}', "Login@Vcode");
      $router->post('login', "Login@Login");
      $router->post('verify/token', "Login@Token");
      $router->post('change_passwd', "Login@ChangePasswd");
      $router->post('change_uinfo', "Login@ChangeUinfo");
      // 用户管理
      $router->post('user/list', "User@List");
      $router->post('user/add', "User@Add");
      $router->post('user/edit', "User@Edit");
      $router->post('user/delete', "User@Del");
      $router->post('user/state', "User@State");
      $router->post('user/perm', "User@Perm");
      $router->post('user/info', "User@Info");
      $router->post('user/role_list', "User@RoleList");
      // 菜单管理
      $router->post('sys_menus/list', "SysMenus@List");
      $router->post('sys_menus/add', "SysMenus@Add");
      $router->post('sys_menus/del', "SysMenus@Del");
      $router->post('sys_menus/edit', "SysMenus@Edit");
      $router->post('sys_menus/perm', "SysMenus@Perm");
      $router->post('sys_menus/getMenusAll', "SysMenus@GetMenusAll");
      $router->post('sys_menus/getMenusPerm', "SysMenus@GetMenusPerm");
      // 系统角色
      $router->post('sys_role/list', "SysRole@List");
      $router->post('sys_role/add', "SysRole@Add");
      $router->post('sys_role/del', "SysRole@Del");
      $router->post('sys_role/edit', "SysRole@Edit");
      $router->post('sys_role/perm', "SysRole@Perm");
      $router->post('sys_role/permList', "SysRole@PermList");
      // 采购入库
      $router->post('erp_purchases_in/add', "ErpPurchasesIn@Add");
      $router->post('erp_purchases_in/edit', "ErpPurchasesIn@Edit");
      $router->post('erp_purchases_in/del', "ErpPurchasesIn@Del");
    });
  }
}
