<?php
namespace App\Services;

use App\Models\MagazineModel;
use DB;
use App\Http\Controllers\AddfilesController;

class MagazineServices
{
	/**
     *  click2 文章状态 ---0待审核，1已审核，2审核失败，3草稿
     */
	protected $titlemodel;//期刊标题表
	protected $imgmodel;//期刊图片表
    protected $wordtitle;//文章标题
    protected $wordcon;//文章内容
    protected $wordfile;//文章附件
    protected $arr;//数据

    public function __construct($arr='')
    {
        
        $this->arr=$arr;//数据
    	$this->titlemodel = new MagazineModel('pre_periodical_title');//期刊
    	$this->imgmodel = new MagazineModel('pre_periodical_content');
        $this->wordtitle = new MagazineModel('article_title');//文章标题
        $this->wordcon = new MagazineModel('article_content');//文章内容
        $this->wordfile = new MagazineModel('article_file');//文章附件
        //$this->addfiles = new AddfilesController();
        
    }

    //修改文章分类
    /**
       catid  分类id
       catname 分类名
       upid 父级
       state 分类状态 0正常，1禁用
       username 操作人
       uid 操作人id
     */
    public function upd_type(){

        $res=$this->wordtitle->upd(['catid'=>$this->arr['catid']],$this->arr);
        if(empty($res)){
            return ['修改分类失败'];
        }
        return true;
    }
    /**添加文章分类
       name 分类名
       p_id 父级
       state 分类状态 0正常，1禁用
       username 操作人
       uid 操作人id
     */
    public function add_type(){
        if(!$this->wordfile->insertOne($this->arr)){
            return ['添加分类失败'];
        };
        return true;
    }

    //修改文章状态
    /**
     * 普通用户只能更改文章状态：草稿,待审核
       state 文章状态 0待审核，1已审核，2审核失败，3草稿
       aid 文章id
     */
    public function upd_word(){

        $res=$this->wordtitle->upd(['click2'=>$this->arr['state']],['aid'=>$this->arr['aid']]);
        if(empty($res)){
            return ['文章状态修改失败'];
        }
        return true;
    }

    //文章附件-大文件切片上传
    /**
     * file_name 附件名称
       index 切片序号
       aid 文章id
       file 上传的文件
       --------------
        [file] => Array
        (
            [name] => QQ浏览器截图20181228163652.png
            [type] => image/png
            [tmp_name] => C:\WINDOWS\php34F9.tmp
            [error] => 0
            [size] => 207926
        )
     */
    public function add_file_qie(){

        $res=(array)$this->wordtitle->getOne(['aid'=>$this->arr['aid']]);
        if(empty($res)||$res['click2']!=3){
            return ['当前文章状态不能进行修改'];
        }
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if(empty($ext) ||$ext!='mp4' ||$ext!='mov'){
            return ['文件格式错误'];
        }

        $dir='files/video/'.date('Ymd').'/'.$this->arr['aid'].'/'.iconv("utf-8","gbk",$this->arr['file_name']).'/';
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $url = $dir .iconv("utf-8","gbk",$this->arr['file_name']) . '-' . $this->arr['index']; //接收文件名时进行转码，防止中文乱码。
        $res=move_uploaded_file($_FILES['file']['tmp_name'], $url);
        if(empty($res)){
            return ['上传失败'];
        }
        return $url;
    }
    //文章附件-合并切片文件
    /**
     * aid 文章id
       index 最后的切片序号
       file_name 附件名称
     */
    public function add_file_he(){

        $dir='files/video/'.date('Ymd').'/'.$this->arr['aid'].'/'.iconv("utf-8","gbk",$this->arr['file_name']).'/';
       
        $url = $dir .iconv("utf-8","gbk",$this->arr['file_name']);
        $dst = fopen($url, 'wb');
        for($i = 0; $i <=  $this->arr['index']; $i++) {
            $slice = $url . '-' . $i;
            $src = fopen($slice, 'rb');
            stream_copy_to_stream($src, $dst);
            fclose($src);//关闭文件
            unlink($slice);//删除文件
        }
        fclose($dst);
        $arr=array(
            'aid'=>$this->arr['aid'],
            'url'=>$url,
            'file_name'=>$this->arr['file_name'],
        );
         // var_dump($arr);die;
        if(!$this->wordfile->insertOne($arr)){
            return ['添加附件失败'];
        };
        return $url;
    }

