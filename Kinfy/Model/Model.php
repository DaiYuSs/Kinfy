<?php

namespace Kinfy\Model;

use Kinfy\DB\DB;

class Model
{
    // 存放当前实例
    protected static $instance = null;
    // 当前实例对应的数据表
    protected $table = '';
    // 当前实例的数据库对象
    protected $DB = null;
    // 实例属性
    public $properties = [];
    // 数据库列名
    protected $fields = [];
    // 数据库列名别名，键：数据库列名，值：对象属性名
    protected $field2property = [];
    // 实例属性名对应的数据库列名
    private $property2field = [];
    // 是否自动大小写和下划线之间进行转换
    protected $autoCamelCase = true;
    // 模型对应的数据库的主键
    protected $pk = 'id';
    // 主键是否是由数据库自动生成（比如，自增主键）
    protected $autoPk = true;
    // 不允许批量添加的数据库字段
    protected $guarded = [];
    // 允许批量添加的数据库字段
    protected $fillable = [];
    // 隐藏指定字段值
    public $fieldView = [];

    /**
     * 构造函数,初始化当前实例对应的数据表,初始化当前实例对应的数据库对象
     *
     * @param null $id 如果传入id,则默认查找当前id的内容
     */
    public function __construct($id = null)
    {
        if (!$this->table) {
            // 返回对象的类名,包括命名空间
            $class = get_class($this);
            // 去除命名空间,首字母小写,复数形式作为表名
            $this->table = lcfirst(substr($class, strrpos($class, '\\') + 1)) . 's';
        }
        if (!$this->DB) {
            $this->DB = new DB();
        }
        $this->DB->table($this->table);
        if ($id != null) {
            $this->DB->where($this->pk, $id);
            //不能调用DB的first,因为自己的first有做字段过滤
            $this->first();
        }
    }

    /**
     * 如果主键不是数据库自动生成,则应当重写主键生成方法
     *
     * @return string
     */
    protected function genPk()
    {
        return uniqid(md5(microtime(true)));
    }

    /**
     * 判断给定字符串是否为大写字母
     *
     * @param string $str
     * @return bool
     */
    private function isUpper($str)
    {
        return ord($str) > 64 && ord($str) < 91;
    }

    /**
     * 属性转换数据库字段规则
     *
     * @param string $name
     * @return string
     */
    protected function property2field(string $name)
    {
        if (empty($this->property2field)) {
            $this->property2field = array_flip($this->field2property);
        }
        if (isset($this->property2field[$name])) {
            return $this->property2field[$name];
        }
        if ($this->autoCamelCase) {
            return $this->camel2snake($name);
        }
        return $name;
    }

    /**
     * 数据库字段转换属性规则
     *
     * @param string $name
     * @return string
     */
    protected function field2property($name)
    {
        if (isset($this->field2property[$name])) {
            return $this->field2property[$name];
        }
        if ($this->autoCamelCase) {
            return $this->snake2camel($name);
        }
        return $name;
    }

    /**
     * 暂废弃
     * 执行DB方法前,将传入的参数名进行转换成数据库字段对应的参数名
     *
     * @param array $arguments 需要被转换的参数名
     * @return array 转换后的参数组
     */
//    protected function filterArguments(array $arguments)
//    {
//        foreach ($arguments as &$v) {
//            $v = $this->property2field($v);
//        }
//        return $arguments;
//    }

    /**
     * 往数据库添加的时候,属性名fielName 转换成 file_name 形式
     * 同时经过黑白名单过滤
     *
     * @param array $data
     */
    protected function filterFields(array $data = [])
    {
        // 批量添加的数据，经过黑白名单过滤
        if ($data) {
            foreach ($data as $k => $v) {
                $k = $this->property2field($k);
                // 如果白名单有内容,且字段不在白名单就跳过
                // 如果设置了白名单，以白名单为准，否则以黑名单为准
                if ($this->fillable) {
                    if (!in_array($k, $this->fillable)) {
                        continue;
                    }
                }
                // 如果黑名单有内容,且字段不在黑名单就跳过
                if ($this->guarded) {
                    if (in_array($k, $this->guarded)) {
                        continue;
                    }
                }
                $this->fields[$k] = $v;
            }
        }
        // 手工添加的数据不过滤
        foreach ($this->properties as $k2 => $v2) {
            $k2 = $this->property2field($k2);
            $this->fields[$k2] = $v2;
        }
    }

    /**
     * 数据库读取出来的数据,列名为field_name 转换成 fieldName
     *
     * @param array $data
     * @return array
     */
    protected function filterProperties($data)
    {
        // 没有自定义列名转换规则,同时也关闭了自动转换规则,且没有隐藏列,则直接返回
        if (empty($this->field2property) && !$this->autoCamelCase && empty($this->fieldView)) {
            return $data;
        }
        $new_data = [];
        foreach ($data as $k => $v) {
            // 查看是否有自定义的转换规则
            $k2 = $this->field2property($k);
            // 是否有设置字段为隐藏内容
            if (isset($this->fieldView[$k])) {
                $new_data[$k2] = $this->fieldView[$k];
            } else {
                $new_data[$k2] = $v;
            }
        }
        return $new_data;
    }

