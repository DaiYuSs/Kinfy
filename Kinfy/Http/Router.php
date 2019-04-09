<?php

namespace Kinfy\Http;

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
    //存放group方法url前缀池
    public static $url_pre = [];
    //存放当前注册的所有的路由规则
    public static $routes = [];
    //存放当前注册的所有正则表达式路由规则
    public static $re_routes = [];

    /**
     * Initialize static variables
     *
     * @return void
     */
    public static function MyStaticInit()
    {
        self::$parameter_necessary = self::$parameter_necessary_start . self::$parameter_necessary_end;
        self::$parameter_choose = self::$parameter_necessary_start . '?' . self::$parameter_necessary_end;
    }

    /**
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
     * Add designation route
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
//        self::$routes[strtoupper($request_type)][$pattern] = $callback;
        //判断是否为带参路由
        $is_regx = strpos($pattern, '{') !== false;
        if (!$is_regx) {
            self::$routes[$request_type][$pattern] = $callback;
        } else {
            $route = [
                'pattern_raw' => $pattern
            ];
            //先找出占位符的名称
            $is_matched = preg_match_all('#{(.*?)}#', $pattern, $pnames);
//            print_r(self::$routes);
            if ($is_matched) {
                //占位符默认替换的规则为全部
                $rule = '.*?';
                foreach ($pnames[1] as $p) {
                    if (isset($re_rule[$p])) {
                        $rule = $re_rule[$p];
                    }
                    $pattern = str_replace(
                        '{' . $p . '}',
                        '(' . $rule . ')',
                        $pattern
                    );
                };
//                $route = [
//                    'pattern_raw' => $pattern,
//                    'pattern_re' => $pattern,
//                    'callback' => $callback
//                ];
//                self::$routes[$request_type][$route['pattern_raw']] = $route;
            }
        }
    }

    /**
     * Route prefix loop nesting
     *
     * @param $pre
     * @param $callback
     */
    public static function group($pre, $callback)
    {
        array_push(self::$url_pre, $pre);
        if (is_callable($callback)) {
            call_user_func($callback);
        }
        array_pop(self::$url_pre);
    }

    /**
     * Multiple request types are uniformly added to the route
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

    /**
     * @param $url_pattern
     * @param $url
     */
    public static function getParams($url_pattern, $url)
    {
    }

    /**
     * Remove the extra '/' from the head and tail and add a '/' to the head
     *
     * @param string $path
     * @return string
     */
    public static function path($path)
    {
        return '/' . trim($path, '/');
    }

    //执行$routes数组里的转发规则
    public static function dispatch()
    {
        //缩短self::$routes;
        $routes = self::$routes;
        //缩短self::$re_routes
        $re_router = self::$re_routes;
        print_r($routes);
        print_r($re_router);
        die();
        //获取请求头
        $request_type = strtoupper($_SERVER['REQUEST_METHOD']);
        //获取请求地址
        $url = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '/';
        //改变url样子
        $url = self::router_parameter_list($request_type, $url);

        $is_matched = false;
        if (isset($routes['ANY'][$url])) {
            $request_type = 'ANY';
            $is_matched = true;
        } elseif (isset($routes[$request_type][$url])) {
            $is_matched = true;
        } elseif (isset($routes['ANY'])) {
            foreach ($routes['ANY'] as $pattern => $callback) {
                $params = self::getParams($pattern, $url);
            }
        }
        //检查是否有定义的请求方式下的请求地址
        if ($is_matched) {
            $callback = $routes[$request_type][$url];
            //检查是可执行函数还是控制器
            if (is_callable($callback)) {
                call_user_func($callback, ...self::$parameter_array);
            } else {
                list($class, $method) = explode(self::$delimiter, $callback);
                $class = self::$namespace . $class;
                $obj = new $class();
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