    //文章附件-普通文件上传
    /**
     * file 上传的文件
       aid 文章id
       file_name 文件名称
     */
    public function add_file(){

       if(empty($this->arr['aid'])){
         return ['参数不对'];
       }
        //判断文件后缀
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if(empty($ext) ||$ext!='mp4' ||$ext!='mov'){
            return ['文件格式错误'];
        }
        //新建文件夹
        $dir='files/file/'.date('Ymd').'/'.$this->arr['aid'].'/';
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $url = $dir .'file'.time().$this->arr['aid']; 
        $res=move_uploaded_file($_FILES['file']['tmp_name'], $url);
        if(empty($res)){
            return ['上传失败'];
        }
        $arr=array(
                    'aid'=>$this->arr['aid'],
                    'url'=>$url,
                    'file_name'=>'',
                );
         // var_dump($arr);die;
        if(!$this->wordfile->insertOne($arr)){
            return ['添加附件失败'];
        };
        return $url;
    }

    //添加文章
    /** 
    --判断文章是否草稿 草稿才能添加,修改
    --普通用户只能更改文章状态：草稿,待审核，

     * title 文章标题
       author 作者
       username 用户名
       uid 用户id
       catid 所属分类id
       summary 摘要
       time 添加时间
       click2 文章状态 ---0待审核，1已审核，2审核失败，3草稿
        
        修改 aid 文章id
     */
    public function word_title(){

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
        if(empty($this->arr['aid'])){
            $res= $this->wordtitle->insertId($arr);
        }else{
            $res=$this->wordtitle->upd($arr,['aid'=>$this->arr['aid']]);
        }
        if(!$res){
             return ['添加|修改文章标题失败'];
        }
        return true;
    }
    //文章内容添加
    /**
    --判断文章是否已审核 已审核后不能在添加,修改
    
     * content [文章正文,文章正文2]
       aid 文章标题id

       修改 cid
     */
    public function word_con(){

       if(empty($this->arr['aid']) || empty($this->arr['content']) ){
         return ['参数不能为空'];
       }
        DB::beginTransaction();//开启事务
          $flag=1;
        foreach($this->arr['content'] as $k=>$v){
            if(empty($this->arr['cid'])){
                if(!$this->wordcon->insertOne(['content'=>$v,'aid'=>$aid])){
                    $flag1=0;
                };
            }else{
                if(!$this->wordtitle->upd(['content'=>$v],['cid'=>$this->arr['cid']])){
                    $flag1=0;
                };
            }
            
        }
        if($aid&&$flag){
            DB::commit();//提交事务 
            return true;
        }else{
           DB::rollBack();//回滚事务
           return ['添加文章内容失败'];
        }
    }


    /**添加期刊标题
     * 
     * year 年份
     * title 标题期号
     * is_flag 0待发布，1已发布
     */
	public function add_title(){
        $arr=array(
                'year'=>$this->arr['year'],
                'title'=>$this->arr['title'],
                'is_flag'=>$this->arr['is_flag']??0,
            );
		return $this->titlemodel->insertId($arr);
	}
	/**添加期刊图片
     *
     * id         期刊标题表id
       file         上传的图片
     */
	public function add_img(){
       
       if(empty($this->arr['id'])){
         return ['参数不对'];
       }

       //判断文件后缀
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if(empty($ext) || ($ext!='png' &&$ext!='jpg' &&$ext!='jpeg')){
            return ['文件格式错误'];
        }
        //新建文件夹
        $dir='files/img/'.date('Ymd').'/'.$this->arr['id'].'/';
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $url = $dir .'img'.time().$this->arr['id']; //接收文件名时进行转码，防止中文乱码。
        $res=move_uploaded_file($_FILES['file']['tmp_name'], $url);
        if(empty($res)){
            return ['上传失败'];
        }
       
         $arr=array(
                    'id'=>$this->arr['id'],
                    'url'=>$url,
                    'time'=>date('Y-m-d H:i:s'),
                );
         // var_dump($arr);die;
        if(!$this->imgmodel->insertOne($arr)){
            return ['期刊图片添加失败'];
        };
        return $url;

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