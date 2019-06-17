<?php
namespace App\Services;

use App\Models\MagazineModel;
use DB;
use App\Http\Controllers\AddfilesController;

class MagazineServices
{
	/**
     * 杂志管理
     * 20190306
     * sll
     * 
     * 杂志状态：草稿，已发布，待发布，撤回
     */
	protected $titlemodel;//杂志标题表
	protected $imgmodel;//杂志图片表
    protected $caijimodel;//杂志文本表
	protected $logmodel;//杂志日志表
    protected $addfiles;//上传图片
    protected $i_c_model;//编辑内容表

    protected $arr;//数据

    public function __construct($arr='')
    {
        $this->arr=$arr;//数据
    	$this->titlemodel = new MagazineModel('magazine_title');
    	$this->imgmodel = new MagazineModel('magazine_img');
        $this->caijimodel = new MagazineModel('magazine_caiji');
        $this->i_c_model = new MagazineModel('magazine_i_c');
        $this->logmodel = new MagazineModel('magazine_log');
        // $this->addfiles = new AddfilesController();
        
    }
    /**添加杂志日志
     * 
     * u_id         用户id
     * t_id         杂志标题表id
     * id_status    id类型：0杂志内容表m_id，1杂志标题表t_id
     * log          日志内容
     * create       创建时间
     */
    public function addlog($u_id,$id=0,$log){
        $arr=array(
                'u_id'=>$u_id,
                't_id'=>$id,
                'log'=>$log,
                'create'=>date('Y-m-d H:i:s'),
            );
        return $this->logmodel->insertOne($arr);
    }

    /**查看杂志
     *
     * if_sel       0杂志列表，1杂志编辑后展示，2杂志纯图片展示，3图片编辑-杂志采集文本展示,4杂志年份
     * t_id         杂志图片编辑
     * t_id         杂志内容展示
     * is_flag      0草稿，1待发布，2已发布 --杂志列表
     * c_text       if_sel=3，模糊查询用
     */
    public function sel(){

        $arr1=[];
        if(isset($this->arr['t_id'])){
            $this->arr['t_id']=(int)$this->arr['t_id'];
        }
        
        if($this->arr['if_sel']==1){
            //--杂志编辑后展示-根据t_id查i_id,i_id到i_c表，组合i_id&c_id
            $obj = $this->imgmodel;
            if(isset($this->arr['i_id'])){
                $obj =$obj->where('magazine_img.i_id',$this->arr['i_id']);
            }
            $obj =$obj->where('magazine_img.t_id',$this->arr['t_id'])->get()->toArray();
            $obj1=$this->i_c_model
                ->leftJoin('magazine_caiji as caiji','magazine_i_c.c_id','caiji.c_id')
                ->select('magazine_i_c.i_id','caiji.c_id','caiji.c_text','caiji.c_link')
                ->whereIn('magazine_i_c.i_id',array_column($obj,'i_id'))
                ->get()->toArray();
            foreach($obj as $k=>&$v){
                $v['caiji']=[];
                foreach($obj1 as $k1=>&$v1){
                    if($v['i_id']==$v1['i_id']){
                        $v['caiji'][]=$v1;
                        unset($v1);
                    }
                }
            }
        }elseif($this->arr['if_sel']==2){
            //杂志纯图片展示
            $where=array('t_id'=>$this->arr['t_id']);
             if(isset($this->arr['i_id'])){
                $where1=array('i_id'=>$this->arr['i_id']);
                $obj=$this->imgmodel->getall($where,'','', $where1);
            }else{
                 $obj=$this->imgmodel->getall($where);
            }
           
        }elseif($this->arr['if_sel']==3){
            //图片编辑-杂志采集文本展示-模糊查询
            $where[]=array('t_id','=',$this->arr['t_id']);
            if(empty($this->arr['u_id'])){
                $where[]=['u_id','=',0]; 
            }else{
                $where[]=['u_id','!=',0]; 
            }
            $this->arr['c_text']=$this->arr['c_text']??0;
            $obj=$this->caijimodel->getall($where,'',$this->arr['c_text']);
        }elseif($this->arr['if_sel']==4){
             //杂志年份列表
            $where=['is_flag'=>$this->arr['is_flag']??2];
            $orwhere=0;
            if(isset($this->arr['is_flag'])){
                $where="is_flag={$this->arr['is_flag']}";
            }else{
                $where="is_flag=2";
            }if(isset($this->arr['is_flag2'])){
                $where.=" or is_flag={$this->arr['is_flag2']}";
            }
            $obj=(array)DB::select("select year from magazine_title where {$where} GROUP BY year order by year desc");
        }else{
            //杂志列表
            $where=['is_flag'=>$this->arr['is_flag']??2];
            $orwhere=0;
            if(isset($this->arr['is_flag2'])){
                $orwhere=['is_flag'=>$this->arr['is_flag2']];
            }
            $where1='';
            if(isset($this->arr['year'])){
                $where1=['year'=>$this->arr['year']];
            }
            $obj=$this->titlemodel->getall($where,['create','DESC'],'',$where1,$orwhere); 
        }
        return $obj;
    }

