<?php

namespace Kinfy;

use Kinfy\Http\Router;
use Kinfy\Http\Controller;
use Kinfy\Config\Config;

class app
{
    /**
     * 配置文件路径
     *
     * @var string
     */
    private $configDir = __DIR__ . './../app/config/';

    /**
     * 路由文件路径
     *
     * @var string
     */
    private $routerDir = './../app/router/web.php';

    /**
     * 自定义通用函数库文件路径
     *
     * @var string
     */
    private $appFunc = __DIR__ . './../app/common/common_functions.php';

    /**
     * 路由匹配中执行的回调函数
     *
     * @var callable
     */
    private $onMatch = null;

    /**
     * 路由未匹配中执行的回调函数
     *
     * @var callable
     */
    private $onMissMatch = null;

    /**
     * 设置配置文件的路径
     *
     * @param string $configDir
     */
    public function setConfigDir(string $configDir): void
    {
        $this->configDir = $configDir;
    }

    /**
     * 设置路由文件的路径
     *
     * @param string $routerDir
     */
    public function setRouterDir(string $routerDir): void
    {
        $this->routerDir = $routerDir;
    }

    /**
     * 设置用户自定义通用函数库文件路径
     *
     * @param string $appFunc
     */
    public function setAppFunc(string $appFunc): void
    {
        $this->appFunc = $appFunc;
    }

    /**
     * 设置路由匹配中执行的回调函数
     *
     * @param callable $callable
     */
    public function onRouterMatch(callable $callable): void
    {
        $this->onMatch = $callable;
    }

    /**
     * 设置路由未匹配中执行的回调函数
     *
     * @param callable $callable
     */
    public function onRouterMissMatch(callable $callable): void
    {
        $this->onMissMatch = $callable;
    }

    /**
     * 框架启动
     */
    public function start(): void
    {
        // 优先设置配置文件的根目录
        Config::setBaseDir($this->configDir);

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
        if (file_exists($this->appFunc)) {
            require_once $this->appFunc;
        }

        // 加载系统自定义的函数库函数
        require_once __DIR__ . './../Kinfy/common/common_functions.php';
        // 加载路由文件
        require_once $this->routerDir;

        // 路由匹配中执行的回调函数
        Router::$onMatch = $this->onMatch ?? function ($callback, $params) {
                Controller::run($callback, $params);
            };

        // 路由未匹配中执行的回调函数
        Router::$onMissMatch = $this->onMissMatch ?? function () {
                require_once __DIR__ . './../app/resource/404.html';
            };
        // 路由执行函数
        Router::dispatch();
    }
}
