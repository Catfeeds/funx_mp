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
        $filed = ['id', 'name', 'start_time', 'end_time', 'description', 'coupon_info','activity_type','type'
            ,'one_prize','one_count','two_prize','two_count','three_prize','three_count'];
        $data = Activitymodel::where('id',$id)->select($filed)->first();
        if(!$data){
            $this->api_res(1007);
            return false;
        }
        if (!(time()>= strtotime($data->start_time) && time() < strtotime($data->end_time))) {
            $this->api_res(11001);
            return false;
        }elseif($data->type== 'LOWER'){
            $this->api_res(11006);
            return false;
        }
        $arr = [$data->one_prize,$data->two_prize,$data->three_prize];
        $this->load->model('coupontypemodel');
        $p = Coupontypemodel::whereIn('id',$arr)->get(['name'])->toArray();
        $prize = [
            ['prize'=>$p[0]['name'],'count'=>$data->one_count,'type'=>1,'name'=>'一等奖'],
            ['name'=>'谢谢参与','type'=>0],
            ['prize'=>$p[1]['name'],'count'=>$data->two_count,'type'=>2,'name'=>'二等奖'],
            ['name'=>'谢谢参与','type'=>0],
            ['prize'=>$p[2]['name'],'count'=>$data->three_count,'type'=>3,'name'=>'三等奖'],
            ['name'=>'谢谢参与','type'=>0],];

        $this->api_res(0,['data'=>$prize,'name'=>$data->name,'strat_time'=>$data->start_time->format('Y-m-d')
            ,'end_time'=>$data->end_time->format('Y-m-d'),'description'=>$data->description]);
    }
    public function drawQualifications()
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
        $data = Activitymodel::where('id',$id)->get($filed)->toArray();
        if(!$data){
            $this->api_res(1007);
            return false;
        }
        $this->load->model('customermodel');
        $this->load->model('residentmodel');
        $this->load->model('storeactivitymodel');
        $this->load->model('drawmodel');
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        //1-首次关注用户 2-已入住用户 3-以退租用户 4-所有用户
        $customer = unserialize($data[0]['limit']);
        $Qualifications = $customer['com'];
        if ($Qualifications == '1') {
            $costomer = Customermodel::where('id', CURRENT_ID)->get();
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID])->get();
            if (!$costomer || $resident) {
                $this->api_res(11004);
                return false;
            }
        } elseif ($Qualifications == '2') {
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID, 'status' => 'NORMAL'])->select(['store_id'])->first();
            if (!$resident) {
                $this->api_res(11004);
                return false;
            }
            $store_activirty = Storeactivitymodel::where(['store_id'=>$resident->store_id,'activity_id'=>$data[0]['id']])->get();
            if(!$store_activirty){
                $this->api_res(11004);
                return false;
            }
        } elseif ($Qualifications == '3') {
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID, 'status' => 'NORMAL_REFUND'])->select(['store_id'])->first();
            if (!$resident) {
                $this->api_res(11004);
                return false;
            }
            $store_activirty = Storeactivitymodel::where(['store_id'=>$resident->store_id,'activity_id'=>$data[0]['id']])->get();
            if(!$store_activirty){
                $this->api_res(11004);
                return false;
            }
        }elseif($Qualifications == '1,2'){
            $costomer = Customermodel::where('id', CURRENT_ID)->get();
            $resident = Residentmodel::where(['customer_id' => CURRENT_ID, 'status' => 'NORMAL'])->get();
            if ((!$costomer || !$resident)) {
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
            $count = Drawmodel::where(['activity_id' =>$data[0]['id'], 'customer_id' => CURRENT_ID])->count();
            if ($count >= 1) {
                $this->api_res(11002);
                return false;
            }
        } elseif ($drawlimt == '2') {
            $count = Drawmodel::where(['activity_id' => $data[0]['id'], 'customer_id' => CURRENT_ID,])
                ->whereDate('draw_time', date('Y-m-d', time())) ->count();
            if ($count >= 1) {
                $this->api_res(11002);
                return false;
            }
        } elseif ($drawlimt == '3') {
            $count = Drawmodel::where(['activity_id' => $data[0]['id'], 'customer_id' => CURRENT_ID,])
                ->whereDate('draw_time', date('Y-m-d', time()))->count();
            if ($count >= 2) {
                $this->api_res(11002);
                return false;
            }
        }
        //门店符合要求

        $data_id = $data[0]['id'];
        $prize = [
            'prize' => $data[0]['one_prize'].','.$data[0]['two_prize'].','.$data[0]['three_prize'],
            'count' => $data[0]['one_count'].','.$data[0]['two_count'].','.$data[0]['three_count'],
        ];
        $interval_time =strtotime($data[0]['end_time']) - strtotime($data[0]['start_time']);

        $this->lotteryDraw($prize, $data_id, $interval_time);
    }

    private function lotteryDraw($prize, $data_id, $interval_time)
    {
        $this->load->model('coupontypemodel');
        $draw = new Drawmodel();
        $draw_time = Drawmodel::where(['activity_id' => $data_id])->orderBy('draw_time','desc')
            ->get(['draw_time'])->toArray();
        //设置时间间隔
        $c = explode(',', $prize['count']);
        $count = 0;
        for ($i = 0; $i < count($c); $i++) {
            $count += $c[$i];
        }
        $de_time = ceil($interval_time/$count);
        $time = isset($draw_time[0]['draw_time'])?$draw_time[0]['draw_time']:0;
        if ((time() - strtotime($time)) < $de_time){
            //插入未中奖记录
            $draw->activity_id = $data_id;
            $draw->customer_id = CURRENT_ID;
            $draw->draw_time = date('Y-m-d H:i:s', time());
            $draw->is_draw = 0;
            if ($draw->save()) {
                $this->api_res(0,['type'=>0]);
                return false;
            } else {
                $this->api_res(500);
                return false;
            }
        }
        $prize_rand = rand(1, $count*2);
        $p = explode(',', $prize['prize']);
        for ($i = 0; $i < count($p); $i++) {
            if ($prize_rand <= $c[$i]) {
                //插入中奖记录
                $prize_name = Coupontypemodel::where('id', $p[$i])->get(['name']);
                $draw->activity_id = $data_id;
                $draw->customer_id = CURRENT_ID;
                $draw->draw_time = date('Y-m-d H:i:s', time());
                $draw->is_draw = 1;
                $draw->prize_id = $p[$i];
                $draw->prize_name = $prize_name[0]['name'];
                if ($draw->save()) {
                    //发放奖品
                    $coupon = Coupontypemodel::where('id',$p[$i])->first();
                    $update_coupon = [
                        'customer_id'=>CURRENT_ID,
                        'coupon_type_id' => $p[$i],
                        'status' => 'unused',
                        'deadline' => $coupon->deadline,
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
                $draw->activity_id = $data_id;
                $draw->customer_id = CURRENT_ID;
                $draw->draw_time = date('Y-m-d H:i:s', time());
                $draw->is_draw = 0;
                if ($draw->save()) {
                    $this->api_res(0,['type'=>0]);
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
        $activity = Activitymodel::find($id);
        $shareData['imgUrl'] =$this->fullAliossUrl($activity->share_img);
        if($activity->activity_type== 'TRNTABLE'){
            //转盘
            $shareData['link'] = config_item('web_domain').'/#/turntable/'.$activity->id;
        }elseif($activity->activity_type== 'SCRATCH'){
            //刮刮乐
            $shareData['link'] = config_item('web_domain').'/#/scraping/'.$activity->id;
        }else {
            $shareData['link'] = $activity->qrcode_url;
        }
        $shareData['desc'] = $activity->share_des;
        $shareData['title'] = $activity->share_title;
        $this->load->helper('wechat');
        $appid = config_item('wx_map_appid');
        $secret = config_item('wx_map_secret');
        $this->load->library('M_redis');
        $ticket = $this->m_redis->getjsapi_ticket();
        if(!$ticket){
            $ticket = $this->get_access_token($appid,$secret);
            $this->m_redis->setjsapi_ticket($ticket);
        }
        $time = time();
        $chars = $this->random_str();
        $url = $this->input->get_request_header('referer', false);
        $str = "jsapi_ticket=$ticket&noncestr=$chars&timestamp=$time&url=$url";
        $signature = sha1($str);
        $debug = false;
        if(ENVIRONMENT!='production'){
            $debug = true;
        }
        $jssdk = [
            'debug'=>$debug,
            'appId' => $appid,
            'timestamp' => $time,
            'nonceStr' => $chars,
            'signature' =>$signature,
            'jsApiList' => ['onMenuShareTimeline', 'onMenuShareAppMessage','onMenuShareQQ','onMenuShareWeibo','onMenuShareQZone'],
        ];
        $this->api_res(0,['jssdk'=>$jssdk,'shareDate'=>$shareData]);
    }
}