    /**修改杂志内容
     * 
     * is_flag  t_id状态：0草稿，1待发布，2已发布 --- i_id状态：0未编辑，1已编辑，2禁用
     * if_upd   0杂志标题表，1杂志图片表
     * title    修改标题，不修改为空
     * id       t_id || i_id
     * u_id     用户id
     */
    public function upd($u_id){

        try {
            
            DB::beginTransaction();//开启事务

                $arr=['is_flag'=>$this->arr['is_flag']];

                if($this->arr['if_upd']==1){
                    if(!empty($this->arr['title']))
                    $arr['i_title']=$this->arr['title'];
                    $where=['i_id'=>(int)$this->arr['id']];
                    $m=$this->imgmodel;
                    $str='img表';
                }elseif($this->arr['if_upd']==0){
                    if(!empty($this->arr['title']))
                    $arr['title']=$this->arr['title'];
                    $where=['t_id'=>(int)$this->arr['id']];
                    $m=$this->titlemodel;
                    $str='title表';
                }
                $m->upd($arr,$where);
                if(!$this->addlog($u_id,0,$str.',修改杂志状态is_flag:'.$arr['is_flag']))
                {
                     DB::rollBack();//回滚事务
                     return false;
                };
            DB::commit();//提交事务
            return true; 
        } catch (Exception $e) {
            DB::rollBack();//回滚事务
            return false;
        }
    }
    //删除本地杂志|本地数据
    public function del(){
        
        if(!empty($this->arr['c_id'])){
           $where[]=['c_id','=',$this->arr['c_id']];
            return $this->caijimodel->del($where);
        }
        if(!empty($this->arr['t_id'])){
           $where[]=['t_id','=',$this->arr['t_id']];
           return $this->titlemodel->del($where);
        }
        return false;
    }

