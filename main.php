<?php
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Middleware\Cors;
use Router\Admin;

/* 常量 */
define('BASE_PATH', __DIR__);
define('STDERR', fopen('php://stderr', 'a'));

/* composer */
$load = BASE_PATH.'/vendor/autoload.php';
if(!is_file($load)) die('安装依赖包: composer install');
require $load;

/* 应用 */
$app = new Container();
$app->setInstance($app);

try{
  /* 注册 */
  (new EventServiceProvider($app))->register();
  (new RoutingServiceProvider($app))->register();
  /* 路由 */
  Admin::Init();
  /* 请求 */
  $request = Request::createFromGlobals();
  $response = $app['router']->dispatch($request);
  $response->send();
}catch (\Exception $e){
  Cors::Init();
  echo json_encode(['code'=>5000,'msg'=>'服务错误！']);
}
