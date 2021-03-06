<?php

namespace Kinfy\Http;

use Kinfy\Config\Config;

//REQUEST_METHOD
//REDIRECT_URL

/**
 * Class Router
 * @package Kinfy\Http
 * @author Mr.Lin
 * @version 0.1
 */
class Router
{
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
    //存放group方法url前缀池
    public static $url_pre = [];
    // 存放路由过滤的中间件
    public static $middlewares = [];
    //存放当前注册的所有的路由规则
    public static $routes = [];
    //存放当前注册的所有正则表达式路由规则
    public static $re_routes = [];
    //存放正则参数全局默认规则
    public static $default_rule = [];
    //路由匹配中的回调方法，默认为空
    public static $onMatch = null;
    //当路由未匹配的时候执行回调函数，默认为空
    public static $onMissMatch = null;

    /**
     * 初始化静态变量
     *
     * @return void
     */
    public static function MyStaticInit()
    {
        self::$parameter_necessary = self::$parameter_necessary_start . self::$parameter_necessary_end;
        self::$parameter_choose = self::$parameter_necessary_start . '?' . self::$parameter_necessary_end;
    }

    /**
     * 处理未定义的请求方法
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (count($arguments) >= 2) {
//            $arguments[0] = self::router_replace($arguments[0]);
            self::addRoute($name, ...$arguments);
        }
    }

    /**
     * 添加指定参数的路由注册
     *
     * @param string $request_type
     * @param string $pattern
     * @param callable $callback
     * @param
     * @return void
     */
    private static function addRoute($request_type, $pattern, $callback, $re_rule = null)
    {
        $request_type = strtoupper($request_type);
        $pattern = self::path(implode('/', self::$url_pre) . self::path($pattern));
        $route = [
            'callback' => $callback,
            'middlewares' => self::$middlewares
        ];
//        self::$routes[strtoupper($request_type)][$pattern] = $callback;
        //判断是否为带参路由
        $is_regx = strpos($pattern, '{') !== false;
        if (!$is_regx) {
            self::$routes[$request_type][$pattern] = $route;
        } else {
            self::routerReplace($request_type, $pattern, $callback, $re_rule);
        }
    }

    /**
     * 提供中间件和路由前缀注册
     *
     * @param array|string $pre 中间件名和路由前缀
     * @param callable $callback
     */
    public static function group($pre, $callback)
    {
        $p = '';
        $m = '';
        if (is_array($pre)) {
            if (isset($pre['prefix']) && $pre['prefix']) {
                $p = $pre['prefix'];
            }
            if (isset($pre['middleware']) && $pre['middleware']) {
                $m = $pre['middleware'];
            }
        } elseif (is_string($pre)) {
            $p = $pre;
        }

        $p && array_push(self::$url_pre, $p);
        $m && array_push(self::$middlewares, $m);

        if (is_callable($callback)) {
            call_user_func($callback);
        }

        $p && array_pop(self::$url_pre);
        $m && array_pop(self::$middlewares);
    }

    /**
     * 多个请求类型统一添加到路由中
     *
     * @param array $request_type_arr
     * @param string $pattern
     * @param callable $callback
     */
    public static function MATCH($request_type_arr, $pattern, $callback)
    {
        if (!is_array($request_type_arr)) {
            echo 'match request type should be an array' . '<br>Content:';
            echo '<br>' . $request_type_arr . '<br>' . $pattern . '<br>' . $callback . '<br>';
            die();
        }
//        $pattern = self::router_replace($pattern);
        foreach ($request_type_arr as $request_type) {
            self::addRoute($request_type, $pattern, $callback);
        }
    }

    /**
     * 带参路由进入函数，分析路由规则，注册到正则路由或是非正则路由
     *
     * @param string $request_type
     * @param string $pattern
     * @param callable $callback
     * @param array $re_rule
     */
    private static function routerReplace($request_type, $pattern, $callback, $re_rule)
    {
        //判断可选参数是否在必选参数前面
        $parameter_flag = false;
        //原始路径数据
        $pattern_raw = $pattern;
        //可选路径数据
        $new_pattern_raw = $pattern;
        //原始路径的正则数据
        $normal_pattern = $pattern;
        //可选路径的正则数据
        $new_normal_pattern = $pattern;
        $is_matched = preg_match_all('#{(.*?)}#', $pattern, $pnames);
        if ($is_matched) {
            foreach ($pnames[1] as $p) {
                $pname = str_replace('?', '', $p);
                //寻找正确正则表达式
                $rule = '[^/]+';
                if (isset($re_rule[$pname]) && is_array($re_rule)) {
                    $rule = $re_rule[$pname];
                } elseif (isset(self::$default_rule[$pname])) {
                    $rule = self::$default_rule[$pname];
                }
                //判断是否为可选参数
                if ($pname !== $p) {
                    //把可选路径的参数删除
                    $new_pattern_raw = str_replace(
                        '/{' . $p . '}',
                        '',
                        $pattern_raw
                    );
                    //把原始路径的正则数据剔除带参数
                    $new_normal_pattern = str_replace(
                        '/{' . $p . '}',
                        '',
                        $normal_pattern
                    );
                    $normal_pattern = str_replace(
                        '{' . $p . '}',
                        '(' . $rule . ')',
                        $normal_pattern
                    );
                    $parameter_flag = true;
                } elseif ($parameter_flag) {
                    echo '错误Router：' . $pattern_raw . '<br>' . '可选参数在必选参数前面是非法定义<br>';
                    die();
                } else {
                    $normal_pattern = str_replace(
                        '{' . $pname . '}',
                        '(' . $rule . ')',
                        $normal_pattern
                    );
                }
            }
            self::addReRoute($request_type, $new_pattern_raw, $new_normal_pattern, $callback);
            self::addReRoute($request_type, $pattern_raw, $normal_pattern, $callback);
        }
    }

