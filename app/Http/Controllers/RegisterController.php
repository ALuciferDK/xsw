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
use Illuminate\Support\Facades\Mail;
use Cache;
use App\Services\AdminServices;
use Illuminate\Support\Facades\Validator;//验证

class RegisterController extends Controller
{
    //返回参数值
    protected $code = '200'; //结果编码
    protected $message = 'success'; //结果说明
    protected $content = []; //返回数据，json
    protected $email = '';
    protected function returninfo(){
        $arr = array(
            'code' => $this->code, //结果编码
            'message' => $this->message, //结果说明
            'content'=>$this->content, //数据
        );
        //返回
        return(json_encode($arr ,true));
    }

    /*
     * 获取验证码方法
     * */
    public function captcha()
    {
        $captcha['url'] = captcha_src();
        return $this->responseData($captcha);
    }
    /*
     * 对比验证码方法
     * */
    public function captchaValidate(Request $request)
    {
        $rules = ['captcha' => 'required|captcha'];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()){
            $this->code='400';
            $this->message='error';
            return $this->returninfo();
        } else {
            $num = substr(rand(100000,99999),0,3);
            $array = array_merge(range('a','b'),range('A','B'),range('0','9'));
            shuffle($array);
            $array = array_flip($array);
            $array = array_rand($array,4);
            $str = implode($array,'');
            $token = md5($num.$str);
            Cache::put($token,$token,10);
            $this->content = $token;
            return $this->returninfo();
        }
    }
    /*
     * 发送邮箱验证码方法
     * */
    public function SendEmail(Request $request)
    {   

        $data = $request->input();
        if(empty($data['email']) || empty($data['token']))
        {
            $this->code='400';
            $this->message='参数错误';
            return $this->returninfo();
        }
        $token = Cache::get($data['token']);
        if($token != $data['token'])
        {
            $this->code='400';
            $this->message='token错误';
            return $this->returninfo();
        }
        // 验证邮箱唯一性
        $rules = [
            'a_email' => 'unique:admin'
        ];
        $messages = [
            'a_email.unique'=>'该邮箱已经存在',
        ];
        $validator = Validator::make(['a_email'=>$data['email']], $rules, $messages);
        // 如果验证出错误，提示错误
        if ($validator->fails()) {
            $this->code='400';
            $this->message='该邮箱已经存在';
            return $this->returninfo();
        }

        $this->email = $data['email'];
        //随机生成的验证码
        $array = array_merge(range('a','b'),range('A','B'),range('0','9'));
        shuffle($array);
        $array = array_flip($array);
        $array = array_rand($array,4);
        $validate = '';
        foreach ($array as $v){
            $validate .= $v;
        }
        Cache::put($data['email'],$validate,'5');
        Mail::raw('您的激活密码是'.$validate.',请您在3分钟内输入验证，过期无效。',function ($message){
            $message->to($this->email);   // 收件人的邮箱地址
            $message->subject('学术网账号激活邮件');    // 邮件主题
        });
    }
    /*
     * 注册方法，传递整体数据过来。然后后台进行验证
     * a_name,a_email,a_password,
     * */
    public function register(Request $request)
    {
        $data = $request->input();
        $result = Cache::get($data['email']);
        if(empty($result))
        {
            $this->code='400';
            $this->message='邮箱验证码过期';
            return $this->returninfo();
        }
        else if($result != $data['key'])
        {
            $this->cod1585808204e='400';
            $this->message='邮箱验证码错误';
            return $this->returninfo();
        }
        unset($data['key']);
        $data = $this->data;//获取传入的数据
        $adminServices = new AdminServices();//实例化services
        $result = $adminServices->insertAdmin($data);//调用管理员添加
        if($result === true)
        {
            return $this->returninfo();
        }
        else if($result === 2)
        {
            
            $this->code='400';
            $this->message='用户名或邮箱已存在';
             return $this->returninfo();
        }
        else
        {
            $this->code='400';
            $this->message='注册失败,请重试';
             return $this->returninfo();

        }
    }
     /*
     * 找回密码
     * 发送邮箱验证码方法
     * */
    public function SendEmail_pass(Request $request)
    {
        $data = $request->input();
        if(empty($data['email']) || empty($data['token']))
        {
            $this->code='400';
            $this->message='参数错误';
            return $this->returninfo();
        }
        $token = Cache::get($data['token']);
        if($token != $data['token'])
        {
            $this->code='400';
            $this->message='token错误';
            return $this->returninfo();
        }
        $this->email = $data['email'];
        //随机生成的验证码
        $array = array_merge(range('a','b'),range('A','B'),range('0','9'));
        shuffle($array);
        $array = array_flip($array);
        $array = array_rand($array,4);
        $validate = '';
        foreach ($array as $v){
            $validate .= $v;
        }
        Cache::put($data['email'],$validate,'5');
        Mail::raw('您的验证密码是'.$validate.',请您在3分钟内输入验证，过期无效。',function ($message){
            $message->to($this->email);   // 收件人的邮箱地址
            $message->subject('学术网账号找回密码邮件');    // 邮件主题
        });
    }

}
