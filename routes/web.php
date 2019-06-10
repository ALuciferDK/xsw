<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::any('index_sel','HomeController@index');//首页
Route::any('navigation_sel','HomeController@title');//导航栏
Route::any('list_sel','HomeController@search');//搜索文章标题|分类catid,展示文章列表
Route::any('content_sel','HomeController@SearchContent');//查看文章
Route::any('periodical_sel','HomeController@periodical_sel');//查看期刊

Route::get('captcha','RegisterController@captcha');
Route::post('captcha/validate','RegisterController@captchaValidate');