<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/9
 * Time: 14:50
 */
date_default_timezone_set('PRC');
class Draw extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('drawmodel');
    }

    public function showDraw()
    {
        $post = $this->input->post(null, true);
        $id = isset($post['id'])?$post['id']:null;
        if (!$id) {
            $this->api_res(1002);
            return false;
        }
        $this->load->model('activitymodel');
        $filed = ['id', 'name', 'start_time', 'end_time', 'description', 'coupon_info', 'limit','activity_type'
            ,'one_prize','one_count','two_prize','two_count','three_prize','three_count'];
        $data = Activitymodel::where('id',80)->get($filed)->toArray();
        if (!(time()>= strtotime($data[0]['start_time']) && time() < strtotime($data[0]['end_time']))) {
            $this->api_res(11001);
            return false;
        }elseif($data[0]['activity_type']== '-1'){
            $this->api_res(11006);
            return false;
        }
        $arr = [$data[0]['one_prize'],$data[0]['two_prize'],$data[0]['three_prize']];
        $this->load->model('coupontypemodel');
        $p = Coupontypemodel::whereIn('id',$arr)->get(['name'])->toArray();
        $prize = [
          ['prize'=>$p[0]['name'],'count'=>$data[0]['one_count'],'type'=>1,'name'=>'一等奖'],
            ['name'=>'谢谢参与','type'=>0],
            ['prize'=>$p[1]['name'],'count'=>$data[0]['two_count'],'type'=>2,'name'=>'二等奖'],
            ['name'=>'谢谢参与','type'=>0],
           ['prize'=>$p[2]['name'],'count'=>$data[0]['three_count'],'type'=>3,'name'=>'三等奖'],
            ['name'=>'谢谢参与','type'=>0],];
        $this->api_res(0,['data'=>$prize,'name'=>$data[0]['name']]);
}

    public function drawQualifications()
    {
        define('CURRENT_ID',1);
        $post = $this->input->post(null, true);
        $id = isset($post['id'])?$post['id']:null;
        if (!$id) {
            $this->api_res(1002);
            return false;
        }
        $this->load->model('activitymodel');
        $filed = ['id', 'name', 'start_time', 'end_time', 'description', 'coupon_info', 'limit','activity_type'
            ,'one_prize','one_count','two_prize','two_count','three_prize','three_count'];
        $data = Activitymodel::where('id',$id)->get($filed)->toArray();
        $this->load->model('customermodel');
        $this->load->model('residentmodel');
        $this->load->model('storeactivitymodel');
        $this->load->model('drawmodel');
        $this->load->model('couponmodel');
        //1-首次关注用户 2-已入住用户 3-以退租用户 4-所有用户
        //关注用户comstomer表有，但是resident表没有
        //已入住用户  resident表有信息，而且正在入住
        //已退租用户  resdent表由信息,但是已经退租
        $customer = unserialize($data[0]['limit']);
        $Qualifications = $customer['com'];
        if ($Qualifications == '1') {
            $costomer = Customermodel::where('id', CURRENT_ID)->get();
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID])->get();
            if ((!$costomer) && $resident) {
                $this->api_res(11004);
                return false;
            }
        } elseif ($Qualifications == '2') {
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID, 'status' => 'NORMAL'])->get();
            if (!$resident) {
                $this->api_res(11004);
                return false;
            }
        } elseif ($Qualifications == '3') {
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID, 'status' => 'NORMAL_REFUND'])->get();
            if (!$resident) {
                $this->api_res(11004);
                return false;
            }
        }elseif($Qualifications == '1,2'){
            $costomer = Customermodel::where('id', CURRENT_ID)->get();
            if ((!$costomer)) {
                $this->api_res(11004);
                return false;
            }
        }elseif($Qualifications == '2,3'){
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID])->get();
            if (!$resident) {
                $this->api_res(11004);
                return false;
            }
            }
