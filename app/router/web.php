<?php

use Kinfy\Http\Router;

//Router::GET('/aaa', 'ArticleController@index');

Router::MATCH(['get'], 'user/', 'UserController@index');

Router::MATCH(['post'], 'user/', 'UserController@add');
