<?php
/**
 * 用户管理控制器
 * @since   2016-01-21
 * @author  zhaoxiang <zhaoxiang051405@outlook.com>
 */

namespace Admin\Controller;

class MemberController extends BaseController {

    public function index() {
     
        $this->display();
    }
	public function area(){
		$postData = I('post.');
		$type=$postData['type'];
		$pid=$postData['pid'];
		$where['pid']=$pid;
		if($type=2){
			 $info = D('ApiCity')->where($where)->select();
		}else{
			$info = D('ApiArea')->where($where)->select();
		}
        $this->ajaxReturn($info, 'json');
	}
	
     public function ajaxGetIndex() {
        $postData = I('post.');
        $start = $postData['start'] ? $postData['start'] : 0;
        $limit = $postData['length'] ? $postData['length'] : 20;
        $draw = $postData['draw'];
        $where = array();
        $getInfo = I('get.');
       if($getInfo['keyword']){
		$where['username'] = array('like', '%' . $getInfo['keyword'] . '%');
       }
        $total = D('ApiUsers')->where($where)->count();
        $info = D('ApiUsers')->where($where)->limit($start, $limit)->order('userid desc')->select();
        $data = array(
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $info
        );
        $this->ajaxReturn($data, 'json');
    }
	public function showDetail() {
            $id = I('get.id');
			
            if( $id ){
                $person = D('ApiUsers')->alias('u')
				->join("RIGHT join api_identity i on i.userid=u.userid")
				->where(array('u.userid' => $id,'type'=>1))->find();
				$company = D('ApiUsers')->alias('u')
				->join("api_identity i on i.userid=u.userid")
				->where(array('u.userid' => $id,'type'=>2))->find();
				$company['image']=array_filter(explode("|",$company['number']));
				
				//print_r($company);
                $this->assign('company', $company);
				$this->assign('person', $person);
                $this->display('add');
            }else{
                $this->redirect('add');
            }               
    }
    public function add() {
        if (IS_POST) {
            $data = I('post.');
            $has = D('ApiUser')->where(array('username' => $data['username']))->count();
            if ($has) {
                $this->ajaxError('用户名已经存在，请重设！');
            }
            $data['password'] = user_md5($data['password']);
            $data['regIp'] = get_client_ip(1);
            $data['regTime'] = time();
            $res = D('ApiUser')->add($data);
            if ($res === false) {
                $this->ajaxError('操作失败');
            } else {
                $this->ajaxSuccess('添加成功');
            }
        } else {
            $this->display();
        }
    }

    public function close() {
        $id = I('post.id');
        $res = D('ApiUsers')->where(array('userid' => $id))->save(array('dataflag' => 2));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

    public function open() {
        $id = I('post.id');
        $res = D('ApiUsers')->where(array('userid' => $id))->save(array('dataflag' => 1));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

    public function del() {
        $id = I('post.id');
        $isAdmin = isAdministrator($id);
        if ($isAdmin) {
            $this->ajaxError('超级管理员不可以被操作');
        }

        $res = D('ApiUser')->where(array('id' => $id))->delete();
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

}