<?php

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class Index extends Base {
	//首页轮播图,热门，推荐
	public function index($param){
		//$city=$param['city']?$param['city']:130100;
        $city=130100;
		$position=$param['position']?$param['position']:1;//1个人 2企业
		$lunbo=D('ApiPicture')->where(['position'=>$position,'city'=>$city])->field('photo,linkurl')->select();
		foreach($lunbo as &$val){
			$val['photo']='https://'.$_SERVER['HTTP_HOST'].__ROOT__.'/'.$val['photo'];
		}
		
        $where=array();
		$where['r.city']=$city;
		$where['r.dataflag']=1;
		//$where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
		//$where['r.isdefault']=1;
		if($position==1){
          $where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
			$where['recommend']=1;
			$recommend=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.wage,r.end_date,payment_type,a.region as area,p.type')
						   ->limit(4)
                           ->order("r.id desc")
						   ->select();
			unset($where['recommend']);
			$where['hot']=1;
			$hot=D('ApiRecruitment')->alias('r')
			               ->where($where)
						   ->field('r.id,r.title,r.wage')
						   ->limit(2)
						   ->select();
		}
		if($position==2){
			
			$where['r.recommend']=1;
			$recommend=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.wage,r.end_date,payment_type,a.region as area,p.type')
						   ->limit(4)
                           ->order("r.rid desc")
						   ->select();
			unset($where['recommend']);
			$where['r.hot']=1;
			$hot=D('ApiResume')->alias('r')
			               ->where($where)
						   ->field('r.rid,r.title,r.wage')
						   ->limit(2)
						   ->select();
		}
		//Response::debug($where);
		return array('lunbo'=>$lunbo,'recommend'=>$recommend,'hot'=>$hot);
	}
	//热门切换
	public function hot($param){
		//$city=$param['city']?$param['city']:130100;
        $city=130100;
		$position=$param['position']?$param['position']:1;//1个人 2企业
        $where=array();
		$where['r.city']=$city;
		$where['r.dataflag']=1;
		//$where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
		$where['r.hot']=1;
		//$where['r.isdefault']=1;
		if($position==1){
          $where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
			$count=D('ApiRecruitment')->alias('r')
			               ->where($where)
						   ->count();
			$start=($count<3)?0:mt_rand(0,$count-2);
			$hot=D('ApiRecruitment')->alias('r')
			               ->where($where)
						   ->field('r.id,r.title,r.wage')
						   ->limit($start,2)
						   ->select();
		}
		if($position==2){
			//$where['r.isdefault']=1;
			$count=D('ApiResume')->alias('r')
			               ->where($where)
						   ->count();
			$start=($count<3)?0:mt_rand(0,$count-2);
			$hot=D('ApiResume')->alias('r')
			               ->where($where)
						   ->field('r.rid,r.title,r.wage')
						   ->limit($start,2)
						   ->select();
		}
		return $hot;
	}
	//城市定位
	public function location($param){
		$lng=$param['lng'];
		$lat=$param['lat'];
		$url = "http://apis.map.qq.com/ws/geocoder/v1/?location=".$lat.",".$lng."&key=D2ABZ-A5YK5-PNII5-QSOKN-6E4UK-CUF7V&get_poi=1";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);
        print_r($resArr);exit;
		$city=substr($resArr['result']['ad_info']['city_code'],3,6);
		$location=$resArr['result']['pois'][0]['title'];
        return array('city'=>$city,'location'=>$location);
	}
	//简历列表
	public function jianli($param){
		//$city=$param['city']?$param['city']:130100;
        $city=130100;
		$type=$param['type'];//1全部 2附近 3日结 4最新
		$page=$param['page']?$param['page']:1;
		$offset=10;
		$distance=10000;
		$lng=$param['lng'];
		$lat=$param['lat'];
		$zhiwei=$param['zhiwei'];
		$quyu=$param['quyu'];
		$jiesuan=$param['jiesuan'];
		$where=array();
		$where['r.city']=$city;
		$where['r.dataflag']=1;
		//$where['r.isdefault']=1;
		//$where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
		$res=array();
		switch($type){
			case 1:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			$count=D('ApiResume')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
                           ->order("r.rid desc")
						   ->page($page,$offset)
						   ->select();
			break;
			case 2:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			$count=D('ApiResume')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$data=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.end_date,r.wage,r.payment_type,r.lng,r.lat,p.type,a.region as area')
                           ->order("r.rid desc")
						   ->page($page,$offset)
						   ->select();
			foreach($data as &$val){
				$dis=GetDistance($val['lng'],$val['lat'],$lng,$lat);
				if($dis<$distance){
					$res[]=$val;
				}
			}
			break;
			case 3:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			$where['payment_type']=1;
			$count=D('ApiResume')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
                           ->order("r.rid desc")
						   ->page($page,$offset)
						   ->select();
			break;
			case 4:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			//$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
			//$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
			//$where['addtime']=array(array('gt',$beginToday),array('lt',$endToday)) ;
			$count=D('ApiResume')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
                           ->order("r.rid desc")
						   ->page($page,$offset)
						   ->select();
			break;
		}
		return array('total'=>$count,'result'=>$res);
	}
	//招聘列表
	public function zhiwei($param){
		//$city=$param['city']?$param['city']:130100;
       $city=130100;
		$type=$param['type']?$param['type']:1;//1全部 2附近 3日结 4最新
		$page=$param['page']?$param['page']:1;
		$offset=10;
		$distance=10000;
		$lng=$param['lng'];
		$lat=$param['lat'];
		$zhiwei=$param['zhiwei'];
		$quyu=$param['quyu'];
		$jiesuan=$param['jiesuan'];
		$where=array();
		$where['r.city']=$city;
		$where['r.dataflag']=1;
		//$where['r.isdefault']=1;
		$where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
		$res=array();
		switch($type){
			case 1:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			$count=D('ApiRecruitment')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
                           ->order("r.id desc")
						   ->page($page,$offset)
						   ->select();
			break;
			case 2:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			$count=D('ApiRecruitment')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$data=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.end_date,r.wage,r.payment_type,r.lng,r.lat,p.type,a.region as area')
                           ->order("r.id desc")
						   ->page($page,$offset)
						   ->select();
			//foreach($data as &$value){
			//	$dis=GetDistance($value['lng'],$value['lat'],$lng,$lat);
				//Response::debug($dis);
			//	if($dis<$distance){
				//	$res[]=$value;
				//}
			//}
			$res=$data;
			break;
			case 3:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			//$where['payment_type']=1;
			$count=D('ApiRecruitment')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
                           ->order("r.id desc")
						   ->page($page,$offset)
						   ->select();
			break;
			case 4:
			if($zhiwei){
				$where['r.position_id']=$zhiwei;
			}
			if($quyu){
				$where['r.area']=$quyu;
			}
			if($jiesuan){
				$where['r.payment_type']=$jiesuan;
			}
			//$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
			//$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
			//$where['addtime']=array(array('gt',$beginToday),array('lt',$endToday)) ;
			$count=D('ApiRecruitment')->alias('r')			               
			               ->where($where)						   
						   ->count();
			$res=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
						   ->page($page,$offset)
						   ->select();
			break;
		}
		return array('total'=>$count,'result'=>$res);
	}
	//简历详情
	public function jianliinfo($param){
		$userid=$param['userid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$rid=$param['rid'];
		if(!$rid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少rid');
		}
		$where=array();
		$where['rid']=$rid;
		$res=D('ApiResume')->alias('r')
		                ->join("api_identity i on r.userid=i.userid")
						->join("api_users u on u.userid=r.userid")
						->join("api_r_paytype p on r.payment_type=p.id")
		                ->where(['r.rid'=>$rid,'i.type=1'])
						->field('r.rid as id,r.title,r.position_id,r.phone,r.wage,r.payment_type,p.type,r.end_date,r.lng,r.lat,r.address,r.description,i.realname,u.userphoto')
						->find();
       // $res['userphoto']=						
		$res['collect']=D('ApiCollect')->where(['userid'=>$userid,'type'=>1,'bid'=>$rid])->count();
		return $res;
	}
	//招聘详情
	public function zhaopininfo($param){
		$userid=$param['userid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$id=$param['id'];
		if(!$id){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少id');
		}
		$where=array();
		$res=D('ApiRecruitment')->alias('r')
		                ->join("api_identity i on r.userid=i.userid")
						->join("api_users u on u.userid=r.userid")
						->join("api_r_paytype p on r.payment_type=p.id")
		                ->where(['r.id'=>$id,'i.type=2'])
						->field('r.id,r.title,r.position_id,r.phone,r.wage,r.payment_type,p.type,r.end_date,r.job_start,r.job_end,r.worktime,r.lng,r.lat,r.address,r.descript,i.realname,u.userphoto')
						->find();
		$res['worktime']=str_replace('|','至',$res['worktime']);				
		$res['collect']=D('ApiCollect')->where(['userid'=>$userid,'type'=>2,'bid'=>$id])->count();
		return $res;
	}
	//搜索
	public function search($param){
		$city=$param['city']?$param['city']:130100;
		$position=$param['position']?$param['position']:1;//1个人 2企业
		$keyword=$param['keyword'];
		$where=array();
		$where['title|address']=['like',"%$keyword%"];
		$where['r.dataflag']=1;
        $where['city']=$city;
		//$where['r.isdefault']=1;
		if($position==1){
			$res=D('ApiRecruitment')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.id,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
						   ->select();
		}
		if($position==2){
			$res=D('ApiResume')->alias('r')
			               ->join("api_areas a on r.area=a.code")
						   ->join("api_r_paytype p on r.payment_type=p.id")
			               ->where($where)
						   ->field('r.rid,r.title,r.end_date,r.wage,r.payment_type,p.type,a.region as area')
						   ->select(); 
		}
		return $res;
	}


	
	
}