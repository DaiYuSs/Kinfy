<?php

require_once __DIR__ . './../vendor/autoload.php';
require_once __DIR__ . './../app/router/web.php';

use Kinfy\Http\Router;
use Kinfy\Http\Controller;

//路由未匹配中执行的回调函数
Router::$onMissMatch = function () {
    require_once __DIR__ . './../app/resource/404.html';
};

//路由匹配中执行的回调函数
Router::$onMatch = function ($callback, $params) {
    Controller::run($callback, $params);
};

Router::dispatch();