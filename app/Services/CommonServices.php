<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/17
 * Time: 10:53
 */
namespace App\Services;

use App\Models\AdminModel;
use App\Models\MenuModel;
use App\Models\RoleModel;
class CommonServices
{
    public $adminModel;
    public $roleModel;
    public $menuModel;
    public $userModel;
    public function __construct()
    {
        $this->adminModel = new AdminModel('admin_user_role');
        $this->roleModel = new RoleModel('admin_role_menu');
        $this->menuModel = new MenuModel('admin_menu');
        $this->userModel = new AdminModel('admin');
    }

    public function getAdminRole($data)//获取管理员角色
    {
        $info = "role_id";
        $where = ['a_id'=>$data];
        $data = $this->adminModel->get($where,$info);
        $data = json_decode($data,true);//print_r($data);die;
        return $data;
    }

    public function getMenuID($data)//通过权限id获取权限
    {
        $info = 'menu_id';
        $where = $data;
        $num = 'role_id';
        $data = $this->roleModel->get($where,$info,$num);
        $data = json_decode($data,true);//print_r($data);die;
        return $data;
    }


    public function getMenu($data)//获取权限
    {
        $where = $data;
        $info = 'menu_id';
        $data = $this->menuModel->getWhere($where,$info);
        $data = json_decode($data,true);//print_r($data);die;
        return $data;
    }

    public function power($data)//最终获取权限方法
    {
        //$homeService = new HomeService();//实例化service
        $role_id = $this->getAdminRole($data);//通过登陆的管理员ID获取一个二维的管理员所拥有的角色ID

        $role_id = array_column($role_id,'role_id');//把得到的二维数组转换成一维数组

        $resource_id = $this->getMenuID($role_id);//通过角色id获取菜单id

        $menu_id = array_column($resource_id,'menu_id');//把得到的二维数组转换成一维数组

        $menu = $this->getMenu($menu_id);//通过菜单ID获取菜单表数据

        $uri = array_column($menu,'url');//分离出需要的uri

        return  $uri;//返回uri
    }

    public function is_freeze($data)//查看管理员是否被禁用方法
    {
        $where = [['a_id','=',$data],['a_is_freeze','=','1']];
        $result = (array)$this->userModel->getOne($where);
        return $result;
    }
}