<?php

namespace Kinfy\View;

use Kinfy\View\engine\Blade;

class View
{
    // 模板引擎所用的编译器
    public $compiler = null;
    // 模板引擎资源所在的根目录,以/结尾
    public $base_dir = '';
    // 模板引擎缓存文件夹(存放资源文件),以'/'结尾
    // dir是物理路径,cache是可以删除的意思
    public $cache_dir = '';
    // 模板主题
    // 主题 theme
    // 皮肤 skin       // 不影响功能,小改动
    // 模板 template   // 大改动
    public $theme = 'default';
    // 模板主题根目录
    //public $base = '';
    // 模板主题缓存目录
    //public $cache = '';
    // 模板文件后缀
    public $suffix = '.tpl.php';
    // 模板自动更新
    public $auto_refresh = true;
    // 模板数据
    public $data = [];

    /**
     * View constructor.
     *
     * @param null $engine 模板引擎
     */
    public function __construct($engine = null)
    {
        // 如果传入引擎,则初始化编译器
        if ($engine) {
            $this->compiler = new $engine();
        } else {
            $this->compiler = new Blade();
        }
        // 生成默认视图模板路径
        if (!$this->base_dir) {
            // 出现在普通字符串中的路径，如果代表的是windows文件路径，则使用 斜杆/ 和 反斜杠\ 是一样的；如果代表的是网络文件路径，则必须使用 斜杆/
            $this->base_dir = '../../kinfy/app/View/';
        }
        // 生成默认视图缓存路径
        if (!$this->cache_dir) {
            $this->cache_dir = '../../kinfy/app/Cache/';
        }
    }

    /**
     * 负责生成主题资源文件根目录
     *
     * @return string
     */
    public function themeBaseDir()
    {
        return $this->base_dir . $this->theme . '/';
    }

    /**
     * 负责生成主题资源文件根目录
     *
     * @return string
     */
    public function themeCacheDir()
    {
        return $this->cache_dir . $this->theme . '/';
    }

    /**
     * 根据名称返回模板资源文件
     *
     * @param string $name 模板名称
     * @return string 模板文件地址
     */
    public function tplFile($name)
    {
        return $this->themeBaseDir() . $name . $this->suffix;
    }

    /**
     * 根据名称返回模板缓存文件
     *
     * @param string $name 模板名称
     * @return string 缓存模板文件地址
     */
    public function tplCache($name)
    {
        return $this->themeCacheDir() . $name . '.php';
    }

    /**
     * 模板变量赋值
     *
     * @param string $name 模板变量名称
     * @param mixed $value 变量内容值,类型多样
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * 模板变量赋值
     *
     * @param string $name 模板变量名称
     * @param mixed $value 变量内容值,类型多样
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 模板显示
     *
     * @param string $tpl 模板名称
     */
    public function show($tpl)
    {
        extract($this->data);
        // 拼接缓存文件 如 index 转换成 E:/kinfy/Cache/default/index.tpl.php
        $tpl_cache = $this->tplCache($tpl);
        // 强制更新
        if ($this->auto_refresh || !file_exists($tpl_cache)) {
            $this->compiling($tpl);
        }
        include_once $tpl_cache;
    }

    /**
     * 模板编译
     *
     * @param string $tpl 模板名称
     */
    public function compiling($tpl)
    {
        $c = $this->compiler;
        // 传入最开始的模板名
        $c->tpl = $tpl;
        $c->base_dir = $this->themeBaseDir();
        $c->suffix = $this->suffix;
        // 模板读取
        $c->template = file_get_contents($this->tplFile($tpl));

        // 模板编译
        $c->compiling();

        // 写入模板前,判断是否有对应主题的缓存文件夹,如无,则创建
        $this->mkTplCacheDir($tpl);
        // 写入模板缓存文件
        file_put_contents($this->tplCache($tpl), $c->template);
    }

    /**
     * 创建缓存文件所对应的目录,逐级创建
     *
     * @param string $tpl 模板名称(带地址)
     */
    private function mkTplCacheDir($tpl)
    {
        $tpl_cache_path = $this->theme . '/' . $tpl;
        $path_arr = explode('/', $tpl_cache_path);
        $dir = $this->cache_dir;
        // 第一个不需要,最后一个是文件名也不需要
        for ($i = 0; $i < count($path_arr) - 1; $i++) {
            $dir .= $path_arr[$i] . '/';
            if (!is_dir($dir)) {
                mkdir($dir);
            }
        }
    }
}
