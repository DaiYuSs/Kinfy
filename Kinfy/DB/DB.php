<?php

namespace Kinfy\DB;

use PDO;

class DB
{
    private $pdo = null;
    private $where = [];
    private $table = '';
    private $limit = [];
    private $fields = '*';
    public $sql = [];
    public $join = '';

    /**
     * DB初始化连接数据库和全局属性
     *
     * @param null $conf
     */
    public function __construct($conf = null)
    {
        if ($conf == null) {
            $conf = [
                'dbms' => 'mysql',
                'host' => '127.0.0.1',
                'name' => 'kinfy',
                'user' => 'root',
                'pass' => ''
            ];
        }
        $dsn = "{$conf['dbms']}:host={$conf['host']};dbname={$conf['name']}";
        try {
            //开始连接数据库
            $this->pdo = new PDO($dsn, $conf['user'], $conf['pass']);
            //echo '连接成功';
            //设置字符集
            $this->pdo->query('set names utf8');
        } catch (PDOException $e) {
            //连接失败错误提示
            die('error:' . $e->getMessage());
        }
        //设置全局属性，默认读取的数据已关联数组的形式返回
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * 设置sql默认查询的表
     *
     * @param string $name
     * @return $this
     */
    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    public function limit($offset, $num)
    {
        $this->limit = [$offset, $num];
        return $this;
    }

    public function take($num)
    {
        return $this->limit(0, $num);
    }

    /**
     * 获取所需数据的第一条
     *
     * @return bool|mixed
     */
    public function first()
    {
        $r = $this->take(1)->get();
        if (isset($r[0])) {
            return $r[0];
        } else {
            return false;
        }
    }

    /**
     * 设置查询条件参数(字段,操作符,值)
     * opt === option   op === operator
     *
     * @param string $field
     * @param string $op
     * @param string $val
     * @return $this
     */
    public function where($field, $op, $val = null)
    {
        return $this->andWhere($field, $op, $val);
    }

    /**
     * where 条件语句存储
     *
     * @param string $field
     * @param string $op
     * @param string $val
     * @param string $type
     * @return $this
     */
    private function _where($field, $op, $val, $type)
    {
        if ($val === null) {
            $val = $op;
            $op = '=';
        }
        $this->where[] = [$field, $op, $val, $type];
        return $this;
    }

    /**
     *
     * @param $field
     * @param $op
     * @param null $val
     * @return DB
     */
    public function andWhere($field, $op, $val = null)
    {
        return $this->_where($field, $op, $val, 'and');
    }

    /**
     *
     * @param $field
     * @param $op
     * @param null $val
     * @return DB
     */
    public function orWhere($field, $op, $val = null)
    {
        return $this->_where($field, $op, $val, 'or');
    }

    /**
     * 指向andWhereNull
     *
     * @param $field
     * @return $this
     */
    public function whereNull($field)
    {
        return $this->andWhereNull($field);
    }

    /**
     *
     * @param $field
     * @return $this
     */
    public function andWhereNull($field)
    {
        return $this->_where($field, 'IS', 'NULL', 'and');
    }

    /**
     *
     * @param $field
     * @return $this
     */
    public function orWhereNull($field)
    {
        return $this->_where($field, 'IS', 'NULL', 'or');
    }

    /**
     * 指向andWhereNotNull
     *
     * @param $field
     * @return $this
     */
    public function whereNotNull($field)
    {
        return $this->andWhereNotNull($field);
    }

    /**
     *
     * @param $field
     * @return $this
     */
    public function andWhereNotNull($field)
    {
        return $this->_where($field, 'IS NOT', 'NULL', 'and');
    }

    /**
     *
     * @param string $field
     * @return $this
     */
    public function orWhereNotNull($field)
    {
        return $this->_where($field, 'IS NOT', 'NULL', 'or');
    }

    /**
     * 插入一个'('
     *
     * @return $this
     */
    public function L()
    {
        return $this->_where('(', '', '', '');
    }

    /**
     * 插入一个')'
     *
     * @return $this
     */
    public function R()
    {
        return $this->_where(')', '', '', '');
    }

    /**
     * 根据指定条件生成SQL语句与预处理的值
     *
     * @return array
     */
    public function genSql()
    {
        if ($this->sql != []) {
            return $this->sql;
        }
        list($WHERE, $VAL) = $this->genWhere();
        $limit = '';
        if ($this->limit) {
            $limit = " limit {$this->limit[0]},{$this->limit[1]} ";
        }
        //1.准备SQL
        $this->sql['sql'] = "select {$this->fields} from {$this->table} {$this->join} $WHERE {$limit}";
        $this->sql['value'] = $VAL;
        return $this->sql;
    }

    /**
     * 简易的join暴力插入
     *
     * @param string $table
     * @param string $condition
     * @return $this
     */
    public function join($table, $condition)
    {
        $this->join = "JOIN {$table} ON {$condition}";
        return $this;
    }

    /**
     * 接收任意个参数作为sql语句指定列
     *
     * @param mixed ...$fields
     * @return $this
     */
    public function select(...$fields)
    {
        if (!empty($fields)) {
            $sep = '';
            $this->fields = '';
            foreach ($fields as $f) {
                $is_identifier = strpos($f, '.') !== false;
                if (!$is_identifier) {
                    $f = "`{$f}`";
                }
                $this->fields .= "{$sep}{$f}";
                $sep = ',';
            }
        }
        return $this;
    }

    /**
     * 生成条件判断语句预处理和预处理参数
     *
     * @return array
     */
    private function genWhere()
    {
        //1.生成带?的条件语句
        $where = '';
        //2.生成?对于值的数组
        $values = [];
        //关系表达式
        $sep = '';
        foreach ($this->where as $w) {
            list($field, $op, $val) = $w;
            $where .= " {$sep} `{$field}` {$op} ? ";
            $values[] = $val;
        }
        $where = $where ? ' where ' . $where : $where;
        return [$where, $values];
    }

    /**
     * 获取数据
     *
     * @return array
     */
    public function get()
    {
        $this->genSql();
        $pdosmt = $this->pdo->prepare($this->sql['sql']);
        $r = $pdosmt->execute($this->sql['value']);
        if (!$r) {
            print_r($pdosmt->errorInfo());
        }
        $this->clear();
        return $pdosmt->fetchAll();
    }

    /**
     * 清空所有所需的条件
     */
    public function clear()
    {
        $this->where = [];
        $this->table = '';
        $this->limit = [];
        $this->fields = '*';
        $this->sql = [];
        $this->join = '';
    }
}