<?php
/**
 * 公司用户中心的相关操作
 * @since   2018/11/30 创建
 * @author  李胜辉
 */
namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class CompanyCenter extends Base {
    /**
     * 公司用户中心获取用户信息
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

        $user_info=D('ApiIdentity as i')->join('left join api_users as u on u.id=i.userid')->field('i.id,i.realname,u.identity,u.id as uid,u.userphoto')->where(['i.type'=>$position,'i.userid'=>$userid])->find();
        if(empty($user_info)){
            return array();
        }
        return $user_info;
	}

	
}