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
        //判断是否有合同
        if(isset($input['has_contract'])){

            $contract   = $resident->contract();
            if($contract->exists){
                $this->api_res(10016);
                return;
            }
        }
        //验证住户的uxid是不是当前ID
//        $this->checkUser($resident->uxid);
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

}
