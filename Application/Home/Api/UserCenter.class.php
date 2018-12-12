<?php
/**
 * 用户中心的相关操作
 * @since   2018/11/30 创建
 * @author  李胜辉
 */

namespace Home\Api;

use Admin\Model\ApiAppModel;
use Home\ORG\ApiLog;
use Home\ORG\Response;
use Home\ORG\ReturnCode;
use Home\ORG\Str;

class UserCenter extends Base
{
    /**
     * 用户中心获取用户信息
     * @author: 李胜辉
     * @time: 2018/11/30 11:34
     *
     */

    public function getUserInfo($param)
    {
        $position = $param['position'];
        $userid = $param['userid'];
        if (!$userid) {
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
        }
        if (!$position) {
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
        }

        $user_info = D('ApiIdentity as i')->join('left join api_users as u on u.id=i.userid')->join('left join api_resume as r on r.userid=i.userid')->join('left join api_r_position as p on p.id=r.position_id')->field('i.id,i.realname,u.identity,u.id as uid,p.position_name,u.userphoto')->where(['i.type' => $position, 'i.userid' => $userid])->find();
        if (empty($user_info)) {
            Response::error(-1, '暂无数据');
        }
        Response::success($user_info);
    }

    /**
     * 我的相册添加照片
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function addPhotos($param)
    {
        $identity_type = D('api_identity')->where(array('id' => $param['identity_id']))->getField('type');
        if ($identity_type == 1) {
            $path['file_path'] = 'uploads/users/photos';

        } else {
            $path['file_path'] = 'uploads/company/photos';
        }
        if ($_FILES) {
            $param['pic'] = $this->upload($path);
        }
        $data = $param;
        $data['add_time'] = date('Y-m-d H:i:s', time());
        $res = D('api_users_photos')->add($data);
        if ($res) {
            Response::success(array('id' => $res));
        } else {
            Response::error(-1, '暂无数据');
        }
    }

    /**
     * 我的相册删除照片
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function deletePhotos($param)
    {
        $id = $param['id'];//图片id
        $res = D('api_users_photos')->where(array('id' => $id))->delete();
        if ($res) {
            Response::success(array('del_num' => $res));
        } else {
            Response::error(-1, '暂无数据');
        }
    }

    /**
     * 我的相册列表
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     *
     */
    public function photosList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 9;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $identity_id = $param['identity_id'] ? $param['identity_id'] : '';//用户身份id
        $where = array();
        if ($identity_id != '') {
            $where['identity_id'] = $identity_id;
        }
        $res = D('api_users_photos')->field('id,identity_id,title,pic,add_time')->where($where)->limit($start, $limit)->select();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据');
        }
    }

    /**
     * 意见反馈
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int userid 用户认证身份id
     * @param: string content 内容
     */
    public function ideaFeedback($param)
    {
        $data = $param;
        $data['addtime'] = date('Y-m-d H:i:s', time());
        $res = D('api_yijian')->add($data);
        if ($res) {
            Response::success(array('id' => $res));
        } else {
            Response::error(-1, '反馈失败');
        }
    }

    /**
     * 发布简历
     * @author: 李胜辉
     * @time: 2018/12/12 11:34
     * @param:int userid 会员表id
     * @param:string title 标题
     * @param:int position_id 职位（对应职位表id）
     * @param:string phone 联系电话
     * @param:float wage 工资（/天）
     * @param:int payment_type 结算方式（1日 2周 3月 4 时）
     * @param:float lng 经度
     * @param:float lat 纬度
     * @param:string address 详细地址
     * @param:string description 描述
     * @param:string user_name 用户姓名
     * @param:int sex 性别(1:男;0:女)
     * @param:int age 年龄
     * @param:string education 学历
     * @param:string graduate 毕业院校
     * @param:string worktime_description 工作时间
     * @param:string work_records 工作履历
     */
    public function publishResume($param)
    {
        $data = $param;
        $description = htmlspecialchars_decode($param['description'], ENT_QUOTES);
        $arr_descript = json_decode($description, true);
        $str_descript = '';
        if ($arr_descript) {
            foreach ($arr_descript as $key => $value) {
                str_replace(';', ',', $value['note']);
                $str_descript .= $value['note'] . ';';
            }
            unset($key, $value);
            $data['description'] = $str_descript;
        }
        $work_records = htmlspecialchars_decode($param['work_records'], ENT_QUOTES);
        $arr_records = json_decode($work_records, true);
        unset($data['work_records']);
        $data['addtime'] = time();//添加时间
        if ($arr_records) {
            foreach ($arr_records as $key => $value) {
                $years[] = date('Y', strtotime($value['start_time']));
            }
            unset($key, $value);
        }
        $min = intval(min($years));//最早工作时间
        $now = intval(date('Y', time()));//现在时间
        $data['worked_years'] = $now - $min;//工作经验年限
        $is_have = D('api_resume')->where(array('userid' => $data['userid'], 'isdefault' => 1))->getField('rid');
        $data['isdefault'] = $is_have ? 2 : 1;
        $res = D('api_resume')->add($data);
        if ($res) {
            $records = array();
            $all_records = array();
            if ($arr_records) {
                foreach ($arr_records as $key => $value) {
                    $records['start_time'] = $value['start_time'];
                    $records['end_time'] = $value['end_time'];
                    $records['com_name'] = $value['com_name'];
                    $records['position'] = $value['position'];
                    $records['resume_id'] = $res;
                    $records['add_time'] = date('Y-m-d H:i:s', time());
                    $all_records[] = $records;
                }
                unset($key, $value);
            }

            $insert = D('api_work_records')->addAll($all_records);
            if ($insert) {
                Response::success(array('id' => $res));
            } else {
                Response::error(-2, '工作履历添加失败!');
            }
        } else {
            Response::error(-1, '发布失败!');
        }
    }


}