    /**添加-总
     *
     * if_add 0添加标题，1添加图片,2采集文本，3添加编辑内容-图片文本关联表
     */
    public function add($u_id){
        try {

          DB::beginTransaction();//开启事务

            if($this->arr['if_add']==0)
                $res=$this->addtitle();
            elseif($this->arr['if_add']==1)
                $res=$this->addimg();
            elseif($this->arr['if_add']==2)
                $res=$this->addcaiji();
            elseif ($this->arr['if_add']==3)
                $res=$this->add_i_c();

              if($res){
                 DB::commit();//成功提交
               }else{
                 DB::rollBack();//失败回滚
               }
               if($this->arr['if_add']==0){
                 return array('t_id'=>$res);
               }else{
                 return $res;
               }
           
        } catch (Exception $e) {
            DB::rollBack();//失败回滚
            return false;
        }
    }
    /**添加编辑内容
     *
     * i_id     图片id
     * c_id     采集文本id
     */
    public function add_i_c(){

        $where=array('i_id'=>(int)$this->arr['i_id']);
        //覆盖之前的编辑
        $this->i_c_model->del($where);
        
        foreach ($this->arr['c_id'] as $k => $v) {
            $arr=array(
                'i_id'=>(int)$this->arr['i_id'],
                'c_id'=>(int)$v,
            );
            if(!$this->i_c_model->insertOne($arr))
            {
                return false;
            };
        }
        //改状态为已编辑
        $this->imgmodel->upd(['is_flag'=>1],['i_id'=>(int)$this->arr['i_id']]);
        return true;

    }
    /**添加杂志标题
     * 
     * year 年份
     * title 标题期号
     * is_flag 0待发布，1已发布
     */
	public function addtitle(){
        $arr=array(
                'year'=>$this->arr['year'],
                'title'=>$this->arr['title'],
                'is_flag'=>$this->arr['is_flag']??0,
            );
		return $this->titlemodel->insertId($arr);
	}
	/**添加杂志img
     *
     * t_id         杂志标题表id
     * img          图片链接
     * i_title      图片标题
     */
	public function addimg(){
        
        //得到文件对象
        $base64_image_content = $this->arr['file'];
        $addName=trim($this->arr['i_title']);
        $this->arr['t_id']=(int)$this->arr['t_id'];
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result))
        {
            $type = $result[2];
            // print_r($result);die;
            $new_file = './img/'.date('Ymd').'/';
            if(!file_exists($new_file))
            {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777,true);
            }
         
            $new_file = $new_file.'img'.time().'_'.$this->arr['t_id'].".{$type}";
            //解码图片
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content))))
            {
                 $arr=array(
                    'id'=>$this->arr['t_id'],
                    'url'=>$new_file,
                    'time'=>date('Y-m-d H:i:s'),
                );
                 //添加封面
                 $tt= (array)DB::table('magazine_title')->where('id',$this->arr['t_id'])->select('url')->first();
                if(isset($tt['url']) && empty($tt['url'])){
                    $this->titlemodel->upd(['url'=>$new_file],['t_id'=>$this->arr['t_id']]);
                 }
                 // var_dump($arr);die;
                if(!$this->imgmodel->insertOne($arr)){
                    return false;
                };
            }

        }
        
        return true;

	}
    /**添加采集内容
     *
     * t_id         杂志标题表id
     * c_text       文本内容
     * c_link       文本链接
     * p_id         父级id
     */
    public function addcaiji(){
            //'http://www.nfzz.net.cn/epaper/fb/298/node_392152.htm'

        //本地数据添加
        if(!empty($this->arr['u_id'])){
            $arr=array(
                't_id'=>$this->arr['t_id'],
                'c_text'=>$this->arr['c_text'],
                'c_link'=>$this->arr['url']??0,
                'u_id'=>$this->arr['u_id'],
                'creat'=>date('Y-m-d H:i:s'),
                'title'=>$this->arr['title']??0,
                'type'=>$this->arr['type']??0,
            );
            $s_id=$this->caijimodel->insertId($arr);
            if(empty($s_id)){return false;}
            return ['c_id'=>$s_id,'t_id'=>$arr['t_id'],'c_text'=>$arr['c_text'],'url'=>$arr['c_link']];
        }

        
        $str=$this->excurl($this->arr['url']);
        if(empty($str)){
            return false;
        }
        if(!$this->titlemodel->upd(['url_status'=>1],['t_id'=>(int)$this->arr['t_id']])){
            return array('不能重复添加');
        };
        //采集数据添加
        $regex='#<h1 class="z-m-title" >(.*)</h1>#Uis';
        $regex1='#<a data-url="(http.*)" data-title="(.*)" data-value="(.*)"></a>#Uis';
         preg_match_all($regex, $str, $arr);
         preg_match_all($regex1, $str, $arr1);
         $this->arr['t_id']=(int)$this->arr['t_id'];
         $title=trim($arr['1']['0']);
           
            // $arr2 = array_keys(array_flip($arr1['2']));
            // echo '<pre>';print_r($arr1);die;
            // foreach ($arr2 as $k => $v) {
                foreach ($arr1['2'] as $k1 => $v1) {
                    // if($v==$v1){
                         $arr=array(
                            't_id'=>$this->arr['t_id'],
                            'c_text'=>$arr1['3'][$k1],
                            'c_link'=>$arr1['1'][$k1],
                            'creat'=>date('Y-m-d H:i:s'),
                            'title'=>$title,
                            'type'=>$v1,
                        );
                        $s_id=$this->caijimodel->insertId($arr);
                        if(empty($s_id)){return false;}
                    // }
                }
            // }

            return true;
    }

     //curl请求
    public function excurl($url,$ispost='',$arr=''){

        $ch = curl_init();
        if(stripos($url,"https://")!==FALSE){
            //关闭证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        //post方式
        if(!empty($ispost)){
            $content=http_build_query($arr);//入参内容
            curl_setopt($ch, CURLOPT_POST,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$content);//所传参
        }
        $sContent = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        //返回
        return $sContent;
    }

          //添加日志- 目录名称，文件名称,判断，内容
    public function add_log($dir='logdir',$name='',$judge,$string){
        $dir='./log/'.date('Ymd').'/'.$dir.'/';//目录名
        //目录不存在则新建目录
        is_dir($dir)?'':mkdir($dir,0777,true);
        //转码 
        /*$encode = stristr(PHP_OS, 'WIN') ? 'GBK' : 'UTF-8';
          $string = iconv('UTF-8', $encode, $string);*/
        if($judge){
          file_put_contents($dir.$name.'-success.log',date('Y-m-d H:i:s').PHP_EOL.$string.PHP_EOL, FILE_APPEND);
        }else{
          file_put_contents($dir.$name.'-error.log',date('Y-m-d H:i:s').PHP_EOL.$string.PHP_EOL, FILE_APPEND);
        }
        return true;
    }
	
}