<?php

namespace Kinfy\DB;

use PDO;

class DB
{
    private $pdo = null;
    private $where = [];
    private $table = '';
    private $limit = [];

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
    public function where($field, $op, $val)
    {
        $this->where[] = [$field, $op, $val];
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
        $sep = '';
        foreach ($this->where as $w) {
            list($field, $op, $val) = $w;
            $where .= "{$sep}`{$field}`{$op}? ";
            //默认条件是AND
            $sep = ' and ';
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
        $genwhere = $this->genWhere();
        $limit = '';
        if ($this->limit) {
            $limit = " limit {$this->limit[0]},{$this->limit[1]} ";
        }
        //1.准备SQL
        $sql = "select * from {$this->table} $genwhere[0] {$limit}";
        $pdosmt = $this->pdo->prepare($sql);
        $pdosmt->execute($genwhere[1]);
        return $pdosmt->fetchAll();
    }
}