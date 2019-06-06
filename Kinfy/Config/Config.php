<?php

namespace Kinfy\Config;

class Config
{
    // 存放整个网站的配置信息
    protected static $conf = [

    ];
    // 系统配置文件夹根目录
    protected static $base_dir = '';

    /**
     * 设置系统配置文件夹的目录
     * @param string $dir
     */
    public static function setBaseDir($dir)
    {
        self::$base_dir = $dir;
    }

    /**
     * 获取指定配置信息
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get($key)
    {
        $k = explode('.', $key, 5);
        $f = $k[0];

        if (!isset(self::$conf[$f])) {
            self::$conf[$f] = include self::$base_dir . $f . '.php';
        }

        switch (count($k)) {
            case 1:
                return self::$conf[$f] ?? null;
            case 2:
                return self::$conf[$f][$k[1]] ?? null;
            case 3:
                return self::$conf[$f][$k[1]][$k[2]] ?? null;
            case 4:
                return self::$conf[$f][$k[1]][$k[2]][$k[3]] ?? null;
            case 5:
                return self::$conf[$f][$k[1]][$k[2]][$k[3]][$k[4]] ?? null;
        }
        return null;
    }

    /**
     * 设置配置信息
     *
     * @param string $key
     * @param $value
     */
    public static function set($key, $value)
    {
        $k = explode('.', $key, 5);
        $f = $k[0];

        if (!isset(self::$conf[$f])) {
            self::$conf[$f] = include self::$base_dir . $f . '.php';
        }

        switch (count($k)) {
            case 1:
                self::$conf[$f] = $value;
                break;
            case 2:
                self::$conf[$f][$k[1]] = $value;
                break;
            case 3:
                self::$conf[$f][$k[1]][$k[2]] = $value;
                break;
            case 4:
                self::$conf[$f][$k[1]][$k[2]][$k[3]] = $value;
                break;
            case 5:
                self::$conf[$f][$k[1]][$k[2]][$k[3]][$k[4]] = $value;
                break;
        }
    }
}
