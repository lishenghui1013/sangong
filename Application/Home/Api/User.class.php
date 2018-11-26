<?php
/**
 * 小程序自动获取体验账号实现
 * @since   2017/04/24 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace Home\Api;

use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class User extends Base {
	//微信授权登录
    public function index($param) {
		if(!$param['code']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少code');
		}
        $openId = $this->getOpenId($param['code']);
		//Response::debug($openId);
		if(empty($openId['openid'])){
			Response::error(ReturnCode::LOGIN_ERROR, '授权登录失败');
		}
		$res=D('ApiUsers')->where(array('openid' => $openId['openid']))->find();
		
		if(empty($res)){
			$data['session_key'] = $openId['session_key'];
			$data['addtime'] = time();
			$data['openid'] = $openId['openid'];
			$data['unionid'] = $openId['unionid']?$openId['unionid']:'';
			$newId = D('ApiUsers')->add($data);
			D('ApiAuthGroupAccess')->add(array('uid' => $newId, 'groupId' => 1));
            $position=1;
            $person=0;
            $company=0;			
		}else{
			$data['session_key'] = $openId['session_key'];			
			D('ApiUsers')->where(array('openid' => $openId['openid']))->save($data);
			$newId=$res['userid'];
			$position=$res['identity'];
			$person=D('ApiIdentity')->where(array('userid' => $res['userid'],'type'=>1))->count();
			$company=D('ApiIdentity')->where(array('userid' => $res['userid'],'type'=>2))->count();
		}
        
        return array('userid'=>$newId,'position'=>$position,'person'=>$person,'company'=>$company);
    }
    //获取微信头像
    public function getinfo($param) {
		if(!$param['userid']){
			Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
		}
		$data['username'] = $param['username'];
        $data['userphoto'] = $param['userphoto'];		
		D('ApiUsers')->where(array('userid' => $param['userid']))->save($data);
        
        return '获取成功';
    } 
    public function re($param) {
        $openId = $this->getOpenId($param);
        $pwd = Str::randString(6, 1);
        $data['password'] = user_md5($pwd);
        $old = D('ApiUser')->where(array('openId' => $openId))->find();
        D('ApiUser')->where(array('openId' => $openId))->save($data);

        return array('username' => $old['username'], 'password' => $pwd);
    }

    private function getOpenId($code) {
      	$appid='wxb9a7d2226e4b9cfe';
		$secret='cc326d5bf72c6edd6197ab963bbc1527'; 
		/* $appid='wxb361c1f1cfd59ca4';
		$secret='041d88ebdeab16ef07c6abd8a618b0ec'; 
		 $appid='wx7faa280174d0b32b';
		$secret='864a763deb5d797360d60b3901a537a1'; */
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code={$code}&grant_type=authorization_code";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);

        return $resArr;
    }
}