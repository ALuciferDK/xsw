<?php
namespace App\Http\Controllers;

use App\Http\Controllers\CommonController;
use Session;
use App\Services\MagazineServices;
use Illuminate\Http\Request;


class MagazineController extends CommonController
{	

	/**
	 *  add -杂志标题-杂志图片-杂志采集文本-杂志图片编辑后上传
	 *  upd -杂志状态-杂志图片编辑状态
	 *  sel -所有杂志列表-单期杂志的所有图片-单期杂志的采集文本-杂志图片编辑内容
	 */

	//期刊标题上传
	public function add_title(){

		$user_id=$this->selPower($request);

        $m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_title();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//期刊标题上传
	public function add_img(){

		$user_id=$this->selPower($request);
		
        $m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_img();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//文章附件-切片上传
	public function add_file_qie(){
		 $user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }
        $m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_file_qie();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//文章附件-合并切片上传
	public function add_file_he(){
		 $user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }
        $m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_file_he();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//文章附件-普通文件上传
	public function add_file(){
		 $user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }
        $m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_file();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}

	/**添加文章标题
	 */
	public function add_word_title(Request $request){
		
        $user_id=$this->selPower($request);

		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->word_title();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	/*修改文章标题
	 aid
	 */
	public function upd_word_title(Request $request){
		
		$user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }

		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->word_title();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	/*添加文章内容
	 */
	public function add_word_con(Request $request){
		
		 $user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }

		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->word_con();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	/*修改文章内容
	 cid
	 */
	public function upd_word_con(Request $request){
		
		 $user_id=$this->selPower($request);

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
        if(empty($res)||$res['click2']!=3){
        	$this->content = ['当前文章不能进行修改'];
			$this->errorInfo(2);
        }

		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->word_con();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//用户修改自己的文章状态
	/**
	 * uid 判断用户是否该文章发布人
	 */
	public function upd_word_user(){

		$res=(array)DB::table('article_title')->where([['aid'=>$this->data['aid']],['uid'=>$this->data['uid']]])->first();
		if(empty($res)||$res['click2']==1||$res['click2']==2){
        	$this->content = ['该文章不能修改'];
			$this->errorInfo(2);
        }
		if($this->data['state']!=0 &&$this->data['state']!=3){
			$this->content = ['不能修改为该状态'];
			$this->errorInfo(2);
		}
		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->upd_word();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//管理员修改文章状态
	public function upd_word_admin(){
		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->upd_word();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}

	//修改文章分类
	public function upd_type()){
		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->upd_type();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}
	//添加文章分类
	public function add_type(){
		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->add_type();
		if(is_array($res)){
			$this->content = $res;
			$this->errorInfo(2);
		}
		$this->errorInfo(0);
	}

}