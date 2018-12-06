<?php
/**
 * 用户中心的相关操作
 * @since   2018/11/30 创建
 * @author  李胜辉
 */
namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class UserCenter extends Base {
    /**
     * 用户中心获取用户信息
     * @author: 李胜辉
     * @time: 2018/11/30 11:34
     *
     */

	public function getUserInfo($param){
        $position=$param['position'];
        $userid=$param['userid'];
        if(!$userid){
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
        }
        if(!$position){
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
        }

        $user_info=D('ApiIdentity as i')->join('left join api_users as u on u.id=i.userid')->join('left join api_resume as r on r.userid=i.userid')->join('left join api_r_position as p on p.id=r.position_id')->field('i.id,i.realname,u.identity,u.id as uid,p.position_name,u.userphoto')->where(['i.type'=>$position,'i.userid'=>$userid])->find();
        if(empty($user_info)){
            return array();
        }
        return $user_info;
	}
    /**
     * 我的相册添加照片
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function addPhotos($param){
        $identity_type = D('api_identity')->where(array('id'=>$param['identity_id']))->getField('type');
        if($identity_type==1){
            $path['file_path'] = 'uploads/users/photos';

        }else{
            $path['file_path'] = 'uploads/company/photos';
        }
        if($_FILES){
            $param['pic']= $this->upload($path);
        }
        $data = $param;
        $data['add_time']= date('Y-m-d H:i:s',time());
        $res = D('api_users_photos')->add($data);
        if($res){
            return array('return_status'=>'success');
        }else{
            return array('return_status'=>'fail');
        }
    }
    /**
     * 我的相册删除照片
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function deletePhotos($param){
        $id = $param['id'];//图片id
        $res = D('api_users_photos')->where(array('id'=>$id))->delete();
        if($res){
            return array('return_status'=>'success');
        }else{
            return array('return_status'=>'fail');
        }
    }
    /**
     * 我的相册列表
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function photosList($param){
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 9;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $identity_id = $param['identity_id']?$param['identity_id']:'';//用户身份id
        $where = array();
        if($identity_id!=''){
            $where['identity_id'] = $identity_id;
        }
        $res = D('api_users_photos')->field('id,identity_id,title,pic,add_time')->where($where)->limit($start,$limit)->select();
        if ($res) {
            $res['return_status'] = 'success';//success:成功;fail:失败
            return $res;
        } else {
            $res['return_status'] = 'fail';//success:成功;fail:失败
            return $res;
        }
    }

    /**
     * 意见反馈
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int userid 用户认证身份id
     * @param: string content 内容
     */
    public function ideaFeedback($param){
        $data = $param;
        $data['addtime']= date('Y-m-d H:i:s',time());
        $res = D('api_yijian')->add($data);
        if($res){
            return array('return_status'=>'success');
        }else{
            return array('return_status'=>'fail');
        }
    }

	
}