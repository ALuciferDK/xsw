<?php

namespace App\Services;

use App\Models\AdminModel;
use App\Models\RoleModel;
use DB;
use Session;
class AdminServices
{
    protected $userModel;
    protected $roleModel;
    public function __construct()
    {
        $this->userModel = new AdminModel('admin');
        $this->roleModel = new AdminModel('admin_user_role');//表名是用户角色表
    }

    /*
     * 获取所有用户信息
     * */
    public function getUserAll($arr)//
    {
        $info = 'a_id';
        $info2 = 'a_name';
        $page=$arr['page']??1;
    	$limit=$arr['limit']??7;
    	$offset=ceil(($page-1)*$limit);
    	$where=$data=[];
        $arr['name']=$arr['name']??'';
        if(!empty($arr['id'])){
            $where[]=['a_id','=',$arr['id']];
            $where1[]=['a_id','=',$arr['id']];
        }
        $where[]=['a_is_super','!=','1'];//不能查超管
        $where[]=['a_name','like','%'.$arr['name'].'%'];
        $where1[]=['a_is_super','!=','1'];//不能查超管
        $where1[]=['a_email','like','%'.$arr['name'].'%'];
        $total=$this->userModel->where($where)->orWhere($where1)->count();
        $result = $this->userModel->getUserAll($info,$info2,$offset,$limit,$where,$where1);//指定查询需要两个字段值
        $i = 0;
        $n = ($offset+1);
        foreach ($result as $key=>$value)//对数据循环赋值
        {
            $data[$i] = (array)$value;
            $data[$i]['num'] = $n;
            $i++;$n++;
        } //print_r($data);die;
        $arr1['count']=$total;
        $arr1['pagecount']=ceil($total/$limit);
        $arr1['page']=$page;
        $arr1['data']=$data;
        return $arr1;
    }

    /*
     * 添加管理员
     * */
    public function insertAdmin($data,$role=3)//
    {
        unset($data['token']);//删除传递多余token
        $flag = true;//树立旗帜标注，以此作为判断标志
        $where = ['a_name'=>$data['a_name']];//组合传递参数
        $where1 = ['a_email'=>$data['a_email']];
        $result = $this->userModel->getOrWhere($where,$where1);//调用model类里的查询单条方法
        if (!empty($result)) {//如果不是空，就表示此管理员名称已存在
            return 2;
        }
        else
        {
            DB::beginTransaction();//开启事务
            try{
                $id = $this->userModel->insertId($data);//调用model类里的添加返回id方法
                if(!$id)//判断是否添加成功
                {
                    $flag = false;
                    DB::rollBack();//如果失败，事务回滚
                }
                else
                {
                    $data = ['a_id'=>$id,'role_id'=>$role];//组合条件
                    $result = $this->roleModel->insertOne($data);//给管理员添加角色，此角色为固定角色
                    if(!$result)
                    {
                        $flag = false;
                        DB::rollBack();//失败事物回滚
                    }
                    else
                    {
                        DB::commit();//成功提交
                    }
                }
            }catch (\Exception $exception){
                $flag = false;
                DB::rollBack();//执行异常回滚
            }
        }
        return $flag;//返回旗帜
    }

    /*
     * 删除单条管理员
     * */
    public function delAdmin($data)
    {
        unset($data['token']);
        $flag = true;
        $where = ['a_id'=>$data['a_id']];//组合参数
        $result = (array)$this->userModel->getOne($where);//获取当前id的数据
        if(empty($result) || $result['a_is_super'] === 1) //如果是超管|管理员不存在，不让删除
        {
            $flag = false;
        }
        else
        {
            // DB::beginTransaction();//事务开启
            try{
                $result = $this->userModel->del($where);//调用删除方法
                if(!$result)
                {
                    $flag = false;
                    // DB::rollBack();//如果失败，事务回滚
                }
                else
                {
                    $this->roleModel->del($where);//调用删除方法
                    // if(!$result)
                    // {   
                    //     $flag = false;
                    //     // DB::rollBack();//如果失败，事务回滚
                    // }
                    // else
                    // {
                    //     // DB::commit();//成功提交
                    // }
                } 
            }catch (\Exception $exception)
            {
                $flag = false;
                // DB::rollBack();//执行异常回滚
            }
        }

        return $flag;
    }

    //修改管理员信息
    public function updAdmin($data){
        $adminData = Session::get($data['token']);
        $where = ['a_id'=>$adminData['a_id']];
        $result = $this->userModel->getOne($where);
        if($adminData['a_id'] == $data['a_id'] && $result->a_is_super == 1)
        {
            return -1;
        }
        else
        {
            if($adminData['a_id'] != $data['a_id'] && $result->a_is_super != 1)
            {
                return -1;
            }
        }
        unset($data['token']);
        $where = ['a_id'=>$data['a_id']];//执行条件
            
            if(!empty($data['a_is_freeze'])){
                $data1= ['a_is_freeze'=>$data['a_is_freeze']];
                $result = $this->userModel->upd($data1,$where);//调用方法
            }
            if(!empty($data['a_password'])){
                $data1 = ['a_password'=>$data['a_password']];
                $result = $this->userModel->upd($data1,$where);//调用方法
            }

            $result = $this->userModel->upd($data1,$where);//调用方法
            if($result)
            {
                return false;
            }
            else
            {
                return true;
            }
        
    }
}
