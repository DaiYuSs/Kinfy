<?php

namespace Kinfy\DB;

use Kinfy\Config\Config;
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
    public $orderby = [];

    /**
     * DB初始化连接数据库和全局属性
     *
     * @param null $conf
     */
    public function __construct($conf = null)
    {
        $conf = $conf ?? Config::get('db');
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

    /**
     * 根据指定条件获取数据
     *
     * @param int $offset 跳过多少条数据
     * @param int $num 取多少个数据
     * @return $this
     */
    public function limit($offset, $num)
    {
        $this->limit = [$offset, $num];
        return $this;
    }

    /**
     * 从数据库第一条开始取数据
     *
     * @param int $num 取多少个数据
     * @return $this
     */
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
        $JOIN = $this->join;
        $ORDERBY = $this->orderby ? 'ORDER BY ' : '';
        foreach ($this->orderby as $ob) {
            $is_identifier = strpos($ob[0], '.') !== false;
            if (!$is_identifier) {
                $ob[0] = "`{$ob[0]}`";
            }
            $ORDERBY .= "{$ob[0]} {$ob[1]},";
        }
        $ORDERBY = rtrim($ORDERBY, ',');
        //1.准备SQL
        $this->sql['sql'] = "select {$this->fields} from {$this->table} {$JOIN} {$WHERE} {$ORDERBY} {$limit}";
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
        $pdosmt = $this->execute()['pdosmt'];
        return $pdosmt->fetchAll();
    }

    /**
     * 清空所有所需的条件
     */
    public function clear()
    {
        $this->where = [];
        //$this->table = '';,表名不清除，让模型只需赋值一次
        $this->limit = [];
        $this->fields = '*';
        $this->sql = [];
        $this->join = '';
        $this->orderby = [];
    }

    /**
     * 将数据进行微处理转给 batchInsert 方法
     *
     * @param array $values 键(字段名)值对
     * @param bool $force_align 是否为固定列
     * @return mixed
     */
    public function insert($values, $force_align = true)
    {
        //如果不是二维数组,则封装成二维数组
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        return $this->batchInsert($values, $force_align);
    }

    /**
     * 批量执行 insert
     *
     * @param array $values 待插入
     * @param boolean $force_align 是否为固定列
     * @return mixed
     */
    private function batchInsert($values, $force_align)
    {
        if ($force_align) {
            $all_vals = [];
            //提取所有的值
            foreach ($values as $val) {
                // 键排序
                ksort($val);
                foreach ($val as $v) {
                    $all_vals[] = $v;
                }
            }
            // 获取最后一条数据的所有键,并用，分割
            $field_keys_str = implode(',', array_keys($val));
            // 构造占位符ph === placeholder
            $ph = [];
            // 填充固定个'?'
            $ph = array_pad($ph, count($val), '?');
            // ','分割'?'
            // ?,?
            $ph = implode(',', $ph);
            $all_placeholder = '';
            // 填充多行'?'并用(),包裹
            // (?,?,?),(?,?,?),
            for ($i = 0; $i < count($values); $i++) {
                $all_placeholder .= "({$ph}),";
            }
            // 去掉最后一个','
            $all_placeholder = rtrim($all_placeholder, ',');
            $this->sql = [
                'sql' => "insert into `{$this->table}` ({$field_keys_str}) values {$all_placeholder}",
                'value' => $all_vals
            ];
        } else {
            $this->sql['sql'] = '';
            $this->sql['value'] = [];
            foreach ($values as $val) {
                //构造占位符ph === placeholder
                $ph = [];
                // 填充固定个'?'
                $ph = array_pad($ph, count($val), '?');
                // ','分割'?'
                // ?,?
                $ph = implode(',', $ph);
                // 提取数组key用','分割
                // title,content
                $field_keys_str = implode(',', array_keys($val));
                // 拼接所有insert sql语句
                $this->sql['sql'] .= "insert into `{$this->table}` ({$field_keys_str}) values ({$ph});\n";
                foreach ($val as $v) {
                    $this->sql['value'][] = $v;
                }
            }
        }
        return $this->execute()['status'];
    }

    /**
     * @param string $field 按什么字段排序
     * @param string $dir 排序规则
     * @return $this
     */
    public function orderBy($field, $dir = 'DESC')
    {
        $this->orderby[] = [$field, $dir];
        return $this;
    }

    /**
     * 按照参数 $data 提供更新数据
     *
     * @param array $data 键(字段名)值对
     * @return bool
     */
    public function update($data)
    {
        if (empty($data)) {
            return false;
        }
        $SET = ' SET ';
        $values = [];
        foreach ($data as $k => $v) {
            $SET .= "`{$k}` = ?,";
            $values[] = $v;
        }
        $SET = rtrim($SET, ',');

        list($WHERE, $VAL) = $this->genWhere();
        $this->sql['sql'] = "UPDATE `{$this->table}` {$SET} {$WHERE}";
        $this->sql['value'] = array_merge($values, $VAL);
        return $this->execute()['status'];
    }

    /**
     * 执行sql删除语句
     *
     * @return mixed
     */
    public function delete()
    {
        list($WHERE, $VAL) = $this->genWhere();

        $this->sql['sql'] = "DELETE from `{$this->table}` {$WHERE}";
        $this->sql['value'] = $VAL;

        return $this->execute()['status'];
    }

    /**
     * 准备要执行的语句，执行一条预处理语句,返回DB结果与状态
     *
     * @return array
     */
    private function execute()
    {
        $pdosmt = $this->pdo->prepare($this->sql['sql']);
        $r = $pdosmt->execute($this->sql['value']);
        if (!$r) {
            print_r($pdosmt->errorInfo());
        }
        $this->clear();
        return [
            'pdosmt' => $pdosmt,
            'status' => $r
        ];
    }

    /**
     * 只获取一条数据的某一个属性
     *
     * @param string $field 列名
     * @return string
     */
    public function value($field)
    {
        $data[$field] = $this->first()[$field];
        return $data;
    }
}