//次数符合要求 1-一人一次 2-一天一次 3-一天两次
        $drawlimt = $customer['limit'];
        if ($drawlimt == '1') {
            $count = Drawmodel::where(['activity_id' =>$data[0]['id'], 'costomer_id' => CURRENT_ID])->count();
            if ($count == 1) {
                $this->api_res(11002);
                return false;
            }
        } elseif ($drawlimt == '2') {
            $count = Drawmodel::where(['activity_id' => $data[0]['id'], 'costomer_id' => CURRENT_ID,
                'draw_time' > time() - 24 * 60 * 60])->count();
            if ($count == 1) {
                $this->api_res(11002);
                return false;
            }
        } elseif ($drawlimt == '3') {
            $count = Drawmodel::where(['activity_id' => $data[0]['id'], 'costomer_id' => CURRENT_ID,
                'draw_time' > time() - 24 * 60 * 60])->count();
            if ($count == 2) {
                $this->api_res(11002);
                return false;
            }
        }else{
            return false;
        }
        $data_id = $data[0]['id'];
        $prize = [
            'prize' => $data[0]['one_prize'].','.$data[0]['two_prize'].','.$data[0]['three_prize'],
            'count' => $data[0]['one_count'].','.$data[0]['two_count'].','.$data[0]['three_count'],
        ];

        $interval_time = strtotime($data[0]['end_time']) - strtotime($data[0]['start_time']);
        $this->lotteryDraw($prize, $data_id, $interval_time);
    }

    private function lotteryDraw($prize, $data_id, $interval_time)
    { $this->load->model('coupontypemodel');
        $draw_time = Drawmodel::where(['activity_id' => $data_id, 'costomer_id' => CURRENT_ID])->orderBy('draw_time','desc')
            ->get(['draw_time'])->toArray();
        //设置时间间隔
        $time = isset($draw_time[0]['draw_time'])?$draw_time[0]['draw_time']:0;
        if ((time() - strtotime($time)) < $interval_time) {
            $this->api_res(11003);
            return false;
        }
        $c = explode(',', $prize['count']);
        $count = 0;
        for ($i = 0; $i < count($c); $i++) {
            $count += $c[$i];
        }
        $prize_rand = rand(1, $count);
        $p = explode(',', $prize['prize']);
        for ($i = 0; $i < count($p); $i++) {
            if ($prize_rand <= $c[$i]) {
                //插入中奖记录
                $prize_name = Coupontypemodel::where('id', $p[$i])->get(['name']);
                $draw = new Drawmodel();
                $draw->activity_id = $data_id;
                $draw->costomer_id = CURRENT_ID;
                $draw->draw_time = date('Y-m-d H:i:s', time());
                $draw->is_draw = 1;
                $draw->prize_id = $p[$i];
                $draw->prize_name = $prize_name[0]['name'];
                if ($draw->save()) {
                    //发放奖品
                    $update_coupon = [
                        'customer_id'=>1,
                        'coupon_type_id' => $data_id,
                        'status' => 'unused',

                    ];
                    $activity = new Couponmodel();
                    $activity->fill($update_coupon);
                    $res=$activity->save();
                    //改变奖品数量
                     $c[$i]--;
                    $count_insert = Activitymodel::find($data_id);
                    if($i == 0) {
                        $count_insert->one_count =  $c[$i];
                    }elseif($i == 1){
                        $count_insert->two_count =  $c[$i];
                    }elseif($i == 2){
                        $count_insert->three_count =  $c[$i];
                    }
                    if (($count_insert->save())&&($res)) {
                        $this->api_res(0,['prize'=> $prize_name[0]['name'],'type'=>$i+1]);
                        return false;
                    } else {
                        $this->api_res(500);
                        return false;
                    }
                } else {
                    $this->api_res(500);
                    return false;
                }
            } elseif ($i == (count($p) - 1)) {
                //插入未中奖记录
                $draw = new Draw();
                $draw->activity_id = $data_id;
                $draw->costomer_id = CURRENT_ID;
                $draw->draw_time = date('Y-m-d H:i:s', time());
                $draw->is_draw = 0;
                if ($draw->save()) {
                    $this->api_res(11003);
                    return false;
                } else {
                    $this->api_res(500);
                    return false;
                }
            }
            $prize_rand -=$c[$i];
        }
    }

    public function sharWechat(){
        $this->load->model('activitymodel');
        $this->load->helper('wechat');
        $post = $this->input->post(null,true);
        $id = isset($post['id'])?$post['id']:null;
        if (!$id) {
            $this->api_res(1002);
            return false;
        }
        $ac = Activitymodel::where('id',$id)->get()->toArray();
        $arr["imgUrl"] = $this->fullAliossUrl($ac[0]['share_img']);    // 分享显示的缩略图地址
        $arr["link"] = $ac[0]['qrcode_url'];    // 分享地址
        $arr["desc"] = $ac[0]['share_des'];   // 分享描述
        $arr["title"] = $ac[0]['share_title'];
        $this->api_res(0,['jssdk'=>$arr]);
    }
}

