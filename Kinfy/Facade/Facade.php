<?php

namespace Kinfy\Facade;

class Facade
{
    /**
     * 静态类所处的命名空间
     *
     * @var string
     */
    protected static $provider = '';

    /**
     * 静态类数组
     *
     * @var array
     */
    protected static $instance = [];

    /**
     * 因为当执行静态代码时，自身有一个实例static::$instance
     * 返回静态的共用的实例
     *
     * @return mixed
     */
    public static function getInstance()
    {
        $p = static::$provider;
        if (!isset(static::$instance[$p])) {
            static::$instance[$p] = new $p;
        }
        return static::$instance[$p];
    }

    /**
     * 当调用不存在的静态方法的时候，自动静态转动态
     *
     * @param string $name 方法名
     * @param array $arguments 参数列表
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::getInstance()->{$name}(...$arguments);
    }
}
