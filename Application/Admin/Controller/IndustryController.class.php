<?php
/**
 * 行业/职位管理控制器
 * @since   2018/12/19 08:47
 * @author  李胜辉
 */

namespace Admin\Controller;

class IndustryController extends BaseController {
    /**
     * 行业/职位列表
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
    public function index() {
        $this->display();
    }
    /**
     * 行业列表
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
	public function industryList(){
		$list = D('api_r_position')->where(array('pid'=>0))->select();
		return $list;
	}
    /**
     * 行业/职位列表
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
     public function ajaxGetIndex() {
        $postData = I('post.');
        $start = $postData['start'] ? $postData['start'] : 0;//当前页
        $limit = $postData['length'] ? $postData['length'] : 10;//每页显示条数
        $draw = $postData['draw'];
        $where = array();
        $getInfo = I('get.keyword');
        if($getInfo){
            $where['r.position_name'] = array('like', '%' . $getInfo . '%');
        }
        $total = D('api_r_position')->alias('r')->where($where)->count();
        $info = D('api_r_position')->alias('r')
		            ->where($where)
					->field("r.*")
                    ->order('pid')
                    ->limit($start, $limit)
					->select();

         $info = formatTree(listToTree($info,'id','pid'),0,'position_name');
        if($info){
            foreach($info as $key=>$value){
                $info[$key]['sort'] = $key+1;
            }
            unset($key,$value);
            $data = array(
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $total,
                'data'            => $info
            );
            $this->ajaxReturn($data, 'json');
        }else{
            $this->ajaxError('暂无数据');
        }

    }
    /**
     * 添加行业/职位
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
    public function add() {
        if (IS_POST) {
            $data = I('post.');
            $data['addtime'] = date('Y-m-d H:i:s',time());
            $res = D('api_r_position')->add($data);
            if ($res === false) {
                $this->ajaxError('操作失败');
            } else {
                $this->ajaxSuccess('添加成功');
            }
        } else {
            $industryList = $this->industryList();
            $this->assign('industryList',$industryList);
            $this->display();
        }
    }
    /**
     * 编辑行业/职位
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
    public function edit() {
        if (IS_POST) {
            $data = I('post.');
            $res = D('api_r_position')->save($data);
            if ($res === false) {
                $this->ajaxError('操作失败');
            } else {
                $this->ajaxSuccess('添加成功');
            }
        } else {
            $id = I('get.id');
            $detail = D('api_r_position')->where(array('id'=>$id))->find();
            $industryList = $this->industryList();
            $this->assign('industryList',$industryList);
            $this->assign('detail',$detail);
            $this->display('add');
        }
    }

    /**
     * 隐藏行业/职位
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
    public function close() {
        $id = I('post.id');
        $res = D('api_r_position')->where(array('id' => $id))->save(array('dataflag' => 2));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }
    /**
     * 显示行业/职位
     * @author: 李胜辉
     * @time: 2018/12/19 09:34
     *
     */
    public function open() {
        $id = I('post.id');
        $res = D('api_r_position')->where(array('id' => $id))->save(array('dataflag' => 1));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

}