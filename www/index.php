<?php
use Framework\Core\Router;

require_once '../Framework/autoload.php';

session_start();
date_default_timezone_set("Asia/Singapore");

$whoops = new \Whoops\Run();
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

$router = new Router();
$router->add('', ['module' => 'main', 'action' => 'index']);
$router->add('/board/{boardId}', ['module' => 'board', 'action' => 'index']);
$router->add('/board/{boardId}/{seq:[0-9]+}', ['module' => 'board', 'action' => 'detail']);
$router->add('/board/{boardId}/{seq:[0-9]+}/{action}', ['module' => 'board']);
$router->add('/board/{boardId}/{action}', ['module' => 'board']);
$router->add('/{module}', ['action' => 'index']);
$router->add('/{module}/{action}');
$router->add('/{module}/{action}/{id}');

$router->dispatch($_SERVER['REQUEST_URI']);
