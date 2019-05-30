<?php

namespace Kinfy\View\engine;

class Blade implements IEngine
{
    // 模板根目录
    public $base_dir = '';
    // 模板子模板数组
    protected $sub_tpls = [];
    // 模板对应的父模板路径数组
    protected $tpl_parents = [];
    // 模板定界符
    public $tag = ['{', '}'];
    // 要处理的模板字符串
    public $template = '';
    // 当前解析的模板名称
    public $tpl = '';
    // 模板后缀
    public $suffix = '';
    // 母版占位符对应的内容
    protected $tag_body = [];


    /**
     * 必备的外部可执行视图函数
     */
    public function compiling()
    {
        // TODO: Implement compiling() method.
        // layout又叫master支持,母版
        $this->extendsExp();

        // 执行include调用
        $this->template = $this->includeExp();

        // 执行判断语句
        // $this->ifExp();
        // $this->elseIfExp();
        // $this->elseExp();
        // $this->endIfExp();

        // 循环语句
        $this->loopExp();
        $this->endLoopExp();

        // 变量
        $this->varExp();

        // $this->template = '开始编译:' . $this->template . ':编译结束';6

    }

    /**
     * 正则替换模板
     * 搜索 $this->template 中匹配 $pattern 的部分，以 $replace 进行替换。
     *
     * @param string $find 视图源码正则匹配规则
     * @param string $replace 要被替换成的php源码
     */
    public function _replace(string $find, string $replace)
    {
        $pattern = "/{$this->tag[0]}\s*{$find}\s*{$this->tag[1]}/is";
        $this->template = preg_replace(
            $pattern,
            $replace,
            $this->template
        );
    }

    /**
     * 变量转换
     * 生成查找变量正则规则
     * 生成需被替换成的php源码
     *
     */
    public function varExp()
    {
        // [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*   这是官方给出的变量名正则,不含$
        $exp = '(\$[a-zA-Z_][0-9a-zA-Z_\'\"\[\]]*)';
        // 防止变量使用错误,用isset控制变量
        $replace = "<?php if(isset(\\1)){echo \\1;}?>";
        $this->_replace($exp, $replace);
    }

    /**
     * loop循环标签
     * 常规写法{loop $k in $arr}
     * 支持类似案例的{incon:loop $hi in $header_icon},造成COUNT和INDEX换成了incon_COUNT和incon_INDEX
     */
    public function loopExp()
    {
        $exp = '(([a-zA-Z]+)\s*:\s*)?loop\s+(.*?)\s+in\s+(.*?)';
        $replace = "<?php
            if(is_array(\\4)){
                \$\\2_COUNT = count(\\4);
            }
            \$\\2_INDEX = 0;
            foreach(\\4 as \\3){
                \$\\2_INDEX++;
                
        ?>";
        $this->_replace($exp, $replace);
    }

    /**
     * end loop
     */
    public function endLoopExp()
    {
        $exp = '\/loop';
        $replace = '<?php } ?>';
        $this->_replace($exp, $replace);
    }

    /**
     * include加载标签
     * 常规写法{include _header}
     *
     * @param null|string $content 模板内容
     * @param null|string $parent 当前父加载的模板名
     * @return string|string[]|null
     */
    public function includeExp($content = null, $parent = null)
    {
        $exp = 'include\s+(.*?)';
        $pattern = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        // 7 新特性 $content = $content ??  $this->template;
        // 如果没有传入 $content ,则使用默认指定视图模板内容
        return preg_replace_callback($pattern,
            function ($matches) use ($parent) {
                return $this->includeTpl($matches[1], $parent);
            },
            $content ?? $this->template);
    }

    /**
     * 是否死循环加载判断
     * 获取模板内容传给includeExp替换
     *
     * @param string $sub 模板名
     * @param string $parent 父(加载当前模板的模板)模板名
     * @return string|string[]|null
     */
    protected function includeTpl($sub, $parent)
    {
        // 判断该模板的父模板有过加载动作
        if (isset($this->tpl_parents[$parent])) {
            // 获取父模板加载过的所有模板名称数组
            $parent_path = $this->tpl_parents[$parent];
            // 添加当前模板名称到父模板加载记录数组
            $parent_path[] = $parent;
            // 检查模板是否死循环调用
            if (in_array($sub, $parent_path)) {
                print_r($this->tpl_parents);
                die("{$parent}调用{$sub} 文件产生死循环");
            } else {
                // 重新给父模板加载过的所有模板数组赋值
                $this->tpl_parents[$sub] = $parent_path;
            }
        } else {
            // 初始化
            $this->tpl_parents[$sub] = [$parent];
        }
        return $this->readTpl($sub);
    }

    /**
     * 加载子模版,获取子模版内容和子模版名称传给includeExp
     *
     * @param string $tpl
     * @return string|string[]|null
     */
    protected function readTpl($tpl)
    {
        // 拼接子模版地址
        $file = "{$this->base_dir}/{$tpl}{$this->suffix}";
        if (!file_exists($file)) {
            die("{$file} 文件不存在,或者不可读取");
        }
        // 获取子模版文本内容
        $content = file_get_contents($file);
        return $this->includeExp($content, $tpl);
    }

    /**
     * extends 母版标签
     *
     */
    public function extendsExp()
    {
        $exp = 'extends\s+(.*?)';
        $patter = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        // 判断当前页面是否有存在extends
        $ismatch = preg_match($patter, $this->template, $matches);
        if ($ismatch) {
            // 母版内容
            $master = $this->readTpl($matches[1]);
            $this->getTagBody($master);
            $exp_ph = '@(.*?)';
            $patter_ph = "/{$this->tag[0]}\s*{$exp_ph}\s*{$this->tag[1]}/is";
            // 继承母版内容并替换完插槽
            $this->template = preg_replace_callback($patter_ph, [$this, 'replaceTag'], $master);
        }
    }

    /**
     * 判断当前页是否有定义相应插槽数据
     *
     * @param array $matches Array([0] => {@news_footer},[1] => news_footer)
     * @return mixed|string
     */
    private function replaceTag($matches)
    {
        return $this->tag_body[$matches[1]] ?? '';
    }

    /**
     * 获取母版插槽名称,根据名称来查找当前页面是否有定义插槽内容
     *
     * @param string $master 母版全部内容
     */
    private function getTagBody($master)
    {
        $exp = '@(.*?)';
        $patter = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        // 查找母版内插槽名称赋值给$matches 0=>母版全称,1是去掉@符号的真实名称
        preg_match_all($patter, $master, $matches);
        $this->tag_body = [];
        foreach ($matches[1] as $ph) {
            // $ph = new_body  {news_body}(.*?){/news_body}
            $patter_ph = "/{$this->tag[0]}\s*({$ph})\s*{$this->tag[1]}(.*?){$this->tag[0]}\s*\/{$ph}\s*{$this->tag[1]}/is";
            // 根据规则,在当前页面内容 $this->template 查找母版插槽名下的内容
            $ismatched = preg_match($patter_ph, $this->template, $matches_ph);
            if ($ismatched) {
                $this->tag_body[$matches_ph[1]] = $matches_ph[2];
            }
        }
    }
}
