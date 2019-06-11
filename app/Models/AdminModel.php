<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/17
 * Time: 15:08
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class AdminModel extends Model
{
    protected $table;

    /*
     * 构造方法获取传入的表名
     * */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /*
     * 查询表内符合条件的指定一个字段值
     * */
    public function get($where,$info)//
    {
        $data = DB::table($this->table)->where($where)->select($info)->get();
        return $data;
    }

    /*
     * 获取插入id
     * */
    public function insertId($data)//
    {
        $result = DB::table($this->table)->insertGetId($data);
        return $result;
    }

    /*
     * 删除表内指定一条数据
     * */
    public function del($data)//
    {
        $data = DB::table($this->table)->where($data)->delete();
        return $data;
    }
     /*
     * 获取表内的指定一条
     * */
    public function getOrWhere($where,$where1)//
    {
        $data = DB::table($this->table)->where($where)->orWhere($where1)->first();
        return $data;
    }

    /*
     * 获取表内的指定一条
     * */
    public function getOne($where)//
    {
        $data = DB::table($this->table)->where($where)->first();
        return $data;
    }

    /*
     * 查询表内所有两个指定值
     * */
    public function getUserAll($info,$info2,$offset=0,$limit=10,$where=0,$where1=0)//
    {
        $data = DB::table($this->table)->select($info,$info2,'a_email','a_is_super','a_is_freeze');
    	if(!empty($where)){
    		$data=$data->where($where);
    	}if(!empty($where1)){
            $data=$data->orWhere($where1);
        }
    	$data=$data->offset($offset)->limit($limit)->get();
        return $data;
    }

    /*
     * 添加一条数据
     * */
    public function insertOne($data)//
    {
        $result = DB::table($this->table)->insert($data);
        return $result;

    }

    /*
     * 修改数据
     * */
    public function upd($data,$where)
    {
        $result = DB::table($this->table)->where($where)->update($data);
        return $result;
    }
}
