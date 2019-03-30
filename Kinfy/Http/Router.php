<?php

namespace Kinfy\Http;

//REQUEST_METHOD
//REDIRECT_URL
class Router
{
    //当路由未匹配的时候执行回调函数，默认为空
    public static $notFound = null;
    //控制器与方法的分割字符
    public static $delimiter = '@';
    //调用控制器时的命名空间
    public static $namespace = '';
    //路由的必要参数由什么包含
    public static $parameter_necessary = '{}';
    //路由的可选参数由什么包含
    public static $parameter_choose = '{?}';
    //存放当前注册的所有的路由规则
    public static $routes = [];

    //调用当前类不存在的静态方法时使用此函数(方法名,数组参数)
    public static function __callStatic($name, $arguments)
    {
        $name = strtoupper($name);
        if (count($arguments) >= 2) {
            if (count($arguments) >= 3 && $name == 'MATCH' && is_array($arguments[0])) {
                foreach ($arguments[0] as $request_type) {
                    $request_type = strtoupper($request_type);
                    self::$routes[$request_type][self::path($arguments[1])] = $arguments[2];
                }
            } else {
                self::$routes[$name][self::path(($arguments[0]))] = $arguments[1];
            }
        }
    }

    private static function path($path)
    {
        return '/' . trim($path, '/');
    }

    //执行$routes数组里的转发规则
    public static function dispatch()
    {
        //获取请求头
        $request_type = strtoupper($_SERVER['REQUEST_METHOD']);
        //获取请求地址
        $pattern = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '/';

//        $is_matched = false;
//        if (isset(self::$routes['ANY'][$pattern])) {
//            $request_type = 'ANY';
//            $is_matched = true;
//        } else {
//            if (isset(self::$routes[$request_type][$pattern])) {
//                $is_matched = true;
//            }
//        }

        //检查是否有定义的请求方式下的请求地址
        if (isset(self::$routes[$request_type][$pattern])) {
            $callback = self::$routes[$request_type][$pattern];
            //检查是可执行函数还是控制器
            if (is_callable($callback)) {
                call_user_func($callback);
            } else {
                list($class, $method) = explode(self::$delimiter, $callback);
                $calss = self::$namespace . $class;
                $obj = new $calss();
                $obj->{$method}();
            }
        } else {
            //检查是否有自定义的错误页面,有执行,没有返回假404
            if (is_callable(self::$notFound)) {
                call_user_func(self::$notFound);
            } else {
                header('HTTP/1.1 404 Not Found');
                exit();
            }
        }
    }
}