<?php
/**
 * 公共接口
 * @since   2018/12/13 创建
 * @author  李胜辉
 */

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class Common extends Base
{
    /**
     * 根据经纬度获取省市县编号
     * @author: 李胜辉
     * @time: 2018/12/13 09:34
     *
     */

    public function location($param)
    {
        $lng = $param['lng'];//经度
        $lat = $param['lat'];//纬度
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location=" . $lat . "," . $lng . "&key=D2ABZ-A5YK5-PNII5-QSOKN-6E4UK-CUF7V&get_poi=1";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);
        $return = array();
        if (isset($resArr['status']) && $resArr['status'] == 0) {
            $return['city']= substr($resArr['result']['ad_info']['city_code'], 3, 6);//市编号
            $return['province'] = D('api_areas')->where(array('code'=>$return['city']))->getField('pid');//省编号
            $return['area'] = $resArr['result']['ad_info']['adcode'];//县区编号
            return $return;
        }else{
            Response::error(-1, '暂无数据');
        }
    }


}