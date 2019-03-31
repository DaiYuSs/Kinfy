<?php

namespace Kinfy\Http;

//REQUEST_METHOD
//REDIRECT_URL
use function PHPSTORM_META\type;

class Router
{
    //当路由未匹配的时候执行回调函数，默认为空
    public static $notFound = null;
    //控制器与方法的分割字符
    public static $delimiter = '@';
    //调用控制器时的命名空间
    public static $namespace = '';
    //路由的必要参数包含的左部
    public static $parameter_necessary_start = '{';
    //路由的必要参数包含的右部
    public static $parameter_necessary_end = '}';
    //路由的必要参数由什么包含
    private static $parameter_necessary = null;
    //路由的可选参数由什么包含
    private static $parameter_choose = null;
    //路由携带的参数
    private static $parameter_array = [];
    //存放当前注册的所有的路由规则
    public static $routes = [];

    //给静态变量赋值
    public static function MyStaticInit()
    {
        self::$parameter_necessary = self::$parameter_necessary_start . self::$parameter_necessary_end;
        self::$parameter_choose = self::$parameter_necessary_start . '?' . self::$parameter_necessary_end;
    }

    //调用当前类不存在的静态方法时使用此函数(方法名,数组参数)
    public static function __callStatic($name, $arguments)
    {
        $name = strtoupper($name);
        if (count($arguments) >= 3 && $name == 'MATCH') {
            $arguments[1] = self::router_replace($arguments[1]);
            if (is_array($arguments[0])) {
                foreach ($arguments[0] as $request_type) {
                    $request_type = strtoupper($request_type);
                    self::$routes[$request_type][self::path($arguments[1])] = $arguments[2];
                }
            } else {
                echo '自定义请求应是数组</br>' . '错误请求头:' . $name . '<br>请求内容';
                print_r($arguments) . '<br>';
                die();
            }
        } elseif (count($arguments) == 2) {
            $arguments[0] = self::router_replace($arguments[0]);
            self::$routes[$name][self::path(($arguments[0]))] = $arguments[1];
        }
    }
    //把router替换成必填参数和可选参数的样子
    private static function router_replace($router)
    {
        //判断可选参数是否在必选参数前面
        $parameter_flag = false;
        //新路由
        $str = '';
        self::$parameter_array = [];
        $router_array = explode('/', trim($router, '/'));
        foreach ($router_array as $value) {
            //判断是否满足必选参数的条件
            if (strpos($value, self::$parameter_necessary_start) === 0 && strrpos($value,
                    self::$parameter_necessary_end) === strlen($value) - strlen(self::$parameter_necessary_end)) {
                //判断参数后面是否携带'?'
                if (strrpos($value, '?') === strlen($value) - strlen(self::$parameter_necessary_end) - 1) {
//                    array_push(self::$parameter_array, substr($value, strlen(self::$parameter_necessary_start),
//                        (strlen(self::$parameter_necessary_end) + 1) * -1));
                    $value = self::$parameter_choose;
                    $parameter_flag = true;
                } else {
                    //判断可选参数是否在必选参数前面定义
                    if (!$parameter_flag) {
//                        array_push(self::$parameter_array, substr($value, strlen(self::$parameter_necessary_start),
//                            strlen(self::$parameter_necessary_end) * -1));
                        $value = self::$parameter_necessary;
                    } else {
                        echo '错误Router：' . $router . '<br>' . '可选参数在必选参数前面是非法定义<br>';
                        die();
                    }
                }
            }
            $str = $str . '/' . $value;
        }
        return $str;
    }

    //访问的url提取参数存入$parameter_array
    private static function router_parameter_list($request_type, $router)
    {
        //url拆解数组
        $router = explode('/', trim($router, '/'));
        //key为注册的url，value为callback
        foreach (self::$routes[$request_type] as $key => $value) {
            //key拆解数组
            $key = explode('/', trim($key, '/'));
            //判断url个数是否与注册的route个数相对应,注意可选参数需要||单独判断
            if ((count($key) - substr_count(implode('/', $key),
                        self::$parameter_choose) === count($router)) || count($key) === count($router)) {
                //清空参数数组
                self::$parameter_array = [];
                foreach ($key as $keys => $value) {
                    //判断注册的route中 value 是否为参数,url是否有为可选参数传值(isset())
                    if (($value === self::$parameter_necessary || $value === self::$parameter_choose) && isset($router[$keys])) {
                        array_push(self::$parameter_array, $router[$keys]);
                    } elseif (isset($router[$keys]) && $value !== $router[$keys]) {
                        break;
                    }
                    //url替换成route
                    $router[$keys] = $value;
                }
            }
        }
        return '/' . implode('/', $router);
    }

    //去掉路由尾巴的'/'，增加路由开头的'/'
    public static function path($path)
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
        $pattern = self::router_parameter_list($request_type, $pattern);
        //检查是否有定义的请求方式下的请求地址
        if (isset(self::$routes[$request_type][$pattern])) {
            $callback = self::$routes[$request_type][$pattern];
            //检查是可执行函数还是控制器
            if (is_callable($callback)) {
                call_user_func($callback(...self::$parameter_array));
            } else {
                list($class, $method) = explode(self::$delimiter, $callback);
                $calss = self::$namespace . $class;
                $obj = new $calss();
                $obj->{$method}(...self::$parameter_array);
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

//静态变量初始化
Router::MyStaticInit();