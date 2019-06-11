<?php
namespace App\Http\Controllers;

use App\Http\Controllers\CommonController;
use Illuminate\Http\Request;
use App\Services\AdminServices;
use Session;

header('Access-Control-Allow-Origin:*');
class AdminController extends CommonController
{

    /*
     * 管理员查询方法
     * */
    public function adminShow(Request $request)
    {   
        //判断用户权限
        $this->selPower($request);
        $adminServices = new AdminServices();//实例化对应的services层
        $data = $adminServices->getUserAll($this->data);//调用获取admin列表方法
        //if(!empty($data))//获取成功
       // { 
           // array_shift($data);//删除掉超级管理员
           // $data = json_encode($data,true);//转换成json穿
            $this->code = '0';//print_r($data);die;
            $this->message = '获取数据成功-超管不予展示';
            $this->content = $data;//返回给前台数据
            $this->returninfo();
       // }
       // else//执行异常
       // {
         //   $this->errorInfo('5');
       // }
    }

    /*
     * 管理员添加方法
     * */
    public function adminAdd(Request $request)
    {
        //判断用户权限
        $this->selPower($request);
        $data = $this->data;//获取传入的数据
        $adminServices = new AdminServices();//实例化services
        $result = $adminServices->insertAdmin($data);//调用管理员添加
        if($result === true)
        {
            $this->errorInfo('0');
        }
        else if($result === 2)
        {
            $this->errorInfo('-6');
        }
        else
        {
            $this->errorInfo('2');

        }
    }

    /*
     * 管理员删除方法
     * */
    public function adminDel(Request $request)
    {
        //判断用户权限
        $this->selPower($request);
        $data = $this->data;//获取传入数据
        $adminServices = new AdminServices();//实例化services
        $result = $adminServices->delAdmin($data);//调用services里面的管理员删除方法
        if($result)
        {
            $this->errorInfo('0');
        }
        else
        {
            $this->errorInfo('5');
        }
    }

    /*
     * 管理员修改
     * */
    public function adminUpd(Request $request)
    {
        //判断用户权限
        $this->selPower($request);
        $data = $this->data;//获取传入数据
        $adminServices = new AdminServices();//实例化对应的services层
        $result = $adminServices->updAdmin($data);
        if($result === true)
        {
            $this->errorInfo('0');
        }
        else if($result == -1)
        {
            $this->errorInfo('-5');
        }
        else
        {
            $this->errorInfo('5');
        }
    }
}
