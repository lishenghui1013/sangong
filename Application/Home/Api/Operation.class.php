<?php

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class Operation extends Base {
	//预约
	public function collect($param){
		$data['userid']=$param['userid']=1;
		if(!$data['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		
		$data['bid']=$param['id'];
		$data['type']=$param['type'];
		$data['addtime']=date('Y-m-d H:i:s');
		$res=D('ApiCollect')->add($data);
		if(!$res){
		     Response::error(ReturnCode::EXCEPTION, '收藏失败');	
		}
		return $res;
	}

	
	
}