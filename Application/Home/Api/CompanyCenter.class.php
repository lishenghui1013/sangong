<?php
/**
 * 公司用户中心的相关操作
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

class CompanyCenter extends Base
{
    /**
     * 公司用户中心获取用户信息
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

        $user_info = D('ApiIdentity as i')->join('left join api_users as u on u.userid=i.userid')->join('left join api_company_info as c on c.identity_id=i.id')->join('left join api_industry as d on d.id=c.industry_id')->field('i.id,i.is_deposit,i.userid,u.identity,u.userphoto,d.industry_name,c.com_name,c.logo')->where(['i.type' => 2, 'i.userid' => $userid])->find();
        if (empty($user_info)) {
            Response::error(-1, '暂无数据');
        }
        Response::success($user_info);
    }
//简介,  行业列表

    /**
     * 行业列表
     * @author: 李胜辉
     * @time: 2018/12/15 11:34
     *
     */

    public function industryList()
    {
        $list = D('api_r_position')->field('id,position_name,addtime')->where(array('dataflag' => 1, 'pid' => 0))->select();
        if (empty($list)) {
            Response::error(-1, '暂无数据');
        }
        Response::success($list);
    }

    /**
     * 添加公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int userid 用户id
     * @param: string intro 公司简介
     * @param: int industry_id 行业id
     * @param: string logo 公司logo
     * @param: string phone 公司电话
     * @param: string address 公司地址
     * @param: string dis_info 优惠信息(json串)
     * @param: string item_info 服务项目(json串)
     */
    public function addCompanyInfo($param)
    {
        $id = $param['userid'];
        $identity_id = D('api_identity')->where(array('userid' => $id, 'type' => 2))->getField('id');
        //添加公司信息表api_company_info记录
        $com_data['identify_id'] = $identity_id;
        $com_data['com_name'] = $param['com_name'];//公司名称
        $com_data['intro'] = $param['intro'];
        $com_data['industry_id'] = $param['industry_id'];
        $com_data['logo'] = $param['logo'];
        $com_data['phone'] = $param['phone'];
        $com_data['address'] = $param['address'];
        $com_data['add_time'] = date('Y-m-d H:i:s', time());
        $res = D('api_company_info')->add($com_data);
        //添加优惠信息记录
        $com_discounts = json_decode($param['com_discounts'],TRUE);
        $time = date('Y-m-d H:i:s', time());
        if ($com_discounts) {
            foreach ($com_discounts as $key => $value) {
                $com_discounts[$key]['add_time'] = $time;
                $com_discounts[$key]['identity_id'] = $identity_id;
                $com_discounts[$key]['pic'] = substr($com_discounts[$key]['pic'],0,-1);
            }
            unset($key, $value);
        }
        $dis_res = D('api_company_discounts')->addAll($com_discounts);
        //添加服务项目
        $com_item = json_decode($param['com_item'],TRUE);
        if ($com_item) {
            foreach ($com_item as $key => $value) {
                $com_item[$key]['add_time'] = $time;
                $com_item[$key]['identity_id'] = $identity_id;
                $com_item[$key]['pic'] = substr($com_item[$key]['pic'],0,-1);
            }
            unset($key, $value);
        }
        $item_res = D('api_company_item')->addAll($com_item);
        if ($res) {
            if ($dis_res) {
                if ($item_res) {
                    Response::success(array());
                } else {
                    Response::error(-2, '服务项目添加失败!');
                }
            } else {
                Response::error(-3, '优惠信息添加失败!');
            }
        } else {
            Response::error(-1, '添加失败!');
        }
    }

    /**
     * 编辑公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int userid 用户id
     * @param: string intro 公司简介
     * @param: int industry_id 行业id
     * @param: string logo 公司logo
     * @param: string phone 公司电话
     * @param: string address 公司地址
     */
    public function editCompanyInfo($param)
    {
        $id = $param['userid'] ? $param['userid'] : '';
        $identity_id = D('api_identity')->where(array('userid' => $id, 'type' => 2))->getField('id');
        //添加公司信息表api_company_info记录
        $com_data['intro'] = $param['intro'];
        $com_data['com_name'] = $param['com_name'];//公司名称
        $com_data['industry_id'] = $param['industry_id'];
        $com_data['logo'] = $param['logo'];
        $com_data['phone'] = $param['phone'];
        $com_data['address'] = $param['address'];
        $res = D('api_company_info')->where(array('industry_id' => $identity_id))->save($com_data);
        //编辑优惠信息记录
        $com_discounts = json_decode($param['com_discounts'],TRUE);
        if ($com_discounts) {
            foreach ($com_discounts as $key => $value) {
                $com_discounts[$key]['pic'] = str_replace(';','',$com_discounts[$key]['pic']);
                $update = D('api_company_discounts')->save($com_discounts[$key]);
                if ($update === false) {
                    Response::error(-2, '出错了');
                }
            }
            unset($key, $value);
        }
        //添加服务项目
        $com_item = json_decode($param['com_item'],TRUE);
        if ($com_item) {
            foreach ($com_item as $key => $value) {
                $com_item[$key]['pic'] = str_replace(';','',$com_item[$key]['pic']);
                $set = D('api_company_item')->save($com_item[$key]);
                if ($set === false) {
                    Response::error(-2, '出错了');
                }
            }
            unset($key, $value);
        }
        if ($res) {
            Response::setSuccessMsg('修改成功');
            Response::success(array());
        } else {
            Response::error(-1, '修改失败!');
        }
    }

    /**
     * 查询要编辑的公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int userid 用户id
     */
    public function getEditCompanyInfo($param)
    {
        $id = $param['userid'] ? $param['userid'] : '';
        $identify_id = D('api_identity')->where(array('userid' => $id, 'type' => 2))->getField('id');
        $res['company_info'] = D('api_company_info')->where(array('industry_id' => $identify_id))->find();
        //编辑优惠信息记录
        $res['company_discounts'] = D('api_company_discounts')->where(array('industry_id' => $identify_id))->select();
        //编辑服务项目
        $res['company_discounts'] = D('api_company_item')->where(array('industry_id' => $identify_id))->select();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据');
        }
    }


    /**
     * 发布招聘信息
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
     * @param:string descript 描述
     * @param:string addtime 添加时间
     * @param:string address 详细地址
     * @param:int recruitment_num 招聘人数
     * @param:int worked_years 工作经验
     * @param:string education 学历
     * @param:string worktime_descript 工作时间描述
     */
    public function publishRecruit($param)
    {
        $data = $param;
        $description = htmlspecialchars_decode($param['descript'], ENT_QUOTES);
        $arr_descript = json_decode($description, true);
        $str_descript = '';
        if ($arr_descript) {
            foreach ($arr_descript as $key => $value) {
                str_replace(';', ',', $value['note']);
                $str_descript .= $value['note'] . ';';
            }
            unset($key, $value);
            $data['descript'] = substr($str_descript, 0, -1);
        }
        $common = new Common();
        $arr_area = $common->getAddressNum(array('lng' => $data['lng'], 'lat' => $data['lat']));
        $data['province'] = $arr_area['province'];
        $data['city'] = $arr_area['city'];
        $data['area'] = $arr_area['area'];
        $data['addtime'] = time();//添加时间
        $is_have = D('api_recruitment')->where(array('userid' => $data['userid'], 'isdefault' => 1))->getField('id');
        $data['isdefault'] = $is_have ? 2 : 1;
        $res = D('api_recruitment')->add($data);
        if ($res) {
            Response::success(array('id' => $res));
        } else {
            Response::error(-1, '发布失败!');
        }
    }

    /**
     * 招聘信息列表
     * @author: 李胜辉
     * @time: 2018/12/13 10:34
     */
    public function recruitList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $position_id = $param['position_id'] ? $param['position_id'] : '';//职位id
        $area = $param['area'] ? $param['area'] : '';//区县编码
        $sort = $param['sort'] ? $param['sort'] : '';//排序
        $city_lat = $param['lat'] ? $param['lat'] : '';//纬度
        $city_lng = $param['lng'] ? $param['lng'] : '';//经度
        $userid = $param['userid'] ? $param['userid'] : '';//用户id
        $city = $param['city'] ? '' : '';
        $where = array();
        $where ['r.status'] = 1;
        $where ['r.dataflag'] = 1;
        if ($city != '') {
            $where['city'] = $city;
        }
        if ($userid != '') {
            $where['r.userid'] = $userid;
        }
        if ($position_id != '') {
            $where['r.position_id'] = $position_id;
        }
        if ($area != '') {
            $where['r.area'] = $area;
        }
        $str_sort = 'i.is_deposit,r.worked_years asc';
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
        $list = D('api_recruitment as r')->join('left join api_areas as a on a.code=r.area')->join('left join api_identity as i on i.userid=r.userid')->field('r.id,r.title,r.wage,r.payment_type,r.recruitment_num,r.worked_years,r.education,r.addtime,a.region,i.is_deposit')->where($where)->limit($start, $limit)->order($str_sort)->select();
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
     * 招聘详情页
     * @author: 李胜辉
     * @time: 2018/11/30 11:34
     *
     */

    public function recruitDetail($param)
    {
        $id = $param['id'];//招聘id
        if (!$id) {
            Response::error(-2, '缺少参数');
        }
        $resume_info = D('api_recruitment as r')->join('left join api_identity as i on i.userid=r.userid')->join('left join api_r_position as p on p.id=r.position_id')->field('r.id,r.userid,r.title,p.position_name,p.pid,r.phone,r.wage,r.payment_type,r.address,r.addtime,r.descript,r.read_num,r.recruitment_num,r.worked_years,r.education,i.realname,i.id as identity_id,r.worktime_descript')->where(['r.id' => $id])->find();
        if (empty($resume_info)) {
            Response::error(-1, '暂无数据');
        }
        $resume_info['logo'] = D('api_company_info')->where(array('identity_id' => $resume_info['identity_id']))->getField('logo');
        $read_num = $resume_info['read_num'] + 1;
        D('api_recruitment')->where(array('id' => $resume_info['id']))->save(array('read_num' => $read_num));
        $description = explode(';', $resume_info['descript']);
        $resume_info['descript'] = $description;
        $resume_info['addtime'] = date('Y-m-d', $resume_info['addtime']);
        $resume_info['industry'] = D('api_r_position')->where(array('id' => $resume_info['pid']))->getField('position_name');//行业名称
        Response::success($resume_info);
    }

    /**
     * 查询公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int id 公司id
     */
    public function companyDetail($param)
    {
        $id = $param['id'];//公司id
        $res = D('api_company_info as c')->join('left join api_identity as i on i.id=c.identity_id')->join('left join api_r_position as d on d.id=c.industry_id')->field('c.intro,c.id,c.identity_id,c.industry_id,c.logo,c.phone,c.address,i.realname,i.is_deposit,d.industry_name')->where(array('c.id' => $id))->find();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据!');
        }
    }

    /**
     * 公司服务项目列表
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int id 公司id
     */
    public function serviceItemList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $id = $param['id'];//公司id
        $identiry_id = D('api_company_info')->where(array('id' => $id))->getField('identity_id');
        $res = D('api_company_item')->field('id,item_name,pic')->where(array('identity_id' => $identiry_id))->limit($start, $limit)->select();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据!');
        }
    }

    /**
     * 公司优惠信息列表
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int id 公司id
     */
    public function discountsList($param)
    {
        $id = $param['id'] ? $param['id'] : '';//公司id
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $where = array();
        $total = D('api_company_item')->where($where)->count();
        if ($id != '') {
            $identiry_id = D('api_company_info')->where(array('id' => $id))->getField('identity_id');
            $where['c.identity_id'] = $identiry_id;
            $limit = $total;
        }
        $res = D('api_company_discounts as c')->join('left join api_identity as i on i.id=c.identity_id')->field('c.id,c.identity_id,c.title,c.content,c.pic,c.add_time,i.realname,i.userid')->where($where)->limit($start, $limit)->select();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据!');
        }
    }

    /**
     * 会员企业列表
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     */
    public function memberComList($param)
    {
        $pagenum = $param['pagenum'] ? $param['pagenum'] : 1;//当前页
        $limit = $param['limit'] ? $param['limit'] : 10;//每页显示条数
        $start = ($pagenum - 1) * $limit;
        $where = array();
        $where['type'] = 2;//身份(1个人 2企业)
        $where['is_deposit'] = 1;//是否缴纳了保证金(1:已经缴费;2:没有缴费)
        $res = D('api_identity as i')->join('left join api_users as u on u.userid=i.userid')->join('left join api_company_info as ci on ci.identity_id=i.id')->join('left join api_r_position as d on d.id=ci.industry_id')->field('i.id,i.userid,i.realname,u.userphoto,ci.logo,d.industry_name')->where($where)->limit($start, $limit)->select();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据!');
        }
    }

    /**
     * 拨打简历电话
     * @author: 李胜辉
     * @time: 2018/12/17 11:34
     *
     */

    public function getPhone($param)
    {
        $id = $param['id'];//简历id
        $data['userid'] = $param['userid'] ? $param['userid'] : '';
        $data['identity'] = $param['identity'] ? $param['identity'] : '2';
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
        $phone = D('api_resume')->where(array('rid' => $id))->getField('phone');
        if ($phone) {
            $res = D('api_call_records')->add($data);
            if ($res) {
                Response::success(array('phone' => $phone));
            } else {
                Response::error(-2, '出错了');
            }
            Response::success(array('phone' => $phone));
        } else {
            Response::error(-1, '未查到');
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

}