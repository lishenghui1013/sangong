<?php
/**
 *
 * @since   2017/03/02 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace Home\Api;


class Base {

    protected $city;
	protected $identity;
    protected $userInfo;

    public function __construct() {
        $this->city = C('CITY');
		
        $this->userInfo = C('USER_INFO');
    }
    /**
     * 上传图片
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function upload($param){
        $file_path = $param['file_path']?$param['file_path']:'uploads/default';
        if (!empty($_FILES)) {
            //Response::debug($_FILES);
            $upload = new \Think\Upload();   // 实例化上传类
            $upload->maxSize   =     3145728 ;    // 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath  =     THINK_PATH;          // 设置附件上传根目录
            $upload->savePath  =     '../Public/';    // 设置附件上传（子）目录
            $upload->subName   =     $file_path;  //子文件夹
            $upload->saveName  =     uniqid;     //文件名
            $upload->replace   =     true;  //同名文件是否覆盖
            // 上传文件
            $images   =   $upload->upload();
            //判断是否有图
            $info = '';
            if($images){
                foreach($images as $file){
                    $info.= '/Public/'.$file_path.$file['savename'].';';
                }
                $info = substr($info,0,-1);
                return $info;
            }
            else{
                $a=$upload->getError();//获取失败信息
                return $a;
            }
        }
        else
        {
            return array('return_status' => "fail");
        }
    }
}