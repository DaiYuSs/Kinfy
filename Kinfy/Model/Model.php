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
    // 主键是否是由数据库自动生成的
    protected $autoPk = true;

    /**
     * 构造函数,初始化当前实例对应的数据表,初始化当前实例对应的数据库对象
     *
     * Model constructor.
     */
    public function __construct()
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
     * 往数据库添加的时候,属性名fielName 转换成 file_name 形式
     *
     */
    protected function filterFields()
    {
        if (empty($this->property2field)) {
            $this->property2field = array_flip($this->field2property);
        }
        foreach ($this->properties as $k => $v) {
            if (isset($this->property2field[$k])) {
                $k = $this->property2field[$k];
            } else {
                if ($this->autoCamelCase) {
                    $k = $this->camel2snake($k);
                }
            }
            $this->fields[$k] = $v;
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
        if (empty($this->field2property) && !$this->autoCamelCase) {
            return $data;
        }
        $new_data = [];
        foreach ($data as $k => $v) {
            if (isset($this->field2property[$k])) {
                $k = $this->field2property[$k];
            } else {
                if ($this->autoCamelCase) {
                    $k = $this->snake2camel($k);
                }
            }
            $new_data[$k] = $v;
        }
        return $new_data;
    }

    /**
     * 第二个开始判断,如果是大写字母,且该字母前一个不是大写或者下划线
     * camelSnake 转换成 camel_snake
     *
     * @param string $str
     * @return string
     */
    protected function camel2snake($str)
    {
        $s = '';
        $str = str_split($str);
        foreach ($str as $k => $v) {
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
     * 按照'_'分割,除第一个单词外,首字母统一大写
     * camel_snake 转换成 camelSnake
     *
     * @param string $str
     * @return string
     */
    protected function snake2camel($str)
    {
        $c = '';
        $str_arr = explode('_', $str);
        foreach ($str_arr as $k => $s) {
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
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->properties[$name];
    }

    /**
     * 检测方法是否为DB类的终端方法
     *
     * @param $name
     * @return bool
     */
    private function isTerminalMethod($name)
    {
        $m = [
            'get',
            'first',
            'insert'
        ];
        return in_array($name, $m);
    }

    /**
     * 当调用一个不存在的实例方法时,则自动调用DB类的方法
     *
     * @param string $name
     * @param array $arguments
     * @return $this|array
     */
    public function __call($name, $arguments)
    {
        $name = strtolower($name);
        $r = $this->DB->{$name}(...$arguments);
        //如果不是结束节点(获取数据)
        if (!$this->isTerminalMethod($name)) {
            return $this;
        }
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
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance->{$name}(...$arguments);
    }

    /**
     *
     *
     * @param bool $isAdd
     * @return mixed
     */
    public function save($isAdd = false)
    {
        // 如果强制添加,或者没有主键,则添加,否则更新
        if ($isAdd || !$this->havePk()) {
            return $this->add();
        }
        return $this->update();
    }

    /**
     * 预先处理写进去的数据库字段名称，把属性名转换成数据库字段名,执行DB类的 insert 操作
     * $this->filterFields();多个场所可能使用到此方法,所以没有封装到save里面
     *
     * @return mixed
     */
    private function add()
    {
        $this->filterFields();
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
     * @return bool
     */
    private function update()
    {
        $this->filterFields();
        $k = $this->pk;
        $v = $this->fields[$this->pk];

        unset($this->fields[$this->pk]);
        return $this->DB
            ->where($k, '=', $v)
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

    /**
     * 当执行静态代码时,自身有一个实例static::$instance
     * 返回静态的共用的实例
     *
     * @return $this|null
     */
    public static function getInstance()
    {
        return static::$instance;
    }
}
