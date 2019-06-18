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



	/**添加杂志
	 *
	 * if_add 0添加标题，1添加图片|采集文本，2添加编辑内容-图片文本关联表
	 */
	public function add(Request $request){
		
        $user_id=$this->selPower($request);

		$m_ser=new MagazineServices($this->data);
		$res=$m_ser->add($user_id);
		if(!$res){
			$this->errorInfo(2);
		}elseif(is_array($res)){
			$this->content = $res;
		}
		$this->errorInfo(0);
	}

	/**修改杂志
	 *
	 * if_upd 0杂志发布状态，1杂志图片编辑状态
	 * is_flag 状态：0草稿，1待发布，2已发布 --- 状态：0未编辑，1已编辑，2禁用
	 */
	public function upd(Request $request){

		$user_id=$this->selPower($request);

		$m_ser=new MagazineServices($this->data);
		if($m_ser->upd($user_id)){
			$this->errorInfo(0);
		};
		$this->errorInfo(2);
	}
	/**查看杂志 */
	public function sel(Request $request){
		
		$this->selPower($request);
		try {
			$m_ser=new MagazineServices($this->data);
			$this->content=$m_ser->sel();
			$this->errorInfo(0);
		} catch (Exception $e) {
			$this->errorInfo(5);
		}
		
	}
	//删除本地数据
	function del(Request $request){
		$this->selPower($request);
		$m_ser=new MagazineServices($this->data);
		if($m_ser->del()){
			$this->errorInfo(0);
		};
		$this->errorInfo(2);
	}

}