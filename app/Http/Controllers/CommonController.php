<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CommonServices;
use Session;
use Illuminate\Support\Facades\DB;
use App\Http\Middleware;
use Illuminate\Support\Facades\Cache;
header('Access-Control-Allow-Origin:*');
class CommonController extends Controller
{
    //返回参数值
    protected $code = ''; //结果编码
    protected $message = ''; //结果说明
    protected $content = ''; //返回数据，json
    protected  $data = '';//获取传递数据

    /*
     * 统一访问权限判断构造函数
     * */
    public function __construct(Request $request)
    {  
      if($arr=file_get_contents("php://input")){
            //接值
            $arr=json_decode($arr,1);
            $content=json_encode($arr['content']);
      }else{
            $this->code = '2';
            $this->message = '操作异常,post请求';
            $this->returninfo();
      } 
        if(empty($arr) || !isset($arr['timestamp'])|| !isset($arr['sign'])|| !isset($content)){//如果传输的
            $this->errorInfo('-7');
        }else{
            $this->add_log('log','postlog',$arr);
        }

        //验证签名 -env('APP_KEY_COMMON') .env里面的交互秘钥
        $sign = md5($arr['timestamp'] . $content . env('APP_KEY_COMMON'));
        
        //签名错误
        if (time() - $arr['timestamp'] > 30) {
            $this->errorInfo('-3');
        }
        /*
        else if($sign != $arr['sign'])
        {
            $this->add_log('log','sing_error_log','签名错误');
            $this->code = '-2';
            $this->message = '签名不一致：'.$sign;
            $this->content = $arr;
            $this->returninfo();
        }s
        */
         //解析发送过来的数据
        $this->data = $arr['content'];
        //判断传递参数是否有误
        $this->selData($request);
        
    }

    /**
     *  邮箱，用户名，密码，状态:是否禁用
     */
    public function login ()
    {
        // var_dump($this->data);die;
        $admin = new CommonServices();
        $data = (array)DB::table('admin')->where('a_name',$this->data['a_name'])->orWhere('a_email',$this->data['a_name'])->first();//通过传递参数查询是否存在管理员
        if($data)
        {

            if($data['a_password'] != $this->data['a_password'])
            {
                $this->code = '4';
                $this->message = '密码错误';
                $this->add_log('log','login_error_log','密码错误登录失败');//登录写入日志
                $this->returninfo();
            }
            else if($data['a_is_super'] == 0 && $data['a_is_freeze'] != 0)//如果不是超管并且没被禁用，进入此判断
            {
                $this->add_log('log','login_log','管理员登录成功');//登录写入日志
                $token = md5(rand(10000,99999).time().'admin').rand(1000,9999);//管理员token算法
                $url = $admin->power($data['a_id']);//获取管理员对应权限
                $url['a_id'] = $data['a_id'];//把管理员id存入权限数组
                Session([$token=>$url]);//通过生成token存入管理员id进入session
                Session::save();
                // Cache::put('u'.$data['a_id'],$token,300);
                DB::table('admin')->where(['a_id'=>$data['a_id']])->update(['a_is_login'=>$token]);
                $this->code = '0';
                $this->message = '登陆成功';
                $this->content = ['token'=>$token,'admin'=>0,'name'=>$data['a_name'],'u_id'=>$data['a_id']];//返回给前台一个身份令牌token
                $arr = array(
                    'code' => $this->code, //结果编码
                    'message' => $this->message, //结果说明
                    'content'=>$this->content, //数据
                );
                //返回
                return(json_encode($arr, JSON_UNESCAPED_UNICODE));
            }
            else if($data['a_is_super'] == 1 && $data['a_is_freeze'] != 0)//如果是超管并且没被禁用，进入此判断
            {
                $this->add_log('log','login_log','超级管理员登录成功');//登录写入日志
                $token = md5(rand(10000,99999).time().'superAdmin');//超级管理员token算法
                $url = $admin->power($data['a_id']);//获取管理员对应权限
                $url['a_id'] = $data['a_id'];//把管理员id存入权限数组
                Session([$token=>$url]);//通过生成token存入超级管理员id进入session
                Session::save();//保存session进入session文件
                // Cache::put('u'.$data['a_id'],$token,300);
                DB::table('admin')->where(['a_id'=>$data['a_id']])->update(['a_is_login'=>$token]);
                $this->code = '0';
                $this->message = '登陆成功';
                $this->content = ['token'=>$token,'admin'=>1,'name'=>$data['a_name'],'u_id'=>$data['a_id']];//返回给前台一个身份令牌token
                 $arr = array(
                    'code' => $this->code, //结果编码
                    'message' => $this->message, //结果说明
                    'content'=>$this->content, //数据
                );
                //返回
                return(json_encode($arr, JSON_UNESCAPED_UNICODE));
            }
            else
            {
                $this->code = '6';
                $this->message = '登录失败，您的账户已被禁用';
                $this->returninfo();
            }
        }
        else{
            $this->code = '3';
            $this->message = '用户名错误';
            $this->add_log('log','login_error_log','用户名错误登录失败');//登录写入日志
            $this->returninfo();
        }

    }

