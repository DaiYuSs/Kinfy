<?php

use Kinfy\Http\Router;
use Kinfy\Http\Controller;
use Kinfy\Config\Config;

require_once __DIR__ . './../vendor/autoload.php';
// 优先设置配置文件的根目录
Config::setBaseDir(__DIR__ . './../app/config/');

if (Config::get('app.status') == 'SHUTDOWN') {
    die('网站维护中！');
}

// 动态创建不存在的类Facade
spl_autoload_register(function ($name) {
    // 定义根命名空间的不存在的类
    $provider = Config::get('app.providers.' . $name);
    if (
        strpos($name, '\\') === false
        &&
        $provider
    ) {
        // 如果定义了接口,则做接口判断
        $provider_interface = Config::get('app.provider_interface.' . $name);
        if ($provider_interface && !isset(class_implements($provider)[$provider_interface])) {
            die("{$provider} 必须实现{$provider_interface} 接口");
        }
        // 动态定义类
        eval("
        class {$name} extends \Kinfy\Facade\Facade{
            protected static \$provider = '{$provider}';
        }
        ");
    }
});

// 优先加载用户自定义的函数库函数
if (file_exists(__DIR__ . './../app/common/common_functions.php')) {
    require_once __DIR__ . './../app/common/common_functions.php';
}
// 加载系统自定义的函数库函数
require_once __DIR__ . './../Kinfy/common/common_functions.php';
// 加载路由文件
require_once __DIR__ . './../app/router/web.php';

//路由未匹配中执行的回调函数
Router::$onMissMatch = function () {
    require_once __DIR__ . './../app/resource/404.html';
};

//路由匹配中执行的回调函数
Router::$onMatch = function ($callback, $params) {
    Controller::run($callback, $params);
};

Router::dispatch();
