<?php

namespace App\Controller;

use Kinfy\DB\DB;

class ArticleController extends BaseController
{
    public function index()
    {
        $db = new DB;
//        $data = $db->table('article')
//            ->select('id', 'title')
//            ->where('cate_id', '=', '10')
//            ->where('user_id', '=', '6')
//            ->get();
////            ->first();
//        var_dump($data);

//        $db->sql = [
//            'sql' => 'select * from article where `title` like ?',
//            'value' => ['文章1%']
//        ];
        $db->sql = [
            'sql' => 'select article.id as aid,article.title,users.id as userid,users.username as 编辑人
                      from article join users on article.user_id=users.id limit 2 ',
            'value' => []
        ];
        $data = $db->get();
        print_r($data);
        echo '<br>';


//        $data = $db->table('article')
//            ->select('id', 'title')
//            ->where('title', 'like', '文章1%')
//            ->get();
        $data = $db->table('article')
            ->join('users', 'article.user_id=users.id')
            ->select('article.id as aid', 'article.title', 'users.id as uid', 'users.username as 编辑人')
            ->take(3)
            ->get();
        print_r($data);
    }
}
