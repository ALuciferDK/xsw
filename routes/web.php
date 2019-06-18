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

Route::get('captcha','RegisterController@captcha');//获取验证码方法
Route::any('validate','RegisterController@captchaValidate');//对比验证码方法
Route::any('email','RegisterController@SendEmail');//注册发送邮件
Route::any('email/get','RegisterController@emailGetData');
Route::any('SendEmail_pass','RegisterController@SendEmail_pass');//找回密码发送邮件
Route::any('sel_zan','HomeController@sel_zan');//查看用户是否对该文章点赞
Route::any('do_zan','HomeController@sel_zan');//用户对该文章点赞&取消点赞
Route::group(['middleware'=>'power'],function (){

});