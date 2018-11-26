<?php

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class Personcenter extends Base {
	//身份切换
	public function switchidentity($param){
		$userid=$param['userid'];
		$identity=$param['identity'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		/* switch($identity){
			case 1:$data['identity']=2;break;
			case 2:$data['identity']=1;break;
			default:$data['identity']=1;
		} */
		$data['identity']=$identity;
		$res=D('ApiUsers')->where(array('userid' => $userid))->save($data);
		//S('position_'.$userid,$data['identity']);
		return array('msg'=>'切换成功');
	}
	//收藏
	public function collect($param){
		$data['userid']=$param['userid'];
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		if(!$param['id']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少id');
		}
		if(!$param['type']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少type');
		}
		$check=D('ApiCollect')
		                     ->where(['bid'=>$param['id'],'userid'=>$data['userid'],'type'=>$param['type']])
							 ->count();
		if($check>0){
			D('ApiCollect')
		                     ->where(['bid'=>$param['id'],'userid'=>$data['userid'],'type'=>$param['type']])
							 ->delete();
			return array('collect'=>0);
		}
		$data['bid']=$param['id'];
		$data['type']=$param['type'];
		$data['addtime']=date('Y-m-d H:i:s');
		$res=D('ApiCollect')->add($data);
		if(!$res){
		     Response::error(ReturnCode::EXCEPTION, '收藏失败');	
		}
		return array('collect'=>1);
	}
	//个人报名
	public function signup($param){
		$data['userid']=$param['userid'];
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		if(!$param['reid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少reid');
		}
		//是否有默认简历
		//$where['end_date']=array('egt',date('Y-m-d h:i:s'));
		$where['userid']=$data['userid'];
		//$where['isdefault']=1;
		$where['dataflag']=1;
		$res=D('ApiResume')->where($where)->find();
		if(empty($res)){//没有简历
			return array('jianli'=>0);
		}
			
		$jisnliid=$param['jianliid'];
		$check=D('ApiSignup')
		                     ->where(['r_id'=>$jisnliid,'re_id'=>$param['reid'],'userid'=>$data['userid']])
							 ->count();
		if($check>0){//已报名
			return array('jianli'=>1,'baoming'=>2);
		}
		$data['r_id']=$jisnliid;
		$data['re_id']=$param['reid'];//职位id
		$data['addtime']=date('Y-m-d H:i:s');
		$res1=D('ApiSignup')->add($data);
		if(!$res1){
		     Response::error(ReturnCode::EXCEPTION, '报名失败');	
		}
		return array('jianli'=>1,'baoming'=>1);
	}
	//企业预约
	public function appointment($param){
		$data['userid']=$param['userid'];
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		if(!$param['rid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少rid');
		}
		//是否发布招聘信息
		//是否有默认简历
		$where['end_date']=array('egt',date('Y-m-d h:i:s'));
		$where['userid']=$data['userid'];
		//$where['isdefault']=1;
		$where['dataflag']=1;
		$res=D('ApiRecruitment')->where($where)->find();
		if(empty($res)){//没有招聘
			return array('jianli'=>0);
		}
		$zhaopinid=$param['yuyueid'];
		$check=D('ApiAppointment')
		                     ->where(['re_id'=>$zhaopinid,'r_id'=>$param['rid'],'userid'=>$data['userid']])
							 ->count();
		if($check>0){//已经预约该简历
			return array('jianli'=>1,'baoming'=>2);
		}
		$data['re_id']=$zhaopinid;
		$data['r_id']=$param['rid'];//简历id
		$data['addtime']=date('Y-m-d H:i:s');
		$res1=D('ApiAppointment')->add($data);
		if(!$res1){
		     Response::error(ReturnCode::EXCEPTION, '预约失败');	
		}
		//预约成功
		return array('jianli'=>1,'baoming'=>1);
	}
	//我的收藏
	public function mycollect($param){
		$userid=$param['userid'];
		/* $page=$param['page']?$param['page']:1;
		$pagesize=10; */
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$type=$param['type'];
		if(!$type){//1简历  2招聘
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少type');
		}
		$where=array();
		//$where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
		$where['r.dataflag']=1;
		//$where['r.isdefault']=1;
		$where['a.userid']=$userid;
		$where['a.type']=$type;
		if($type==1){
			
			$list=D('ApiCollect')->alias('a')
			->join('api_resume as r on a.bid=r.rid')
			->join('api_areas as c on r.area=c.code')
			->join("api_r_paytype as p on r.payment_type=p.id")
			->where($where)
			->field('r.rid as id,r.title,r.payment_type,p.type,r.end_date,r.wage,c.region')
			//->page($page,$pagesize)
			->select();
		}
		if($type==2){
          $where['r.end_date']=array('egt',date('Y-m-d h:i:s'));
			$list=D('ApiCollect')->alias('a')
			->join('api_recruitment as r on a.bid=r.id')
			->join('api_areas as c on r.area=c.code  ')
			->join("api_r_paytype as p on r.payment_type=p.id")
			->where($where)
			->field('r.id,r.title,r.payment_type,p.type,r.end_date,r.wage,c.region')
			//->page($page,$pagesize)
			->select();
		}
		
		return $list;
	}
	//我的发布
	public function myfabu($param){
		$userid=$param['userid'];
		$page=$param['page']?$param['page']:1;
		$pagesize=10;
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$type=$param['type'];
		if(!$type){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少type');
		}
     
		if($type==1){//简历
			$list=D('ApiResume')->alias('r')
			->join('api_areas as c on r.area=c.code  ')
			->join("api_r_paytype as p on r.payment_type=p.id")
			->where(['r.userid'=>$userid])
			->field('r.rid,r.title,r.payment_type,p.type,r.end_date,r.wage,r.isdefault as status,c.region')
			//->order('r.isdefault desc')
			//->page($page,$pagesize)
			->select();
		}
		if($type==2){//职位
			$list=D('ApiRecruitment')->alias('r')->join('api_areas as c on r.area=c.code  ')
			->join("api_r_paytype as p on r.payment_type=p.id")
			->where(['r.userid'=>$userid])->field('r.id,r.title,r.payment_type,p.type,r.end_date,r.wage,r.isdefault as status,c.region')
			//->page($page,$pagesize)
			->select();
          foreach($list as &$val){
			if(strtotime($val['end_date'])<=time()){//已过期
				$val['guoqi']=1;
			}else{
				$val['guoqi']=0;
			}			
		}
		}
      // Response::debug($list);
		//foreach($list as &$val){
		//	if(strtotime($val['end_date'])<=time()){//已过期
			//	$val['guoqi']=1;
			//}else{
			//	$val['guoqi']=0;
			//}			
		//}
      
		return $list;
	}
	//预约列表
	public function appointmentlist($param){
		$userid=$param['userid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$type=$param['position'];
		if(!$type){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
		}
		if($type==1){//个人
			$list=D('ApiAppointment')->alias('a')
			->join('api_users as u on a.userid=u.userid')//预约人头像
			->join('api_resume rs on a.r_id=rs.rid')//被预约的简历
			->join('api_identity as i on a.userid=i.userid')//预约人身份
			->join('api_recruitment as r on a.re_id=r.id')//预约人职位
			->where(['rs.userid'=>$userid,'i.type'=>2])
			->field('a.addtime as end_date,u.userphoto,i.realname,r.phone,r.area,r.id')
              ->group('r.id')
              ->select();
		}
		if($type==2){//企业
			$list=D('ApiAppointment')->alias('a')->join('api_resume r on a.r_id=r.rid')
			->join('api_r_paytype as p on r.payment_type=p.id')
			->where(['a.userid'=>$userid])
			->field('r.end_date,r.title,r.area,r.wage,r.payment_type,p.type,r.rid')
              ->group('r.rid')
			->select();
		}
		foreach($list as &$val){
			$val['area']=implode('',getParentNames($val['area']));
		}
		return $list;
	}
	//报名列表
	public function signuplist($param){
		$userid=$param['userid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$type=$param['position'];
		if(!$type){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
		}
		if($type==1){//个人
			$list=D('ApiSignup')->alias('a')
			->join('api_recruitment as r on a.re_id=r.id')
			->join('api_r_paytype as p on r.payment_type=p.id')
			->where(['a.userid'=>$userid])
			->field('r.end_date,r.title,r.area,r.wage,r.payment_type,p.type,r.id')
            ->group('r.id')
              ->select();
		}
		if($type==2){//企业
			$list=D('ApiSignup')->alias('a')->join('api_recruitment as re on a.re_id=re.id')
			->join('api_users as u on a.userid=u.userid')
			->join('api_resume as r on a.r_id=r.rid')
			->join('api_identity as i on a.userid=i.userid')
			//->where(['re.userid'=>$userid,'i.type'=>1,'r.dataflag'=>1,'r.isdefault'=>1])
			->where(['re.userid'=>$userid,'i.type'=>1,'r.dataflag'=>1])
			->field('a.addtime as end_date,i.realname as title,r.area,r.rid,u.userphoto,i.tel')
              ->group('r.rid')
			->select();
		}
		foreach($list as &$val){
			$val['area']=implode('',getParentNames($val['area']));
		}
		return $list;
	}
	//设置默认简历
	public function isdefault($param){
		$userid=$param['userid'];
		$id=$param['rid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		D('ApiResume')->where(['userid'=>$param['userid']])->save(['isdefault'=>0]);
		D('ApiResume')->where(['userid'=>$param['userid'],'rid'=>$id])->save(['isdefault'=>1]);
		return array('msg'=>'设置成功');
	}
	//删除简历
	public function delresume($param){
		$userid=$param['userid'];
		$id=$param['rid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		//$res=D('ApiResume')->where(['userid'=>$param['userid'],'rid'=>$id,'isdefault'=>1])->count();
		//Response::debug($res);
		D('ApiResume')->where(['userid'=>$param['userid'],'rid'=>$id])->delete();
		/* if($res>0){
			$id=D('ApiResume')->where(['userid'=>$param['userid']])->order('rid desc')->find();
			D('ApiResume')->where(['userid'=>$param['userid'],'rid'=>$id['rid']])->save(['isdefault'=>1]);
		} */
		
		return array('msg'=>'删除简历');
	}
	//设置默认招聘
	public function isdefault1($param){
		$userid=$param['userid'];
		$id=$param['rid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		D('ApiRecruitment')->where(['userid'=>$param['userid']])->save(['isdefault'=>0]);
		D('ApiRecruitment')->where(['userid'=>$param['userid'],'id'=>$id])->save(['isdefault'=>1]);
		return array('msg'=>'设置成功');
	}
	//删除招聘
	public function delresume1($param){
		$userid=$param['userid'];
		$id=$param['rid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		//$res=D('ApiRecruitment')->where(['userid'=>$param['userid'],'id'=>$id,'isdefault'=>1])->count();
		//Response::debug($res);
		D('ApiRecruitment')->where(['userid'=>$param['userid'],'id'=>$id])->delete();
		/* if($res>0){
			$id=D('ApiRecruitment')->where(['userid'=>$param['userid']])->order('id desc')->find();
			D('ApiRecruitment')->where(['userid'=>$param['userid'],'id'=>$id['id']])->save(['isdefault'=>1]);
		} */
		
		return array('msg'=>'删除简历');
	}
	//个人认证
	public function person($param){
		$data=$param;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$check=D('ApiIdentity')->where(array('userid'=>$data['userid'],'type'=>1))->find();
		if(!empty($check)){
			Response::error(ReturnCode::DATA_EXISTS, '已认证');
		}
		$data['type']=1;
		$data['addtime']=date("Y-m-d h:i:s");
		D('ApiIdentity')->add($data);
		return array('msg'=>'认证成功');
	}
	//公司认证
	public function company($param){
		$data=$param;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$check=D('ApiIdentity')->where(array('userid'=>$data['userid'],'type'=>2))->find();
		 if(!empty($check)){
			Response::error(ReturnCode::DATA_EXISTS, '已认证');
		} 
		Response::debug($param);        
		$data['number']= $param['num'];
		$data['type']=2;
		$data['addtime']=date("Y-m-d h:i:s");
		D('ApiIdentity')->add($data);
		return array('msg'=>'认证成功');
	}
	//图片上传
    public function upload($param){
		if (!empty($_FILES)) {
			//Response::debug($_FILES);
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize   =     3145728 ;    // 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath  =     THINK_PATH;          // 设置附件上传根目录
            $upload->savePath  =     '../Public/';    // 设置附件上传（子）目录
            $upload->subName   =     'uploads/company/';  //子文件夹
            //$upload->saveName  =     date('Ymdhis');     //文件名
            $upload->saveName  =     uniqid;     //文件名
            $upload->replace   =     true;  //同名文件是否覆盖
            // 上传文件
            $images   =   $upload->upload();
            //return $images;
            //判断是否有图
			$img=array();
            if($images){
				foreach($images as $file){
				   $info.= '/Public/uploads/company/'.$file['savename'].'|';
				  	
				}
				 return $info;
				//Response::debug($info);
            }
            else{
                $a=$upload->getError();//获取失败信息
                return $a;
            }
        }
        else
        {
            return array('state' => "fail");
        }
    }
	/*
     * 保存base64文件
     * $img    string    base64类型的文件
     * $type   string    保存的文件类型
     *      app_user_head_img   用户头像
     *
     *
     */
    public function saveImg_base64($img = null , $type = null)
    {
        //获取保存图片配置
        $imgConfig_savePath = C("img_save.save_path");
        $imgConfig_size     = 3145728;
        $saveFlag = false;
//        dump($imgConfig_savePath[$type]);
//        dump($imgConfig_size);

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result) && $imgConfig_savePath[$type])
        {

            $img_ext                = $result[2]; //图片后缀
            $img_header             = $result[1];//图片头信息
            $new_file_name          = date('Ymd').'/'.uniqid().'.'.$img_ext;
            $origin_img_path        = '';//原图的保存路径
            $origin_img_save_flag   = true;//
            foreach($imgConfig_savePath[$type] as $k => $v)
            {

                if(!is_dir($v.date('Ymd')))
                {
                    mkdir($v.date('Ymd'),0777,true);
                }

                if ($k == 'origin')
                {
                    //先保存一份原图,然后其他尺寸的保存直接调用原图路径origin_img_path.
                    $origin_res = file_put_contents($v.$new_file_name, base64_decode(str_replace($img_header, '', $img)));
                    if (!$origin_res)
                    {
                        $origin_img_save_flag = false;
                        break;
                    }
                    else
                    {
                        $saveFlag = $new_file_name;
                        $origin_img_path = $v.$new_file_name;
                        $this->THINK_IMAGE->open($origin_img_path);
                    }
                }
                else
                {
                    if ($origin_img_save_flag)
                    {
                        $width = $imgConfig_size[$type][$k]['w'];
                        $height = $imgConfig_size[$type][$k]['h'];
                        $this->THINK_IMAGE->thumb($width, $height,3)->save($v.$new_file_name);
                    }

                }
            }

        }
        return $saveFlag;
    }
	//个人中心获取个人姓名
	public function getusername($param){
		$position=$param['position'];
	    $userid=$param['userid'];
		if(!$userid){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		if(!$position){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
		}
		
		$username=D('ApiIdentity')->field('realname')->where(['type'=>$position,'userid'=>$userid])->find();
		if(empty($username)){
			return array();
		}
		return $username;
	}
	//关于我们
	public function about($param){
		$str=$param['str'];
		$about=D('ApiAbout')->field('content')->find();
		return $about;
	}
	//意见反馈
	public function yijian($param){
		$data['userid']=$param['userid'];
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$data['content']=$param['content'];
		$data['addtime']=date('Y-m-d h:i:s');
		$res=D('ApiYijian')->add($data);
		return '提交成功';
	}
	
	
}