<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\HomeModel;

class HomeController extends Controller
{
    //返回参数值
    protected $code = '200'; //结果编码
    protected $message = 'success'; //结果说明
    protected $content = []; //返回数据，json

    protected function returninfo(){
        $arr = array(
            'code' => $this->code, //结果编码
            'message' => $this->message, //结果说明
            'content'=>$this->content, //数据
        );
        //返回
        die(json_encode($arr ,true));
    }

    /**
     * 查找期刊
     * year 第几年
     * num  第几期
     */
    public function periodical_sel(Request $request){
        
        $page = $request->input('page')??1;
        $limit = $request->input('limit')??12;
        $year = $request->input('year')??0;
        $offset = ceil($page-1)*$limit;

        $count = DB::table('pre_periodical_title')->count();

        $arr = DB::table('pre_periodical_title as t')
            ->join('pre_periodical_content as c','t.id','=','c.id');
            if(!empty($year)){
                $arr =$arr->where('year',$year);
            }
           $arr =$arr->select('title','url','year')
                ->orderBy('t.id','desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

        $arr = json_encode($arr, true);
        $arr = json_decode($arr, true);
        
        $this->content['data']=$arr;
        $this->content['page_all']=ceil($count/$limit);
        $this->content['count']=$count;
        $this->returninfo();
    }

    //首页
    public function index(){
        
        //获取分类
        $m = new HomeModel;
        $arr=$m->index_sel();
        $this->content=$arr;
        $this->returninfo();
    }

    /*
     * 获取导航栏
     * */
    public function title() {

        $data = DB::table('category')->select('catid','upid','catname')->get();
        if($data) {
            $data = json_encode($data, true);
            $data = json_decode($data, true);
            $data = $this->CreateTree($data, 0);
            if($data){
                $this->content=$data;
            }else{
                $this->code='400';
                $this->message='出错了,请重新操作';
            }
        }else{   
            $this->code='400';
            $this->message='出错了,请重新操作';
        }
        return $this->returninfo();
    }
    /*
     * 获取树状数据方法，被title方法调用
     * */
    public function CreateTree($tree,$rootId = 0) {
        $return = array();

        foreach($tree as $leaf) {

            if($leaf["upid"] == $rootId) {

                foreach($tree as $subleaf) {

                    if($subleaf["upid"] == $leaf["catid"]) {

                        $leaf['children'] = $this->CreateTree($tree,$leaf["catid"]);

                    }
                }
                $return[] = $leaf;
            }
        }
        return $return;
    }
    /*
     * 通过导航栏查找文章--展示文章标题列表，
     * 通过传递catid精准查询，
     * 或者文章标题查询条件模糊匹配。
     * */
    public function search(Request $request){

        $data = $request->input();
        $page = $data['page']??1;
        $limit = $data['limit']??15;
        $offset = ceil($page-1)*$limit;
        $like = $data['title']??'';//模糊查询
        $id = $data['catid']??array();//根据分类id查
        if(!is_array($id) || !empty($id)){
            $id = explode(',',$id);
        }else if(empty($id)){
            $id = array();
        } 
        //总页数
        $count=DB::table('article_title');
        if(!empty($id)){
            $count =$count->whereIn('catid',$id);
        }
        $count =$count->where('title','like','%'.$like.'%')->count();
        //数据
        $result = DB::table('article_title');
                 if(!empty($id)){
                    $result =$result->whereIn('catid',$id);
                 }
                $result =$result->where('title','like','%'.$like.'%')
                ->orderBy('dateline','desc')
                ->offset($offset)
                ->limit($limit)
                ->select('aid','catid','title','from','author','username','dateline')
                ->get();
        if($result){
            $result = json_encode($result,true);
            $result = json_decode($result,true);
            $result['page_all']=ceil($count/$limit);
            $this->content['data']=$result;
            $this->content['page_all']=$result;
        }else{
            $this->code='400';
            $this->message='查询失败,请重新操作';
        }
        return $this->returninfo();
    }
    /*
     * 查看文章，通过传递标题id获取文章
     * */
    public function SearchContent(Request $request){

        $id = (int)$request->input('aid');
        //$page = (int)$request->input('page')??1;
        if(empty($id)){
            $this->code='400';
            $this->message='aid不能为空';
        }else{
            $result = DB::table('article_title as t')
            ->join('article_content as c','t.aid','=','c.aid')
            ->where('c.aid',$id)
            ->get();
            if($result){
                $result = json_encode($result,true);
                $result = json_decode($result,true);
                $this->content=$result;
            }else{
                $this->code='400';
                $this->message='查询失败,请重新操作';
            }
        }
        return $this->returninfo();
    }

     //用户对文章点赞&取消点赞
    /**
     * cache保存用户最近点赞
     * 判断用户对同1文章1分钟内连续点赞超过一定次数就进行限制
     */
    public function do_zan(Request $request){

        $aid = (int)$request->input('aid');
        $uid = (int)$request->input('uid');
        if(empty($aid) || empty($uid)){
            $this->code='400';
            $this->message='参数值不能为空';
        }else{
            $result = (array)DB::table('article_zan')
            ->where('uid',$uid)
            ->where('aid',$aid)
            ->first();
            DB::beginTransaction();//开启事务
            if(!empty($result)){

               $user=  DB::table('article_zan')->where('uid',$uid)->where('aid',$aid)->delete();
               $title= DB::table('article_title')->where('aid',$aid)->decrement('click1');
               $this->message='取消点赞';
            }else{

              $user=  DB::table('article_zan')->insert(['uid'=>$uid,'aid'=>$aid]);
              $title=  DB::table('article_title')->where('aid',$aid)->increment('click1');
              $this->message='点赞成功';
            }
           if($user&&$title){
               DB::commit();//提交事务 
           }else{
               DB::rollBack();//回滚事务
               $this->code=400;
               $this->message='请稍后重试';
           }
        }
        return $this->returninfo();

    }

    //查看用户是否对文章点赞
    public function sel_zan(Request $request){

        $aid = (int)$request->input('aid');
        $uid = (int)$request->input('uid');
        if(empty($aid) || empty($uid)){
            $this->code='400';
            $this->message='参数值不能为空';
        }else{
            $result = (array)DB::table('article_zan')
            ->where('uid',$uid)
            ->where('aid',$aid)
            ->first();
            if(!empty($result)){
                $this->message='该用户已点赞';
                $this->content=['zan'=>1]; 
            }else{
                $this->message='该用户未点赞';
                $this->content=['zan'=>0];
            }
        }
        return $this->returninfo();

    }


}