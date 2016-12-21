<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

use App\Match;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test', function () {
    $name = 'Test';
    dispatch(new \App\Jobs\QueueLogger($name));
});

//Auth::routes();
//Route::get('/home', 'HomeController@index');

Route::post('optasports','OptaController@postIndex');