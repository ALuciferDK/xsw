<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/10
 * Time: 11:02
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function captcha()
    {
        $captcha['url'] = captcha_src();
        return $this->responseData($captcha);
    }
    public function captchaValidate(Request $request)
    {
        $rules = ['captcha' => 'required|captcha'];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()){
            return $this->responseFailed('验证失败');
        } else {
            return $this->responseSuccess('验证成功');
        }
    }
}