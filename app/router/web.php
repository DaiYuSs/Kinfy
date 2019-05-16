<?php

use Kinfy\Http\Router;

Router::rule('id', '\d+');
//Router::rule('name', '\d+');

//Router::MATCH(['get'], 'user/{id}/{name?}', 'UserController@index');

//Router::MATCH(['post'], 'user/{id}', 'UserController@add');

//Router::MATCH(['get'], 'user/{id}/content/{content}', 'UserController@content');

//Router::MATCH(['delete'], 'user/{id?}', 'UserController@del');

//Router::ANY('/abc/{id}', function ($id) {
//    echo $id;
//});

//Router::group('v5', function () {
//    Router::group('api', function () {
//        Router::GET('foo', function () {
//            echo 'v5/api/foo';
//        });
//    });
//
//    Router::GET('user', function () {
//        echo 'v5/user';
//    });
//});

//Router::ANY('abc/{id}/{name}', function ($id, $name) {
//    echo $id . ' ' . $name . '<br>';
//});

//Router::MATCH(['get'], 'user/{id?}/name', function () {
//    echo '特殊案例';
//});

//Router::GET('user@{id}@project_list', function () {
//    echo '任意分割符路由测试';
//});

//Router::GET('article', 'ArticleController@index');

Router::GET(
    '/user/{uid}/article',
    'UserController@article',
    ['uid' => '\d+']
);

Router::GET(
    '/user/add/{name}',
    'UserController@add'
);

Router::GET(
    '/book/add',
    'BookController@add'
);

Router::GET(
    '/user/list',
    'UserController@userList'
);
