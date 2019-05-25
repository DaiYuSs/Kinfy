<?php

namespace Kinfy\View\engine;

class Blade implements IEngine
{
    // 模板根目录
    public $base_dir = '';
    // 模板子模板数组
    protected $sub_tpls = [];
    // 模板定界符
    public $tag = ['{', '}'];
    // 要处理的模板字符串
    public $template = '';
    // 模板后缀
    public $suffix = '';


    /**
     * 必备的外部可执行视图函数
     */
    public function compiling()
    {
//        // TODO: Implement compiling() method.
//        // layout又叫master支持,母版
//        $this->extendsExp();
//
//        // 执行include调用
        $this->template = $this->includeExp();
//
//        // 执行判断语句
//        $this->ifExp();
//        $this->elseIfExp();
//        $this->elseExp();
//        $this->endIfExp();
//
//        // 循环语句
        $this->loopExp();
        $this->endLoopExp();
//
//        // 变量
        $this->varExp();

//        $this->template = '开始编译:' . $this->template . ':编译结束';6

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
     * @return string|string[]|null
     */
    public function includeExp($content = null)
    {
//        var_dump($content);
        $exp = 'include\s+(.*?)';
        $pattern = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        // 7 新特性 $content = $content ??  $this->template;
//        if ($content) {
//            return preg_replace_callback(
//                $pattern,
//                [$this, 'includeTpl'],
//                $content
//            );
//        } else {
//            $this->template = preg_replace_callback(
//                $pattern,
//                [$this, 'includeTpl'],
//                $this->template
//            );
//        }
//        return $content === null ?
//            $this->template = preg_replace_callback($pattern, [$this, 'includeTpl'], $this->template) :
//            preg_replace_callback($pattern, [$this, 'includeTpl'], $content);
        // 如果没有传入 $content ,则使用默认指定视图模板内容
        return preg_replace_callback($pattern, [$this, 'includeTpl'], $content === null ? $this->template : $content);
    }

    /**
     * 将需要加载的文件名传给 readTpl($tpl) 方法
     *
     * @param array $matches array(2) { [0]=> string(17) "{include _header}" [1]=> string(7) "_header" }
     * @return string|string[]|null
     */
    protected function includeTpl($matches)
    {
        return $this->readTpl($matches[1]);
    }

    /**
     * 避免 include 环加载，加载不存在文件
     *
     * @param $tpl
     * @return string|string[]|null
     */
    private function readTpl($tpl)
    {
        // 检查模板是否死循环调用
        if (in_array($tpl, $this->sub_tpls)) {
            die("{$tpl} 文件调用产生死循环");
        } else {
            $this->sub_tpls[] = $tpl;
        }
        $file = "{$this->base_dir}/{$tpl}{$this->suffix}";
        if (!file_exists($file)) {
            die("{$file} 文件不存在,或者不可读取");
        }
        $content = file_get_contents($file);
        return $this->includeExp($content);
    }
}
