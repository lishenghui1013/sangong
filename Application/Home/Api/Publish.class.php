<?php
/**
 * 小程序自动获取体验账号实现
 * @since   2017/04/24 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class Publish extends Base {
	//职位列表
	public function zhiwei($param){
		$res=D('ApiR_position')->field('id,position_name')->select();
		//$count=count($res);
		array_unshift($res,array('id'=>'','position_name'=>'全部'));
		//$res[$count]['id']='';
		//$res[$count]['position_name']='全部';
		return $res;
	}
	//结算方式列表
	public function paytypes($param){
		$res=D('ApiR_paytype')->field('id,type')->select();
		array_unshift($res,array('id'=>'','type'=>'全部'));
		return $res;
	}
	//发布简历
    public function release($param) {
		$data=$param;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$len=count($param);
		//if($len!=10){
		//	Response::error(ReturnCode::EMPTY_PARAMS, '缺少参数');
		//}
		//D('ApiResume')->where(['userid'=>$data['userid']])->save(['isdefault'=>0]);
		$data['addtime']=time();
		$url = "http://apis.map.qq.com/ws/geocoder/v1/?location=".$data['lat'].",".$data['lng']."&key=D2ABZ-A5YK5-PNII5-QSOKN-6E4UK-CUF7V&get_poi=1";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);
		$data['city']=substr($resArr['result']['ad_info']['city_code'],3,6);
		$data['area']=$resArr['result']['ad_info']['adcode'];
		D('ApiResume')->add($data);
		Response::debug($data);
        return '发布成功';
    }
    //发布招聘
    public function fabu($param) {
		$data=$param;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$len=count($param);
		if($len!=14){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少参数');
		}
		D('ApiRecruitment')->where(['userid'=>$data['userid']])->save(['isdefault'=>0]);
		$data['addtime']=time();
		$url = "http://apis.map.qq.com/ws/geocoder/v1/?location=".$data['lat'].",".$data['lng']."&key=D2ABZ-A5YK5-PNII5-QSOKN-6E4UK-CUF7V&get_poi=1";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);
		$data['city']=substr($resArr['result']['ad_info']['city_code'],3,6);
		$data['area']=$resArr['result']['ad_info']['adcode'];
		Response::debug($data);
		D('ApiRecruitment')->add($data);
        return '发布成功';
    }
	//验证是否发布过招聘
	public function checkfabu($param){
		$data=$param;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$res=D('ApiRecruitment')->where(['userid'=>$data['userid'],'dataflag'=>1])->count();
		return array('check'=>$res);
	}
	//获取省市区
	public function area($param){
		$pid=$param['pid']?$param['pid']:0;
		$list=D('ApiAreas')->field('code,region')->where(['pid'=>$pid])->select();		
		$list[0]['code']='';
		$list[0]['region']='全部';
		return $list;
	}
}