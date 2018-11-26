<?php

/**
 * 获取HTTP全部头信息
 */
if (!function_exists('apache_request_headers')) {
	function apache_request_headers(){
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = explode('_', $arh_key);
				if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val)
						$rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}

		return $arh;
	}
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @param  string $auth_key 要加密的字符串
 * @return string
 * @author jry <598821125@qq.com>
 */
function user_md5($str, $auth_key = ''){
    if(!$auth_key){
        $auth_key = C('AUTH_KEY');
    }
    return '' === $str ? '' : md5(sha1($str) . $auth_key);
}
/**
  *根据区获取省市区名字
 */ 
function getParentNames($id,$data = array()){
		$areas = D('ApiAreas')->where('code='.$id)->field('pid,region')->find();
		
		$data[] = $areas['region'];
		
		if((int)$areas['pid']==0){
			krsort($data);
			return $data;
		}else{
			return getParentNames((int)$areas['pid'], $data);
		}
	}
function GetDistance($lng1,$lat1,$lng2,$lat2){
		//将角度转为狐度
		$radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度
		$radLat2=deg2rad($lat2);
		$radLng1=deg2rad($lng1);
		$radLng2=deg2rad($lng2);
		$a=$radLat1-$radLat2;
		$b=$radLng1-$radLng2;
		$s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
		return floor($s);
    }
/**
 * @param     $url
 * @param int $timeOut
 * @return bool|mixed
 */
if (!function_exists('curlGet')) {
	function curlGet($url, $timeOut = 10){
		$oCurl = curl_init();
		if (stripos($url, "https://") !== false) {
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeOut);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if (intval($aStatus["http_code"]) == 200) {
			return $sContent;
		} else {
			return false;
		}
	}
}