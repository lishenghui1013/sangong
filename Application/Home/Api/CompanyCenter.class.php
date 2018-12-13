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
        $position = $param['position'];
        $userid = $param['userid'];
        if (!$userid) {
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少userid');
        }
        if (!$position) {
            Response::error(ReturnCode::EMPTY_PARAMS, '缺少position');
        }

        $user_info = D('ApiIdentity as i')->join('left join api_users as u on u.id=i.userid')->join('left join api_company_info as c on c.identity_id=i.id')->join('left join api_industry as in on in.id=c.industry_id')->field('i.id,i.realname,u.identity,u.id as uid,u.userphoto,in.industry_name')->where(['i.type' => $position, 'i.userid' => $userid])->find();
        if (empty($user_info)) {
            Response::error(-1, '暂无数据');
        }
        Response::success($user_info);
    }
//简介,  行业列表

    /**
     * 添加公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int identity_id 用户认证身份id
     * @param: string intro 公司简介
     * @param: int industry_id 行业id
     * @param: string logo 公司logo
     * @param: string phone 公司电话
     * @param: string address 公司地址
     */
    public function ideaFeedback($param)
    {
        $data = $param;
        $data['add_time'] = date('Y-m-d H:i:s', time());
        $res = D('api_company_info')->add($data);
        if ($res) {
            Response::success(array('id' => $res));
        } else {
            Response::error(-1, '添加失败!');
        }
    }

    /**
     * 查询公司信息
     * @author: 李胜辉
     * @time: 2018/12/01 11:34
     * @param:int identity_id 用户认证身份id
     */
    public function companyDetail($param)
    {
        $id = $param['identity_id'];//认证身份id
        $res = D('api_company_info')->where(array('identity_id' => $id))->find();
        if ($res) {
            Response::success($res);
        } else {
            Response::error(-1, '暂无数据!');
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
            $data['descript'] = $str_descript;
        }
        $common = new Common();
        $arr_area = $common->location(array('lng' => $data['lng'], 'lat' => $data['lat']));
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
        $where = array();
        $where ['r.status'] = 1;
        $where ['r.dataflag'] = 1;
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
                    $where['r.payment_type'] = array('lt',2);
                    break;
                case '2'://月薪从高到低
                $str_sort = 'r.wage desc';
                    $where['r.payment_type'] = array('gt',1);
                break;
                case '3'://距离
                $str_sort = 'ACOS(SIN(('.$city_lat.' * 3.1415) / 180 ) *SIN((r.lat * 3.1415) / 180 ) +COS(('.$city_lat.' * 3.1415) / 180 ) * COS((r.lat * 3.1415) / 180 ) *COS(('.$city_lng.' * 3.1415) / 180 - (r.lng * 3.1415) / 180 ) ) * 6380  asc';
                break;
                case '4'://发布时间
                    $str_sort = 'r.addtime desc';
                    break;
                default :
                    $str_sort = 'i.is_deposit,r.worked_years';
                    break;
            }
        }else{
            $list = D('api_recruitment as r')->join('left join api_areas as a on a.code=r.area')->join('left join api_identity as i on i.userid=r.userid')->field('r.id,r.title,r.wage,r.payment_type,r.recruitment_num,r.worked_years,r.education,r.addtime,a.region,i.is_deposit')->where($where)->limit($start, $limit)->order($str_sort)->select();

        }
        if ($list) {
            foreach ($list as $key => $value) {
                if ($value['is_deposit'] == '1') {
                    $list[$key]['approve'] = 'Y';//是否认证
                } else {
                    $list[$key]['approve'] = 'N';
                }
                //发布时间
                $list[$key]['addtime'] = date('Y-m-d',$value['addtime']);
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


}