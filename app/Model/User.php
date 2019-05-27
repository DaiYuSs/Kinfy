<?php

namespace App\Model;

class User extends BaseModel
{
    //该模型绑定的数据表
    //如不指定则自动类名作为数据库表名
    protected $table = 'users';

    //protected $autoCamelCase = false;

    //数据库列名转对象属性
    //key:数据库列名
    //value:模型属性名
    protected $field2property = [
        'user_name' => 'name'
    ];

    //数据库列在查询后的显示字符
    //一般情况下配合权限过滤
    public $fieldView = [
        'password' => '***'
    ];

    /**
     * User constructor.
     *
     */
    public function __construct($id = null)
    {
        parent::__construct();
        if ($id != null) {
            $user = $this->where('id', $id)->first();
            return $user;
        }
    }

    /**
     * 借书案例
     * @param $book
     */
    public function borrow($book)
    {
        UserBook::add([
            'user_id' => $this->properties[$this->pk],
            'book_id' => $book->properties[$book->pk]
        ]);
    }
}