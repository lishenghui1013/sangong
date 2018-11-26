<?php
/**
 * 用户管理控制器
 * @since   2016-01-21
 * @author  zhaoxiang <zhaoxiang051405@outlook.com>
 */

namespace Admin\Controller;

class PictureController extends BaseController {

    public function index() {
     // echo _PHP_FILE_;
        $this->display();
    }
	public function area(){
		$postData = I('get.');
		$where['pid']=$postData['pid']?$postData['pid']:0;
		$info = D('ApiAreas')->where($where)->select();
        $this->ajaxReturn($info, 'json');
	}
	
     public function ajaxGetIndex() {
        $postData = I('post.');
        $start = $postData['start'] ? $postData['start'] : 0;
        $limit = $postData['length'] ? $postData['length'] : 20;
        $draw = $postData['draw'];
        $where = array();
        $getInfo = I('get.');
		if($getInfo['position']){
			$where['position']=$getInfo['position'];
		}
		if($getInfo['city']){
		$where['city']=$getInfo['city'];
		}
        $total = D('ApiPicture')->where($where)->count();
        $info = D('ApiPicture')->alias('p')->join('api_areas a on p.city=a.code')->field('p.*,a.region')->where($where)->limit($start, $limit)->select();
        $data = array(
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $info
        );
        $this->ajaxReturn($data, 'json');
    }
    public function add() {
        if (IS_POST) {
            $data = I('post.');

            $save['position'] =$data['position'];
            $save['city'] = $data['city'];
			$save['province'] = $data['province'];
			$save['photo'] = $data['photo'];
            $save['addtime'] = date('Y-m-d H:i:s');
			$save['adduser'] = $this->uid;
            $res = D('ApiPicture')->add($save);
            if ($res === false) {
                $this->ajaxError('操作失败');
            } else {
                $this->ajaxSuccess('添加成功');
            }
        } else {
            $this->display();
        }
    }
    public function edit() {
        if( IS_GET ) {
            $id = I('get.id');
            if( $id ){
                $detail = D('ApiPicture')->where(array('id' => $id))->find();
                $this->assign('detail', $detail);
                $this->display('add');
            }else{
                $this->redirect('add');
            }
        }elseif( IS_POST ) {
            $data = I('post.');
            $res = D('ApiPicture')->where(array('id' => $data['id']))->save($data);
            if( $res === false ) {
                $this->ajaxError('操作失败');
            } else {
              
                $this->ajaxSuccess('添加成功');
            }
        }
    }
    public function close() {
        $id = I('post.id');
        $isAdmin = isAdministrator($id);
        if ($isAdmin) {
            $this->ajaxError('超级管理员不可以被操作');
        }
        $res = D('ApiUser')->where(array('id' => $id))->save(array('status' => 0));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

    public function open() {
        $id = I('post.id');
        $isAdmin = isAdministrator($id);
        if ($isAdmin) {
            $this->ajaxError('超级管理员不可以被操作');
        }
        $res = D('ApiUser')->where(array('id' => $id))->save(array('status' => 1));
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }

    public function del() {
        $id = I('post.id');
        $res = D('ApiPicture')->where(array('id' => $id))->delete();
        if ($res === false) {
            $this->ajaxError('操作失败');
        } else {
            $this->ajaxSuccess('操作成功');
        }
    }
	//图片上传
    public function upload(){
        if (!empty($_FILES)) {
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize   =     3145728 ;    // 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath  =     THINK_PATH;          // 设置附件上传根目录
            $upload->savePath  =     '../Public/';    // 设置附件上传（子）目录
            $upload->subName   =     'uploads/ads';  //子文件夹
            $upload->saveName  =     date('Ymdhis');     //文件名
            $upload->replace   =     true;  //同名文件是否覆盖
            // 上传文件
            $images   =   $upload->upload();
            //return $images;
            //判断是否有图
            if($images){
                $info=substr($images['photo']['savepath'],3).$images['photo']['savename'];
                echo json_encode($info);
            }
            else{
                $a=$upload->getError();//获取失败信息
                echo json_encode($a);
            }
        }else{
            return 2;
        }
    }

}