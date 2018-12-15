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

    public function getAddressNum($param)
    {
        $lng = $param['lng'];//经度
        $lat = $param['lat'];//纬度
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location=" . $lat . "," . $lng . "&key=D2ABZ-A5YK5-PNII5-QSOKN-6E4UK-CUF7V&get_poi=1";
        $res = file_get_contents($url);
        $resArr = json_decode($res, true);
        $return = array();
        if (isset($resArr['status']) && $resArr['status'] == 0) {
            $return['city'] = substr($resArr['result']['ad_info']['city_code'], 3, 6);//市编号
            $return['province'] = D('api_areas')->where(array('code' => $return['city']))->getField('pid');//省编号
            $return['area'] = $resArr['result']['ad_info']['adcode'];//县区编号
            Response::success($return);
        } else {
            Response::error(-1, '暂无数据');
        }
    }

    /**
     * 图片上传
     * @author: 李胜辉
     * @time: 2018/12/15 09:34
     * @param: string file_path 图片存储子目录名称
     */
    public function upload($param)
    {
        $file_path = $param['file_path'];//图片存储子目录名称
        if (!empty($_FILES)) {
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize = 3145728;    // 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath = THINK_PATH;          // 设置附件上传根目录
            $upload->savePath = '../Public/uploads/';    // 设置附件上传（子）目录
            $upload->subName = $file_path;  //子文件夹
            $upload->saveName = uniqid;     //文件名
            $upload->replace = true;  //同名文件是否覆盖
            // 上传文件
            $res_info = $upload->upload();
            if($res_info){
                foreach ($res_info as $key => $value) {
                    $info[$key] = $value['savepath'] . $value['savename'];//拼接图片地址
                    $info[$key] = substr(trim($info[$key]),2);
                }
                unset($key, $value);
                Response::success($info);
            }else{
                $a = $upload->getError();//获取失败信息
                Response::error(-2,$a);
            }

        } else {
            Response::error(-1,'请选择文件');
        }
    }


}