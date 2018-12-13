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
use Home\Api\Common;
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
            Response::error(-1, '暂无数据');
        }
        Response::success($user_info);
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
            Response::success(array('id'=>$res));
        }else{
            Response::error(-1,'添加失败!');
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
            Response::success($res);
        }else{
            Response::error(-1,'暂无数据!');
        }
    }

    /**
     * 发布招聘信息
     * @author: 李胜辉
     * @time: 2018/12/12 11:34
     * @param:int userid 会员表id
     * @param:string title 标题
     * @param:int position_id 职位（对应职位表id）
     * @param:string phone 联系电话
     * @param:float wage 工资（/天）
     * @param:int payment_type 结算方式（1日 2周 3月 4 时）
     * @param:float lng 经度
     * @param:float lat 纬度
     * @param:string address 详细地址
     * @param:string descript 描述
     * @param:string addtime 添加时间
     * @param:string address 详细地址
     * @param:int recruitment_num 招聘人数
     */
    public function publishRecruit($param){
        $data = $param;
        $description = htmlspecialchars_decode($param['descript'], ENT_QUOTES);
        $arr_descript = json_decode($description, true);
        $str_descript = '';
        if($arr_descript) {
            foreach($arr_descript as $key=>$value){
                str_replace(';',',',$value['note']);
                $str_descript .= $value['note'].';';
            }
            unset($key,$value);
            $data['descript'] = $str_descript;
        }
        $common = new Common();
        $arr_area = $common->location(array('lng'=>$data['lng'],'lat'=>$data['lat']));
        $data['province'] = $arr_area['province'];
        $data['city'] = $arr_area['city'];
        $data['area'] = $arr_area['area'];
        $data['addtime'] = time();//添加时间
        $is_have = D('api_recruitment')->where(array('userid' => $data['userid'], 'isdefault' => 1))->getField('id');
        $data['isdefault'] = $is_have ? 2 : 1;
        $res = D('api_recruitment')->add($data);
        if($res){
            Response::success(array('id'=>$res));
        }else{
            Response::error(-1,'发布失败!');
        }
    }


	
}