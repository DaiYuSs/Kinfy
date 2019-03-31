<?php

use Kinfy\Http\Router;

//Router::GET('/aaa', 'ArticleController@index');

Router::MATCH(['get'], 'user/{id}/{name?}', 'UserController@index');

Router::MATCH(['post'], 'user/{id}', 'UserController@add');

Router::MATCH(['get'], 'user/{id}/content/{content}', 'UserController@content');

Router::MATCH(['delete'], 'user/{id?}', 'UserController@del');