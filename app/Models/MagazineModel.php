<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MagazineModel extends Model
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
     * 查询表内符合条件的所有值
     * */
    public function getall($where,$order=0,$like=0,$where1=0,$orwhere=0)
    {
        $data = DB::table($this->table)->where($where);
        // 
        if(!empty($where1)){
           $data = $data->where($where1);
        }
        if(!empty($orwhere)){
           $data = $data->orWhere($orwhere);
        }
        if(!empty($like)){
           $data = $data->where('c_text', 'like', '%'.$like.'%');
        }
        if(!empty($order)){
            // ['name','asc']
           $data = $data->orderBy($order['0'], $order['1']);
        }
        $data=$data->get()->toArray();
        return $data;
    }

    /*
     * 添加数据并返回id
     * */
    public function insertId($data)
    {
        $result = DB::table($this->table)->insertGetId($data);
        return $result;
    }

    /*
     * 删除表内指定一条数据
     * */
    public function del($where)
    {
        $data = DB::table($this->table)->where($where)->delete();
        return $data;
    }

    /*
     * 获取表内的指定一条
     * */
    public function getOne($where)
    {
        $data = DB::table($this->table)->where($where)->first();
        return $data;
    }

    /*
     * 添加一条数据
     * */
    public function insertOne($data)
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