<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HomeModel extends Model
{
    public $data;

    //查首页
    public function index_sel($catid=[],$limit=[10,0]){
        
        $arr=DB::table('category')->where('state',0);
        if(!empty($catid)){
            $arr=$arr->whereIn("catid",$catid);
        }
        $arr2=[];
        $arr=$arr->select('catid','upid','catname')->get()->toArray();
        foreach($arr as $k=>&$v){
            $v=(array)$v;
            $arr2[]=DB::table('article_title')->where('click2',1)->whereIn("catid",[$v['catid']])->select('aid','catid','title')->orderBy('dateline','desc')->offset($limit['1'])->limit($limit['0'])->get()->toArray();
        }

        foreach($arr2 as $k=>&$v){
            foreach($v as $k1=>&$v1){
                $v1=(array)$v1;
            }
        }
        
        $arr1=$this->parent_type($arr,$p=0,$arr2);
        return $arr1;
        
    }
    //根据父级id区分
	public function parent_type($arr,$p=0,$arr2=[]){
		$arr1=[];
		foreach ($arr as $k => $v){
			$v['child']=[];
			if($v['upid']==$p){
				$v['data']=[];
				foreach($arr2 as $k1=>&$v1){
		    		foreach($v1 as $k2=>&$v2){
		    			if($v['catid']==$v2['catid']){
							$v['data'][]=$v2;
							unset($v1);
						}
		    		}
		    	}
				$v['child']=$this->parent_type($arr,$v['catid'],$arr2);
				$arr1[]=$v;
			}
		}
		return $arr1;
	}

    /*
     * 查询表内符合条件的所有值
     * */
    public function sel($where,$orwhere=0,$order=0,$like=0,$limit=0,$isall=0)
    {
        $data = DB::table($table)->where($where);
        
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
        if(!empty($limit)){
            // ['limit','offset']
           $data = $data->limit($limit['0'], $limit['1']);
        }
        if(empty($isall)){
        	$data=$data->get()->toArray();
        }else{ //查1条
        	$data=$data->first()->toArray();
        }
        
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