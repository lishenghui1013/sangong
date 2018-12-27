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
use Home\Api\Common;
use Home\Api\User;
use Home\Api\WXBizDataCrypt;
use Think\Model;

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
        $userid = $param['userid'];//用户id
        if (!$userid) {
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少参数');
        }
        $user_info = D('ApiIdentity as i')->join('left join api_users as u on u.userid=i.userid')->field('i.id,u.username,i.is_deposit,i.userid,u.identity,u.userphoto')->where(['i.type' => 1, 'i.userid' => $userid])->find();
        $user_info['position_name'] = D('api_resume as r')->join('left join api_r_position as p on p.id=r.position_id')->where(array('r.userid'=>$userid,'r.isdefault'=>1))->getField('p.position_name');
        if (empty($user_info)) {
            Response::error(-1, '暂无数据');
        }
        Response::success($user_info);
    }

    /**
     * 我的相册添加照片(暂不考虑)
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
     * 我的相册删除照片(暂不考虑)
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
     * 我的相册列表(暂不考虑)
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
     * 意见反馈(原来就有,写多了)
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
            $data['description'] = substr($str_descript, 0, -1);
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
        $common = new Common();
        $arr_area = $common->getAddressNum(array('lng' => $data['lng'], 'lat' => $data['lat']));
        $data['province'] = $arr_area['province'];
        $data['city'] = $arr_area['city'];
        $data['area'] = $arr_area['area'];
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

    /**
     * 求职信息列表
     * @author: 李胜辉
     * @time: 2018/12/13 10:34
     */
    public function resumeList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $position_id = $param['position_id'] ? $param['position_id'] : '';//职位id
        $area = $param['area'] ? $param['area'] : '';//区县编码
        $sort = $param['sort'] ? $param['sort'] : '';//排序(1:日薪从高到低;2:月薪从高到低;3:距离;4:发布时间)
        $city_lat = $param['lat'] ? $param['lat'] : '';//纬度
        $city_lng = $param['lng'] ? $param['lng'] : '';//经度
        $city = $param['city'] ? '' : '';//城市编号
        $where = array();
        $where ['r.status'] = 1;
        $where ['r.dataflag'] = 1;
        if ($city != '') {
            $where['city'] = $city;
        }
        if ($position_id != '') {
            $where['r.position_id'] = $position_id;
        }
        if ($area != '') {
            $where['r.area'] = $area;
        }
        $str_sort = 'i.is_deposit,r.worked_years desc';
        if ($sort != '') {
            switch ($sort) {
                case '1'://日薪从高到低
                    $str_sort = 'r.wage desc';
                    $where['r.payment_type'] = array('lt', 2);

                    break;
                case '2'://月薪从高到低
                    $str_sort = 'r.wage desc';
                    $where['r.payment_type'] = array('gt', 1);
                    break;
                case '3'://距离
                    $str_sort = 'ACOS(SIN((' . $city_lat . ' * 3.1415) / 180 ) *SIN((r.lat * 3.1415) / 180 ) +COS((' . $city_lat . ' * 3.1415) / 180 ) * COS((r.lat * 3.1415) / 180 ) *COS((' . $city_lng . ' * 3.1415) / 180 - (r.lng * 3.1415) / 180 ) ) * 6380  asc';
                    break;
                case '4'://发布时间
                    $str_sort = 'r.addtime desc';
                    break;
                default :
                    $str_sort = 'i.is_deposit,r.worked_years';
                    break;
            }
        }
        $list = D('api_resume as r')->join('left join api_areas as a on a.code=r.area')->join('left join api_identity as i on i.userid=r.userid')->field('r.rid as id,r.title,r.wage,r.payment_type,r.worked_years,r.sex,r.age,r.education,r.addtime,a.region,i.is_deposit')->where($where)->limit($start, $limit)->order($str_sort)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                if ($value['is_deposit'] == '1') {
                    $list[$key]['approve'] = 'Y';//是否认证
                } else {
                    $list[$key]['approve'] = 'N';
                }
                //发布时间
                $list[$key]['addtime'] = date('Y-m-d', $value['addtime']);
                //日结/月结
                switch ($value['payment_type']) {
                    case '0':
                    case '1':
                        $list[$key]['payment'] = '日结';//日结
                        break;
                    case '2':
                    case '3':
                        $list[$key]['payment'] = '月结';//月结
                        break;
                    default :
                        $list[$key]['payment'] = '未知';//未知
                        break;
                }

            }
            unset($key, $value);
            Response::success($list);
        } else {
            Response::error(-1, '暂无数据');
        }

    }

    /**
     * 日结求职信息列表
     * @author: 李胜辉
     * @time: 2018/12/13 10:34
     */
    public function dayResumeList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $position_id = $param['position_id'] ? $param['position_id'] : '';//职位id
        $area = $param['area'] ? $param['area'] : '';//区县编码
        $sort = $param['sort'] ? $param['sort'] : '';//排序(1:日薪从高到低;2:月薪从高到低;3:距离;4:发布时间)
        $city_lat = $param['lat'] ? $param['lat'] : '';//纬度
        $city_lng = $param['lng'] ? $param['lng'] : '';//经度
        $city = $param['city'] ? '' : '';//城市编号
        $where = array();
        $where ['r.status'] = 1;
        $where ['r.dataflag'] = 1;
        $where['r.payment_type'] = array('lt', 2);
        if ($city != '') {
            $where['city'] = $city;
        }
        if ($position_id != '') {
            $where['r.position_id'] = $position_id;
        }
        if ($area != '') {
            $where['r.area'] = $area;
        }
        $str_sort = 'i.is_deposit,r.worked_years desc';
        if ($sort != '') {
            switch ($sort) {
                case '1'://日薪从高到低
                    $str_sort = 'r.wage desc';
                    break;
                case '2'://距离
                    $str_sort = 'ACOS(SIN((' . $city_lat . ' * 3.1415) / 180 ) *SIN((r.lat * 3.1415) / 180 ) +COS((' . $city_lat . ' * 3.1415) / 180 ) * COS((r.lat * 3.1415) / 180 ) *COS((' . $city_lng . ' * 3.1415) / 180 - (r.lng * 3.1415) / 180 ) ) * 6380  asc';
                    break;
                case '3'://发布时间
                    $str_sort = 'r.addtime desc';
                    break;
                default :
                    $str_sort = 'i.is_deposit,r.worked_years';
                    break;
            }
        }
        $list = D('api_resume as r')->join('left join api_areas as a on a.code=r.area')->join('left join api_identity as i on i.userid=r.userid')->field('r.rid as id,r.title,r.wage,r.payment_type,r.worked_years,r.sex,r.age,r.education,r.addtime,a.region,i.is_deposit')->where($where)->limit($start, $limit)->order($str_sort)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                if ($value['is_deposit'] == '1') {
                    $list[$key]['approve'] = 'Y';//是否认证
                } else {
                    $list[$key]['approve'] = 'N';
                }
                //发布时间
                $list[$key]['addtime'] = date('Y-m-d', $value['addtime']);
                //日结/月结
                switch ($value['payment_type']) {
                    case '0':
                    case '1':
                        $list[$key]['payment'] = '日结';//日结
                        break;
                    case '2':
                    case '3':
                        $list[$key]['payment'] = '月结';//月结
                        break;
                    default :
                        $list[$key]['payment'] = '未知';//未知
                        break;
                }

            }
            unset($key, $value);
            Response::success($list);
        } else {
            Response::error(-1, '暂无数据');
        }

    }

    /**
     * 简历详情页
     * @author: 李胜辉
     * @time: 2018/11/30 11:34
     *
     */

    public function resumeDetail($param)
    {
        $id = $param['id'];//简历id
        if (!$id) {
            Response::error(-2, '缺少参数');
        }
        $resume_info = D('api_resume as r')->join('left join api_users as u on u.userid=r.userid')->join('left join api_r_position as p on p.id=r.position_id')->field('r.rid as id,r.userid,r.user_name,r.age,r.sex,r.education,p.position_name,p.pid,r.title,r.phone,r.wage,r.worktime_description,r.payment_type,r.worked_years,r.graduate,r.read_num,r.description,r.addtime,r.address,u.userphoto')->where(['r.rid' => $id])->find();

        if (empty($resume_info)) {
            Response::error(-1, '暂无数据');
        }
        $read_num = $resume_info['read_num'] + 1;
        D('api_resume')->where(array('rid' => $resume_info['id']))->save(array('read_num' => $read_num));
        $work_records = D('api_work_records')->where(array('resume_id' => $resume_info['id']))->select();
        $resume_info['work_records'] = $work_records;
        $description = explode(';', $resume_info['description']);
        $resume_info['description'] = $description;
        $resume_info['addtime'] = date('Y-m-d', $resume_info['addtime']);
        $resume_info['industry'] = D('api_r_position')->where(array('id' => $resume_info['pid']))->getField('position_name');//行业名称
        Response::success($resume_info);
    }
    /*********************************************************************登录注册 开始*******************************************************/


    /**
     * 获取微信手机号(没用)
     * @author: 李胜辉
     * @time: 2018/12/17 11:34
     *
     */
    public function getWachetTel($param)
    {
        $encryptedData = $param['encryptedData'];//加密数据
        $iv = $param['iv'];//iv
        $appid = 'wxb9a7d2226e4b9cfe';//appid
        //$sessionKey = session('session_key');
        $sessionKey = $param['session_key'];//session_key
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            Response::success($data);
        } else {
            Response::error(-1, $errCode);
        }
    }

    /**
     * 注册
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function register($param)
    {
        $data['phone'] = $param['phone'];//手机号
        $data['identity'] = 1;//身份状态1个人 2公司
        $code = $param['code'];//用户输入的验证码
        $code_info = D('api_phone_code')->where(array('phone' => $param['phone']))->limit(0, 1)->order('id desc')->select();//系统发送的验证码
        $time = time();
        $limit_time = 60 * 5 + $code_info[0]['add_time'];
        if ($time > $limit_time) {
            Response::error(-9, '验证码已过期');
        }
        $sys_code = $code_info[0]['code'];
        $data['password'] = md5($param['password']);//密码
        $verify_password = $param['verify_password'];//确认密码
        $pcre = '/^1[3456789]\d{9}$/';
        if ($data['phone'] == '' || !preg_match_all($pcre, $data['phone'])) {
            Response::error(-2, '手机号格式不正确');
        }
        $has = D('api_users')->where(array('phone' => $data['phone']))->find();
        if ($has) {
            Response::error(-7, '手机号已被注册');
        }
        if ($code === '') {
            Response::error(-3, '验证码不能为空');
        }
        if ($code != $sys_code) {
            Response::error(-4, '验证码错误');
        }
        if ($param['password'] === '') {
            Response::error(-5, '密码不能为空');
        }
        if ($verify_password != $param['password']) {
            Response::error(-6, '两次输入密码不一致');
        }

        //生成昵称
        $common = new Common();
        $data['username'] = $common->buildNickname();//用户昵称
        $data['username'] = time();//添加时间
        $res = D('api_users')->add($data);
        if ($res) {
            $info['userid'] = $res;
            $info['type'] = 1;//身份状态1个人 2公司
            $info['addtime'] = date('Y-m-d H:i:s', time());//添加时间
            $insert = D('api_identity')->add($info);
            if ($insert) {
                D('api_phone_code')->where(array('id'=>$code_info[0]['id']))->delete();
                $return['userid'] = $res;
                $return['identity'] = 1;
                $return['password'] = md5($param['password']);
                $return['phone'] = $param['phone'];
                $return['userphoto'] = D('api_users')->where(array('userid'=>$res))->getField('userphoto');
                Response::success($return);
            } else {
                Response::error(-8, '身份注册失败');
            }
        } else {
            Response::error(-1, '注册失败');
        }
    }

    /**
     * 公司注册
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function companyRegister($param)
    {
        $data['userid'] = $param['userid'];//用户id
        $data['type'] = 2;//身份状态1个人 2公司
        $data['number'] = $param['filePath'];//图片路径
        $data['addtime'] = date('Y-m-d H:i:s', time());//添加时间
        $res = D('api_identity')->add($data);
        if ($res) {
            $update = D('api_users')->where(array(array('userid'=>$res)))->save(array('identity'=>2));
            $info = D('api_users')->where(array(array('userid'=>$res)))->find();
            Response::success($info);
        } else {
            Response::error(-1, '注册失败');
        }
    }

    /**
     * 手机号登录
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function phoneLogin($param)
    {
        $phone = $param['phone'];//手机号
        $password = md5($param['password']);//密码
        $pcre = '/^1[3456789]\d{9}$/';
        if ($phone == '' || !preg_match_all($pcre, $phone)) {
            Response::error(-1, '手机号格式不正确');
        }
        if ($password === '') {
            Response::error(-3, '请输入密码');
        }
        $res = D('api_users')->where(array('phone' => $phone, 'password' => $password))->find();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '账号或密码错误!');
        }
    }

    /**
     * 忘记密码/修改密码
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function forgetPassword($param)
    {
        $phone = $param['phone'];//手机号
        $code = $param['code'];//用户输入的验证码
        $code_info = D('api_phone_code')->where(array('phone' => $param['phone']))->limit(0, 1)->order('id desc')->select();//系统发送的验证码
        $sys_code = $code_info[0]['code'];//系统发送的验证码
        $data['password'] = md5($param['password']);//密码
        $verify_password = $param['verify_password'];//确认密码
        $pcre = '/^1[3456789]\d{9}$/';
        if ($phone == '' || !preg_match_all($pcre, $phone)) {
            Response::error(-2, '手机号格式不正确');
        }
        $has = D('api_users')->where(array('phone' => $phone))->find();
        if (!$has) {
            Response::error(-7, '手机号未注册');
        }
        if ($code === '') {
            Response::error(-3, '验证码不能为空');
        }
        if ($code != $sys_code) {
            Response::error(-4, '验证码错误');
        }
        $time = time();
        $limit_time = 60 * 5 + $code_info[0]['add_time'];
        if ($time > $limit_time) {
            Response::error(-9, '验证码已过期');
        }
        if ($param['password'] === '') {
            Response::error(-5, '密码不能为空');
        }
        if ($verify_password != $param['password']) {
            Response::error(-6, '两次输入密码不一致');
        }
        $update = D('api_users')->where(array('phone' => $phone))->save($data);
        if ($update) {
            D('api_phone_code')->where(array('id'=>$code_info[0]['id']))->delete();
            Response::success(array());
        } else {
            Response::error(-1, '修改失败');
        }

    }


    /**
     * 修改昵称
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function editUserInfo($param)
    {
        $username = $param['username'];//用户名
        $userid = $param['userid'];//用户id
        if ($userid == '') {
            Response::error(-2, '缺少参数');
        }
        $res = D('api_users')->where(array('userid' => $userid))->save(array('username' => $username));
        if ($res) {
            Response::success(array());
        } else {
            Response::error(-1, '修改失败');
        }
    }

    /**
     * 首页判断状态
     * @author: 李胜辉
     * @time: 2018/12/20 11:34
     *
     */
    public function indexLoginStatus($param)
    {
        $phone = $param['phone'];//用户手机号
        $password = $param['password'];//用户密码
        $userid = $param['userid'];//用户id
        $res = D('api_users')->where(array('userid' => $userid))->find();
        if ($res) {
            if ($phone == $res['phone'] && $password == $res['password']) {
                Response::success($res);
            } else {
                Response::error(-2, '账号或密码错误');
            }
        } else {
            Response::error(-1, '请注册');
        }
    }

    /*********************************************************************登录注册 结束*******************************************************/
    /**
     * 上传用户头像
     * @author: 李胜辉
     * @time: 2018/12/17 11:34
     *
     */

    public function uploadIcon($param)
    {
        $common = new Common();
        $res = $common->upload(array('file_path' => 'userIcon'));
        if (empty($res)) {
            Response::error(-1, '上传失败');
        } else {
            $user_id = $param['user_id'];//用户id
            $userphoto = $res;//头像url地址
            if ($user_id) {
                $update = D('api_users')->where(array('userid' => $user_id))->save(array('userphoto' => $userphoto));
                if ($update) {
                    Response::success(array());
                } else {
                    Response::error(-1, '上传失败');
                }
            } else {
                Response::error(-2, '缺少参数');
            }
        }
    }

    /**
     * 拨打企业招聘电话
     * @author: 李胜辉
     * @time: 2018/12/17 11:34
     *
     */

    public function getPhone($param)
    {
        $id = $param['id'];//招聘id
        $data['userid'] = $param['userid'] ? $param['userid'] : '';
        $data['identity'] = $param['identity'] ? $param['identity'] : '1';
        $time = time();
        $data['add_time'] = date('Y-m-d H:i:s', $time);
        $limit_num = 5;//限制次数
        if ($param['userid'] != '' && $param['identity'] != '') {
            $where['userid'] = $param['userid'];
            $where['identity'] = $param['identity'];
            $date = date('Y-m-d', $time);
            $where['_string'] = 'date_format(add_time, "%Y-%m-%d")="' . $date . '"';
            $call_num = D('api_call_records')->where($where)->count();//当天打电话次数
            $share_num = D('api_share')->where($where)->count();//当天分享次数
            $total = $call_num + $share_num;
            if ($total > $limit_num) {
                Response::error(-2, '每天最多拨打' . $limit_num . '次,请通过分享来获得更多拨打机会');
            }
        }
        $phone = D('api_recruitment')->where(array('id' => $id))->getField('phone');
        if ($phone) {
            $res = D('api_call_records')->add($data);
            if($res){
                Response::success(array('phone' => $phone));
            }else{
                Response::error(-2, '出错了');
            }

        } else {
            Response::error(-1, '未查到');
        }
    }
    /**
     * 分享
     * @author: 李胜辉
     * @time: 2018/12/17 11:34
     *
     */

    public function share($param)
    {
        $data['userid'] = $param['userid'] ? $param['userid'] : '';//用户id
        $data['identity'] = $param['identity'] ? $param['identity'] : '';//身份(1:个人;2:公司)
        $time = time();
        $data['add_time'] = date('Y-m-d H:i:s', $time);
        $res = D('api_share')->add($data);
        if ($res) {
            Response::setSuccessMsg('分享成功');
            Response::success(array());
        } else {
            Response::error(-1, '未查到');
        }
    }

    /**
     * 身份切换
     * @author: 李胜辉
     * @time: 2018/12/124 11:34
     *
     */
    public function cutIdentity($param){
        $userid=$param['userid'];//用户id
        $identity=$param['identity'];//身份1:个人;2:公司
        if(!$userid){
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
        }
        $data['identity']=$identity;
        $has = D('api_identity')->where(array('userid' => $userid,'type'=>$identity))->getField('id');
        if($has){
            $res=D('ApiUsers')->where(array('userid' => $userid))->save($data);
            if($res!==false){
                Response::setSuccessMsg('切换成功');
                Response::success(array('status'=>1,'identity'=>$identity));
            }else{
                Response::error(-2,'出错了');
            }
        }else{
            Response::error(-1,'请注册',array('status'=>0));
        }
    }


}