    /**
     * 访问的url提取参数存入$parameter_array
     *
     * @param string $request_type
     * @param string $router
     * @return string
     */
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

    /**
     * 设置全局正则规则
     *
     * @param $param_name
     * @param $pattern
     */
    public static function rule($param_name, $pattern)
    {
        self::$default_rule[$param_name] = $pattern;
    }

    /**
     * 添加正则路由
     *
     * @param string $request_type
     * @param string $pattern_raw
     * @param string $pattern_re
     * @param callable $callback
     */
    public static function addReRoute($request_type, $pattern_raw, $pattern_re, $callback)
    {
        $route = [
            'pattern_raw' => $pattern_raw,
            'pattern_re' => '#^' . $pattern_re . '$#',
            'callback' => $callback,
            'middlewares' => self::$middlewares
        ];
        if (!strpos($pattern_raw, '{') !== false) {
            self::$routes[$request_type][$pattern_raw] = $callback;
        } else {
            self::$re_routes[$request_type][$pattern_raw] = $route;
        }
    }

    /**
     * 移除头部和尾部额外的'/'并在头部添加'/'
     *
     * @param string $path
     * @return string
     */
    public static function path($path)
    {
        return '/' . trim($path, '/');
    }

    /**
     * 执行$routes数组里的转发规则
     *
     */
    public static function dispatch()
    {
        //缩短 self::$routes;  非正则路由集合
        $routes = self::$routes;
        //缩短 self::$re_routes   正则路由集合
        $re_routes = self::$re_routes;
        //缩短 self::$parameter_array
        //$params = self::$parameter_array;
//        print_r($routes);
//        print_r($re_routes);
//        die();
        //获取请求头
        $request_type = strtoupper($_SERVER['REQUEST_METHOD']);
        // 先将url分割成正常的路由
        $url = explode('?', $_SERVER['REQUEST_URI'])[0];
        // 去除多余的'/'
        $url = $url != '/' ? rtrim($url, '/') : '/';
        //改变url样子
        //$url = self::router_parameter_list($request_type, $url);
        // 路由是否通过
        $is_matched = false;
        // 路由指定的回调函数或控制器方法
        $callback = null;
        // 参数列表
        $params = [];
        // 路由对应的中间件列表
        $middlewares = [];

        if (isset($routes['ANY'][$url])) {
            $callback = $routes['ANY'][$url]['callback'];
            $middlewares = $routes['ANY'][$url]['middlewares'];
            $is_matched = true;
        } elseif (isset($routes[$request_type][$url])) {
            $callback = $routes[$request_type][$url]['callback'];
            $middlewares = $routes[$request_type][$url]['middlewares'];
            $is_matched = true;
        } else {
            if (isset($re_routes['ANY'])) {
                foreach ($re_routes['ANY'] as $route) {
                    $is_matched = preg_match($route['pattern_re'], $url, $params);
                    if (!$is_matched) {
                        $is_matched = preg_match($route['pattern_re'], $url . '/', $params);
                    }
                    if ($is_matched) {
                        array_shift($params);
                        $middlewares = $route['middlewares'];
                        $callback = $route['callback'];
                        break;
                    }
                }
            }
            if (!$is_matched && isset($re_routes[$request_type])) {
                foreach ($re_routes[$request_type] as $pattern => $route) {
                    $is_matched = preg_match($route['pattern_re'], $url, $params);
                    if (!$is_matched) {
                        $is_matched = preg_match($route['pattern_re'], $url . '/', $params);
                    }
                    if ($is_matched) {
                        array_shift($params);
                        $middlewares = $route['middlewares'];
                        $callback = $route['callback'];
                        break;
                    }
                }
            }
        }
        //检查是否有定义的请求方式下的请求地址
        if ($is_matched) {
            // 先做中间件数组扁平化
            foreach ($middlewares as $ms) {
                // 遍历当前路由的所有中间件配置信息
                foreach ($ms as $m) {
                    // 如果是闭包
                    if (is_callable($m)) {
                        call_user_func($m);
                    } else {
                        // 获取当前中间件的配置信息
                        $mclass = Config::get('middleware.' . $m);
                        if (is_array($mclass)) {
                            foreach ($mclass as $mc) {
                                $mobj = new $mc;
                                $mobj->handle();
                            }
                        } else {
                            $mobj = new $mclass;
                            $mobj->handle();
                        }
                    }
                }
            }

            //是否有自定义url匹配正确的回调函数
            if (is_callable(self::$onMatch)) {
                call_user_func(self::$onMatch, $callback, $params);
            } else {
                //检查是可执行函数还是控制器
                if (is_callable($callback)) {
                    call_user_func($callback, ...$params);
                } else {
                    list($class, $method) = explode(self::$delimiter, $callback);
                    $class = self::$namespace . $class;
                    $obj = new $class();
                    $obj->{$method}(...$params);
                }
            }
        } else {
            //检查是否有自定义的错误页面,有执行,没有返回假404
            if (is_callable(self::$onMissMatch)) {
                call_user_func(self::$onMissMatch);
            } else {
                header('HTTP/1.1 404 Not Found');
                exit();
            }
        }
    }
}

//静态变量初始化
Router::MyStaticInit();