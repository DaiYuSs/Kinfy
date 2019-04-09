<?php

use Kinfy\Http\Router;

//Router::GET('/aaa', 'ArticleController@index');

Router::MATCH(['get'], 'user/{id}/{name?}', 'UserController@index');

Router::MATCH(['post'], 'user/{id}', 'UserController@add');

Router::MATCH(['get'], 'user/{id}/content/{content}', 'UserController@content');

Router::MATCH(['delete'], 'user/{id?}', 'UserController@del');

Router::ANY('/abc/{id}',function ($id){
    echo $id;
});

Router::group('v5',function (){
    Router::group('api',function (){
        Router::GET('foo',function (){
            echo 'v5/api/foo';
        });
    });

    Router::GET('user',function (){
        echo 'v5/user';
    });
});
Router::ANY('abc/{id}/{name}',function ($id,$name){
    echo $id.' '.$name.'<br>';
},['id'=>'\d+']);