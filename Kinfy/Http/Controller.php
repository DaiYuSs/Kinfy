<?php
/**
 * Created by PhpStorm.
 * User: l
 * Date: 2019/4/13
 * Time: 9:17
 */

namespace Kinfy\Http;

class Controller
{
    public static $conf = [
        'delimiter' => '@',
        'namespace' => '\\App\\Controller\\',
        'global_prefix' => 'before',
        'global_suffix' => 'after',
        'prefix' => 'before',
        'suffix' => 'after',
        'band' => [

        ]
    ];

    public static function run($callback, $params)
    {
        if (is_callable($callback)) {
            call_user_func($callback, ...$params);
        } else {
            list($class, $method) = explode(self::$conf['delimiter'], $callback);
            $class = self::$conf['namespace'] . $class;
            $obj = new $class();

            //如果是禁止执行的方法,则不执行
            if (in_array($method, self::$conf['band'])) {
                die("{$method} 方法被禁用了");
            } else {
                //优先执行全局前置方法
                if (isset(self::$conf['global_prefix'])) {
                    self::execMethod($obj, self::$conf['global_prefix']);
                }

                //执行特定前置
                if (isset(self::$conf['prefix'])) {
                    self::execMethod($obj, self::$conf['prefix'] . $method, $params);
                }

                $obj->{$method}(...$params);

                //执行特定后置
                if (isset(self::$conf['suffix'])) {
                    self::execMethod($obj, self::$conf['suffix'] . $method, $params);
                }

                //最后执行全局后置方法
                if (isset(self::$conf['global_suffix'])) {
                    self::execMethod($obj, self::$conf['global_suffix']);
                }
            }
        }
    }

    public static function execMethod($obj, $method, $params = [])
    {
        if (method_exists($obj, $method)) {
            $obj->{$method}(...$params);
        }
    }
}