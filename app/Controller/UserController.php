<?php

namespace App\Controller;

use App\Model\Article;
use App\Model\User;
use App\Model\UserBook;
use Kinfy\DB\DB;

class UserController extends BaseController
{
    public function index($id, $name = null)
    {
        echo $id . ' ' . $name;
    }

    public function add($name)
    {
        $user = new User();
        $user->name = '3378';
        $user->mobile = 13777777777;
        $user->save();
//
//        $r = User::insert([
//            'user_name' => $name
//        ]);
//        print_r($r);

//        $r = Article::getHot();
//        print_r($r);
    }

    public function del($id = '空空如也')
    {
        echo 'user del:' . $id;
    }

    public function beforedel($id = null)
    {
        if ($id == null) {
            echo '请输入需要删除人的id';
            die();
        }
    }

    public function afterdel($id)
    {
        if ($id == '1') {
            echo '<br>这是超级管理员';
        }
    }

    public function put()
    {
        echo 'user put';
    }

    public function content($id, $content)
    {
        echo '用户:' . $id . '内容:' . $content;
    }

    public function article($user_id)
    {
        $user = new User($user_id);
        $r = $user->getArticle();
    }

    public function detail($user_id)
    {
        //echo '查看用户:' . $user_id . '信息';
        $user = new User();
        $user->where('id', $user_id)->first();

        echo $user->name;
    }

    public function userList()
    {
//        echo '查看用户列表';
        $user = new User();
        $isadmin = false;
        if (!$isadmin) {
            $user->fieldView['mobile'] = '****';
        }
        $data = $user->get();
        print_r($data);
    }

    /**
     * 用户借书案例
     * @param $book
     */
    public function borrow($book)
    {
        $ub = new UserBook();
        $ub->save([
            'user_id' => 41,
            'book_id' => 'asdasdasdasd'
        ]);
    }

    /**
     * 由路由指定的id和数据库字段名来查询数据
     *
     * @param string|int $user_id 用户id
     * @param string $field 指定列名
     */
    public function userInfo($user_id, $field)
    {
        $user = new User();
        $data = $user->where('id', $user_id)->value($field);
        print_r($data);
    }
}