    protected function returninfo()
    {
        $arr = array(
            'code' => $this->code, //结果编码
            'message' => $this->message, //结果说明
            'content'=>$this->content, //数据
        );
        //返回
        die(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    public function errorInfo($num)//错误信息返回方法
    {
        switch ($num)
        {
            case '0':   $this->code = $num;$this->message = '操作成功';break;
            case '2':   $this->code = $num;$this->message = '操作失败';break;
            case '3':   $this->code = $num;$this->message = '用户名错误';break;
            case '4':   $this->code = $num;$this->message = '密码错误';break;
            case '5':   $this->code = $num;$this->message = '执行异常';break;
            case '6':   $this->code = $num;$this->message = '账户被禁用';break;
            case '-2':  $this->code = $num;$this->message = '签名不一致';break;
            case '-3':  $this->code = $num;$this->message = '访问超时';break;
            case '-4':  $this->code = $num;$this->message = '身份令牌失效';break;
            case '-5':  $this->code = $num;$this->message = '权限不足';break;
            case '-6':  $this->code = $num;$this->message = '用户或邮箱已存在';break;
            case '-7':  $this->code = $num;$this->message = '参数不正确';break;
            case '-8':  $this->code = $num;$this->message = '用户已在其他地方登陆';break;
        }
        $this->returninfo();
    }

    //添加日志- 目录名称，文件名称,内容
    public function add_log($dir='logdir',$name='',$string){
        $dir='./log/'.date('Ymd').'/'.$dir.'/';//目录名
        //目录不存在则新建目录
        is_dir($dir)?'':mkdir($dir,0777,true);
        //转码
        /*$encode = stristr(PHP_OS, 'WIN') ? 'GBK' : 'UTF-8';
          $string = iconv('UTF-8', $encode, $string);*/
        file_put_contents($dir.$name.'.log',
            date('Y-m-d H:i:s').PHP_EOL.print_r($string,1).PHP_EOL,
            FILE_APPEND);
        return true;
    }

    public function is_freeze($id)//查看管理员是否被禁方法
    {
        $CommonServices = new CommonServices;
        $data = $CommonServices->is_freeze($id);
        if($data['a_is_freeze'] === 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    //判断用户权限
    public function selPower($request){

        $routeName = $request->route()->uri();//获取当前访问的uri
        if($routeName=='magazine/login'){
            return '';//登录不判断权限
        }
        $data = $this->data;//获取用户传递的数据
        $token = $data['token'];//获取数据里面的token
        $uri = Session($token);//通过token取出登录时存入的权限
        if(empty($uri))//如果取出uri为空，表示token失效或者未登录
        {   
            $this->errorInfo('-4');//无权限返回
        }else{
            
            $id = $uri['a_id'];//获取uri里面存入的用户id
            $a_is_login=(array)DB::table('admin')->where(['a_id'=>$id])->where(['a_is_login'=>$token])->first();
            if(empty($a_is_login)){
                $this->errorInfo('-8');
            }
            
        }

        $result = $this->is_freeze($id);//通过id查询用户是否被禁用
        if(!$result)
        {
           $this->errorInfo('6');
        }
        
        $result = in_array($routeName,$uri);//比对是否拥有对当前uri的访问权限
        if($result)
        {   
            return $id;
        }
        else//无权限返回
        {
            $this->errorInfo('-5');
        }
        
    }

    //判断传递参数是否有误
    public function selData($request){

        $routeName = $request->route()->uri();//获取当前访问的uri
        $data = $this->data;//获取用户传递的数据

         switch ($routeName)
        {
            case 'magazine/login':
                if(!isset($data['a_name'])|| !isset($data['a_password'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'admin/show':
                 if(!isset($data['token'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'admin/add':
                 if(!isset($data['token']) ||!isset($data['a_email']) ||!isset($data['a_name'])|| !isset($data['a_password']) ||!isset($data['a_is_freeze'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'admin/del':
                 if(!isset($data['token'])|| !isset($data['a_id'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'admin/upd':
                 if(!isset($data['token'])|| !isset($data['a_id'])){
                    $this->errorInfo('-7');
                }elseif(!isset($data['a_is_freeze']) && !isset($data['a_password'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'magazine/add':
                 if(!isset($data['token']) || !isset($data['if_add'])){
                    $this->errorInfo('-7');
                }elseif($data['if_add']==0 && (!isset($data['year']) || !isset($data['title']))){
                    $this->errorInfo('-7');
                }elseif($data['if_add']==1 && (!isset($data['t_id']) || !isset($data['i_title'])|| !isset($data['file']))){
                    $this->errorInfo('-7');
                }elseif($data['if_add']==2 && (!isset($data['t_id']) || !isset($data['url']))){
                    $this->errorInfo('-7');
                }elseif($data['if_add']==3 && (!isset($data['i_id']) || !isset($data['c_id']))){
                    $this->errorInfo('-7');
                }
                break;
            case 'magazine/upd':
                 if(!isset($data['token']) ||!isset($data['is_flag']) ||!isset($data['if_upd']) ||!isset($data['id'])){
                    $this->errorInfo('-7');
                }
                break;
            case 'magazine/show':
                if(!isset($data['token']) || !isset($data['if_sel'])){
                    $this->errorInfo('-7');
                }elseif(!isset($data['if_sel']) && !isset($data['t_id'])){ 
                    $this->errorInfo('-7');
                }
                break;
            case 'magazine/del':
                 if(!isset($data['token']) || (!isset($data['c_id']) &&!isset($data['t_id']))){
                     $this->errorInfo('-7');
                }
                break;

        }
    }
}
/*
传参格式说明：

POST传值，JSON格式，UTF-8编码

入参参数：
timestamp ：时间戳，参与签名
sign：验证签名
key：验证签名密钥 allowPassword

签名算法：md5(timestamp+content+key)
接收参数：
格式：json
参数格式
$arr = [
'timestamp' => '1526702719',
'content' => 传输的数据,json字符串,
'sign' => '47c9ad0b30761784ea12770622935752',
];

验证完毕：
返回参数    数据    说明
code    int    结果编码
message    string    结果说明
content    json串    返回数据

附表4：
编码    说明
0    操作成功
1    URL地址错误
2    操作失败
3    用户名错误
4    密码错误
5    执行异常
6    账户被禁用
-2    签名不一致
-3   访问超时
-4   身份令牌失效
-5   权限不足

 */

