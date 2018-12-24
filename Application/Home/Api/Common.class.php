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
use Home\Api\SmsDemo;

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
            return $return;
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
            $info = '';
            if ($res_info) {
                foreach ($res_info as $key => $value) {
                    $info .= $value['savepath'] . $value['savename'] . ';';//拼接图片地址
                    $info = substr(trim($info), 2);
                }
                unset($key, $value);
                $info = substr(trim($info), 0, -1);
                return $info;
            } else {
                $a = $upload->getError();//获取失败信息
                Response::error(-2, $a);
            }

        } else {
            Response::error(-1, '请选择文件');
        }
    }
    /**
     * 文件上传方法
     * @author: 李胜辉
     * @time: 2018/12/24 16:34
     * @param: $files  $_FILES
     * @param: $path string 路径
     */
    public function uploads($param)
    {
        $path = $param['file_path'];
        $upload = new \Think\Upload();   // 实例化上传类
        $upload->maxSize = 314572800000;    // 设置附件上传大小
        /*$upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型*/
        $upload->rootPath = THINK_PATH;          // 设置附件上传根目录
        $upload->savePath = '../Public/uploads/';    // 设置附件上传（子）目录
        $upload->subName = $path;  //子文件夹
        $upload->replace = true;  //同名文件是否覆盖
        // 上传文件
        $return = array();
        if ($_FILES) {
            foreach ($_FILES as $key => $value) {
                $temp = array();
                $temp[$key] = $_FILES[$key];
                $res_info = $upload->upload($temp);
                if ($res_info) {
                    $info = '';
                    foreach ($res_info as $keys => $tepimg) {
                        $info .= preg_replace('/^..\//', '', $tepimg['savepath']) . $tepimg['savename'] . ';';//拼接图片地址
                    }
                    unset($keys, $tepimg);
                    $info = substr($info, 0, -1);
                    $return[$key] = $info;
                }
            }
            unset($key, $value);
        }
        return $return;
    }

    /**
     * 行业/职位列表
     * @author: 李胜辉
     * @time: 2018/12/19 11:34
     *
     */

    public function positionList()
    {
        $where = array();
        $where['dataflag'] = 1;
        $where['pid'] = 0;
        $list = D('api_r_position')->field('id,pid,position_name,addtime,dataflag')->where($where)->order('id asc')->select();
        if (empty($list)) {
            Response::error(-1, '暂无数据');
        }else{
            foreach($list as $key=>$value){
                $list[$key]['position'] = D('api_r_position')->where(array('pid'=>$value['id'],'dataflag'=>1))->field('id,pid,position_name,addtime,dataflag')->select();
            }
            unset($key,$vlaue);
            Response::success($list);
        }

    }
    /*******************************************************************************************发送手机验证码 开始*******************************************************/

    /**
     * 发送验证码
     * @author: 李胜辉
     * @time: 2018/11/06 09:34
     */
    public function sendCodes($param)
    {
        $phone = $param['phone'] ? $param['phone'] : '';//手机号
        $code = $this->buildCodes();//验证码
        $res = SmsDemo::sendSms($phone, $code);
        if ($res['Message'] == 'OK') {
            $data['code'] = $code;
            $data['phone'] = $phone;
            $data['add_time'] = time();
            $add = D('api_phone_code')->add($data);
            if($add){
                Response::success(array('code'=>$code));
            }else{
                Response::error(-2, '发生错误');
            }
        } else {
            Response::error(-1, $res['Message']);
        }
    }

    /**
     * 验证码生成
     * @author: 李胜辉
     * @time: 2018/11/06 09:34
     */
    public function buildCodes()
    {
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= mt_rand(0, 9);
        }
        return $code;

    }


    /*******************************************************************************************发送手机验证码 结束*******************************************************/

    /**
     * 随机昵称
     * @author: 李胜辉
     * @time: 2018/11/06 09:34
     */
    public function buildNickname()
    {
        $arr_rand = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'g', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $nickname = '';
        for ($i = 0; $i < 10; $i++) {
            $index = mt_rand(0, 61);
            $nickname .= $arr_rand[$index];

        }
        return $nickname;

    }

}