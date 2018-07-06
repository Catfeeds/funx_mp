<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/21 0021
 * Time:        18:25
 * Describe:    住户
 */
class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取住户信息
     */
    public function getResident(){
        $input  = $this->input->post(null,true);
        $resident_id   = $this->input->post('resident_id',true);
        $this->load->model('residentmodel');
        $this->load->model('contractmodel');

        //$resident   = Residentmodel::where('uxid',CURRENT_ID)->find($resident_id);
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }


        if(!empty($input['type'])&&$input['type']='sign_contract'&&$resident->card_type!==0){

            header('Location:'.config_item('my_bill_url'));
            return;
        }

//        if($input['type']='sign_contract'&&$resident->card_type!==0){
//
//            header('Location:'.config_item('my_bill_url'));
//            return;
//        }


        //判断是否有合同
        if(isset($input['has_contract'])){

            $contract   = $resident->contract()->where('status','!=',Contractmodel::STATUS_GENERATED);
            if($contract->exists()){
                $this->api_res(10016);
                return;
            }
        }
        //验证住户的uxid是不是当前ID
//        if(!$this->checkUser($resident->uxid))
//        {
//            $this->api_res(10022);
//            return;
//        }
        $this->load->model('roomunionmodel');
        $this->load->model('activitymodel');
        $this->load->model('coupontypemodel');
        $this->load->model('ordermodel');
        $this->load->model('customermodel');
        $this->load->model('storemodel');
        $data=$resident->transform($resident);
        $data['card_one_url']   = $this->fullAliossUrl($data['card_one_url'] );
        $data['card_two_url']   = $this->fullAliossUrl($data['card_two_url'] );
        $data['card_three_url']   = $this->fullAliossUrl($data['card_three_url'] );
        $this->api_res(0,['data'=>$data]);
    }

    /**
     * 租房记录
     */
    public function record()
    {
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $resident_id = Residentmodel::where('customer_id',CURRENT_ID)->get(['id'])
            ->map(function ($re_id){
                return $re_id->id;
            })->toArray();
        if (isset($resident_id)&&!empty($resident_id)){
            $residents  = Residentmodel::with(['roomunion'=>function($query){
                return $query->with('store');
            }])->where('id',$resident_id)
                ->whereIn('status', [
                    Residentmodel::STATE_NORMAL,
                    Residentmodel::STATE_RENEWAL,
                    Residentmodel::STATE_CHANGE_ROOM,
                    Residentmodel::STATE_UNDER_CONTRACT,
                ])->get();
            $this->api_res(0,['residents'=>$residents]);
        }else{
            $this->api_res(0,[]);
        }
    }

//    /**
//     * 申请退房
//     */
//    public function checkOut()
//    {
//        $residentId = trim($this->input->post('resident_id', true));
//        $this->load->model('residentmodel');
//        $resident   = Residentmodel::findOrFail($residentId);
//        $this->load->model('roomunionmodel');
//
//        $this->checkUser($resident->uxid);
//
//        if($resident->roomunion->resident_id != $residentId){
//            $this->api_res(10019);
//            return;
//        }
//        $this->api_res(0,['resident_id'=>$residentId]);
//
//    }
//
//
//    /**
//     * 住户退房-保存退款信息
//     */
//    public function refund()
//    {
//        $bank       = trim($this->input->post('bank', true));
//        $time       = trim($this->input->post('time', true));
//        $cardNumber = trim($this->input->post('card_number', true));
//        $residentId = trim($this->input->post('resident_id', true));
//
//        $this->load->model('residentmodel');
//        $resident   = Residentmodel::findOrFail($residentId);
//        $this->load->model('roomunionmodel');
//
//        $this->checkUser($resident->uxid);
//
//        if($resident->roomunion->resident_id != $residentId){
//            $this->api_res(10019);
//            return;
//        }
//
//        $time = strtotime($time);
//        if (FALSE === $time) {
//            throw new Exception('请选择合适的时间');
//        }
//
//        if (Residentmodel::STATE_NORMAL == $resident->status) {
//            $tmpInfo            = $resident->data;
//            $tmpInfo['refund']  = array(
//                'bank'        => $bank,
//                'out_time'    => date('Y-m-d', $time),
//                'bank_number' => $cardNumber,
//            );
//            $resident->data     = $tmpInfo;
//            $resident->save();
//        }
//
//        $this->api_res(0);
//    }

    public function count()
    {
        $this->load->model('reserveordermodel');
        $count['reserve'] = Reserveordermodel::where('status','WAIT')->where('customer_id',CURRENT_ID)->count();
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');

        $resident   = Residentmodel::with(['roomunion','orders'=>function($query){
            $query->where('status',Ordermodel::STATE_PENDING);
        }])->where('customer_id',$this->user->id);
        $orders  = $resident->get()->map(function($query){
            $query->count  = count($query->orders);
            $query->amount = $query->orders->sum('money');
            return $query;
        })->where('amount','>',0)->count();
        $count['order'] = $orders;
        $this->load->model('couponmodel');
        $count['coupon'] = Couponmodel::where('status','UNUSED')->where('customer_id',CURRENT_ID)->count();
        //$this->load->model('shopmodel');
        $count['shop'] = 0;/*Couponmodel::where('status','UNUSED')->where('customer_id',CURRENT_ID)->count();*/
        $this->api_res(0,$count);
    }
}
