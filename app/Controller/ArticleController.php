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
//        $db->sql = [
//            'sql' => 'select article.id as aid,article.title,users.id as userid,users.username
//                      from article join users on article.user_id=users.id limit 2 ',
//            'value' => []
//        ];
//        $data = $db->get();
//        print_r($data);
//        echo '<br>';


//        $data = $db->table('article')
//            ->select('id', 'title')
//            ->where('title', 'like', '文章1%')
//            ->get();
//        $data = $db->table('article')
//            ->join('users', 'article.user_id=users.id')
//            ->select('article.id as aid', 'article.title', 'users.id as usersid', 'users.username')
//            ->take(3)
//            ->get();
//        print_r($data);

        $values = ['email' => 'john@example.com', 'votes' => 0];
//        print_r(reset($values));
//        print_r($values);
        if (!is_array(reset($values))) {
            $values = [$values];
        }
//        print_r($values);
        echo '<br><br>';
        $values = ['email' => ['john@example.com', 'john@example.com'], 'votes' => 0];
//        print_r(reset($values));
//        print_r($values);
        if (!is_array(reset($values))) {
            $values = [$values];
        }
//        print_r($values);
//        $db->table('article')->batchInsert([
//            ['title' => '文章11', 'content' => '11'],
//            ['content' => '22', 'title' => '文章22'],
//            ['title' => '文章33', 'content' => '33']
//        ]);
        $db->table('article')->batchInsert([
            ['title' => '文章11', 'content' => '11','cate_id' => '5'],
            ['content' => '22', 'title' => '文章22'],
            ['title' => '文章33', 'content' => '33', 'user_id' => '1']
        ],false);
    }
}
