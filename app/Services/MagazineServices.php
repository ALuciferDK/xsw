<?php
namespace App\Services;

use App\Models\MagazineModel;
use DB;
use App\Http\Controllers\AddfilesController;

class MagazineServices
{
	/**
     * 文章管理
     * 20190306
     * sll
     * 
     * 文章状态：草稿，已发布(已审核)，待审核，禁用
     */
	protected $titlemodel;//期刊标题表
	protected $imgmodel;//期刊图片表

    protected $arr;//数据

    public function __construct($arr='')
    {
        
        $this->arr=$arr;//数据
    	$this->titlemodel = new MagazineModel('pre_periodical_title');//期刊
    	$this->imgmodel = new MagazineModel('pre_periodical_content');
        $this->wordtitle = new MagazineModel('article_title');//文章
        $this->wordcon = new MagazineModel('article_content');
        $this->addfiles = new AddfilesController();
        
    }

    public $file_dir='files/';
    //文章附件-大文件切片上传
    /**
     * file_name 附件名称
       index 切片序号
       aid 文章id
     */
    public function add_file_qie(){

        $dir=$this->file_dir;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
         $name=explode('.',$_POST["name"]);
         if($name['1']!='mp4'&&$name['1']!='mov'){
            return ['msg'=>'文件格式错误'];
         }
        $target = $dir .iconv("utf-8","gbk",$_POST["name"]) . '-' . $_POST['index']; //接收文件名时进行转码，防止中文乱码。
        move_uploaded_file($_FILES['file']['tmp_name'], $target);
    }
    //文章附件-合并切片文件
    /**
     * aid 文章id
       index 最后的切片序号
       file_name 附件名称
     */
    public function add_file_he(){
        
        $dir=$this->file_dir;
        $target = $dir .iconv("utf-8","gbk",$_POST["name"]);
        $dst = fopen($target, 'wb');

        for($i = 0; $i <= $_POST['index']; $i++) {
            $slice = $target . '-' . $i;
            $src = fopen($slice, 'rb');
            stream_copy_to_stream($src, $dst);
            fclose($src);//关闭文件
            unlink($slice);//删除文件
        }
        fclose($dst);
    }

    //文章附件-普通文件上传
    public function add_file(){
       $this->addfiles->addfile();
    }

    //添加文章
    /** 
    --普通用户只能更改文章状态：草稿,待审核，
    --判断文章是否已审核 已审核后不能在添加,修改

     * title 文章标题
       author 作者
       username 用户名
       uid 用户id
       catid 所属分类id
       summary 摘要
       comtent [文章正文,文章正文2]
       time 添加时间
       click2 文章状态 ---0待审核，1已审核，2审核失败，3草稿

       is_con 0标题con都添加，值不为0的话【不添加文章标题表，作为aid添加进内容表】
     */
    public function word(){

        DB::beginTransaction();//开启事务

        if(empty($this->arr['is_con'])){
            $arr=array(
                'title'=>$this->arr['year'],
                'author'=>$this->arr['title'],
                'username'=>$this->arr['username'],
                'uid'=>$this->arr['uid'],
                'catid'=>$this->arr['catid'],
                'summary'=>$this->arr['summary'],
                'time'=>date('Y-m-d H:i:s'),
                'click2'=>$this->arr['click2']??0,
            );
            $aid=$this->wordtitle->insertId($arr);
        }else{
            $aid=$this->arr['is_con'];
        }
        $flag=1;
        foreach($this->content as $k=>$v){
            if(!$this->wordtitle->insertOne(['content'=>$v,'aid'=>$aid])){
                $flag1=0;
            };
        }
        if($aid&&$flag){
            DB::commit();//提交事务 
            return true;
        }else{
           DB::rollBack();//回滚事务
           return false;
        }
        
    }


    /**添加期刊标题
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
	/**添加期刊图片
     *
     * t_id         杂志标题表id
     * img          图片链接
     * i_title      图片标题
     */
	public function addimg(){
        
        //得到文件对象
        $base64_image_content = $this->arr['file'];
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
                 // var_dump($arr);die;
                if(!$this->imgmodel->insertOne($arr)){
                    return false;
                };
            }

        }
        
        return true;

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