    /**
     * camelSnake 转换成 camel_snake
     * camelSNAKE        camel_snake
     * camel_SNAKE       camel_snake
     * camel_SNAKeName   camel_snake_name
     *
     * @param string $str 数据库字段名
     * @return string 固定小写字段名
     */
    protected function camel2snake($str)
    {
        $s = '';
        $str = str_split($str);
        foreach ($str as $k => $v) {
            // 第二个开始判断,如果是大写字母,且该字母前一个不是大写或者下划线,则添加'_'
            if (
                $k > 0 &&
                $this->isUpper($str[$k]) &&
                !$this->isUpper($str[$k - 1]) &&
                $str[$k - 1] != '_'
            ) {
                $s .= '_';
            }
            $s .= $str[$k];
        }
        return strtolower($s);
    }

    /**
     * camel_snake 转换成 camelSnake
     *
     * @param string $str
     * @return string
     */
    protected function snake2camel($str)
    {
        $c = '';
        // 按照'_'分割
        $str_arr = explode('_', $str);
        foreach ($str_arr as $k => $s) {
            // 除第一个单词外,首字母统一大写
            if ($k > 0) {
                $s = ucfirst($s);
            }
            $c .= $s;
        }
        return $c;
    }

    /**
     * 设置一个不存在的属性名的时候, 自动将值存到当前实例的properties属性
     *
     * @param string $name
     * @param string|int $value
     */
    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * 读取一个不存在的属性名的时候, 自动到实例的properties属性数组里去获取
     *
     * @param $name
     * @return string
     */
    public function __get($name)
    {
        return $this->properties[$name];
    }

    /**
     * 检测方法是否为DB类的终端方法
     *
     * @param string $name 方法名
     * @return bool 是否匹配值(true|false)
     */
    private function isTerminalMethod(string $name)
    {
        $name = strtolower($name);
        $m = [
            'get',
            'first',
            'value'
        ];
        return in_array($name, $m);
    }

    /**
     * 在该数组里的方法,自动会调用自身带下划线对应的方法
     *
     * @param string $name 方法名
     * @return bool 是否匹配值(true|false)
     */
    private function isSelfMethod(string $name)
    {
        $name = strtolower($name);
        $m = [
            'add',
            'update',
            'save'
        ];
        return in_array($name, $m);
    }

    /**
     * 当调用一个不存在的实例方法时,则自动调用DB类的方法
     *
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return $this|array
     */
    public function __call($name, $arguments)
    {
        // $arguments = $this->filterArguments($arguments);
        $name = strtolower($name);
        // 如果是调用自身的,则不调用DB
        if ($this->isSelfMethod($name)) {
            $sname = '_' . $name;
            return $this->{$sname}(...$arguments);
        }
        $r = $this->DB->{$name}(...$arguments);
        //如果不是结束节点(获取数据)
        if (!$this->isTerminalMethod($name)) {
            return $this;
        }
        // 如果未自定义规则,未开启自动转换,则不转换直接返回
        if (empty($this->field2property) && !$this->autoCamelCase) {
            return $r;
        }
        if (is_array($r)) {
            if ($name == 'get') {
                foreach ($r as &$data) {
                    $data = $this->filterProperties($data);
                }
            } elseif ($name == 'first') {
                $r = $this->filterProperties($r);
                foreach ($r as $k => $v) {
                    $this->$k = $v;
                }
            } elseif ($name == 'value') {
                $r = $this->filterProperties($r);
                return array_values($r)[0];
            }
        }
        return $r;
    }

    /**
     * 当调用一个不存在的实例静态方式时,则自动调用实例非静态方法
     *
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public static function __callStatic($name, $arguments)
    {
        // 当静态实例未被定义时,自动实例化
        return static::getInstance()->{$name}(...$arguments);
    }

    /**
     * 根据类名生成指定静态类
     *
     * @return $this
     */
    public static function getInstance()
    {
        $sub_class = get_called_class();

        if (!isset(static::$instance[$sub_class])) {
            static::$instance[$sub_class] = new static();
        }
        return static::$instance[$sub_class];
    }

    /**
     * 新增或者更新方法,取决于主键是否有值
     *
     * @param array $data
     * @return mixed
     */
    private function _save(array $data = [])
    {
        // 如果没有主键,则添加,否则更新
        if (!$this->havePk()) {
            return $this->add($data);
        }
        return $this->update($data);
    }

    /**
     * 预先处理写进去的数据库字段名称，把属性名转换成数据库字段名,执行DB类的 insert 操作
     * $this->filterFields();多个场所可能使用到此方法,所以没有封装到save里面
     *
     * @param array $data
     * @return mixed
     */
    private function _add(array $data)
    {
        // 预先处理写进去的数据库字段，把属性名转换成数据库字段名
        $this->filterFields($data);
        // 如果字段主键是自动生成的,则删除主键的值
        if ($this->autoPk) {
            unset($this->fields[$this->pk]);
        } elseif (!$this->havePk()) {
            $this->fields[$this->pk] = $this->genPk();
        }
        return $this->DB->insert($this->fields);
    }

    /**
     * 预先处理写进去的数据库字段，把属性名转换成数据库字段名,执行DB类的 update 操作
     * $this->filterFields();多个场所可能使用到此方法,所以没有封装到save里面
     *
     * @param array $data
     * @return bool
     */
    private function _update(array $data)
    {
        $this->filterFields($data);
        $k = $this->pk;
        $v = $this->fields[$this->pk];

        unset($this->fields[$this->pk]);
        return $this->DB
            ->where($k, $v)
            ->update($this->fields);
    }

    /**
     * 判断是否有设置主键属性和值
     *
     * @return bool
     */
    public function havePk()
    {
        return (isset($this->fields[$this->pk]) && $this->fields[$this->pk] !== null);
    }
}
