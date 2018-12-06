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

        $user_info=D('ApiIdentity as i')->join('left join api_users as u on u.id=i.userid')->join('left join api_company_info as c on c.identity_id=i.id')->join('left join api_industry as in on in.id=c.industry_id')->field('i.id,i.realname,u.identity,u.id as uid,u.userphoto,in.industry_name')->where(['i.type'=>$position,'i.userid'=>$userid])->find();
        if(empty($user_info)){
            return array();
        }
        return $user_info;
	}
//简介,  行业列表
    /**
     * 添加公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int identity_id 用户认证身份id
     * @param: string intro 公司简介
     * @param: int industry_id 行业id
     * @param: string logo 公司logo
     * @param: string phone 公司电话
     * @param: string address 公司地址
     */
    public function ideaFeedback($param){
        $data = $param;
        $data['add_time']= date('Y-m-d H:i:s',time());
        $res = D('api_company_info')->add($data);
        if($res){
            return array('return_status'=>'success');
        }else{
            return array('return_status'=>'fail');
        }
    }
    /**
     * 查询公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int identity_id 用户认证身份id
     */
    public function companyDetail($param){
        $id = $param['identity_id'];//认证身份id
        $res = D('api_company_info')->where(array('identity_id'=>$id))->find();
        if($res){
            return array('return_status'=>'success');
        }else{
            return array('return_status'=>'fail');
        }
    }


	
}