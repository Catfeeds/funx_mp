<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/21
 * Time:        14:10
 * Describe:    预约看房
 */
class Reserve extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reserveordermodel');
    }

    /*
     * 生成新预约订单
     */
    public function reserve()
    {
        $post = $this->input->post(NULL,true);
        if(!$this->validation())
        {
            $fieldarr= ['store_id','room_type_id','name','phone','visit_time'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return;
        }

        $reserve = new Reserveordermodel();
        $reserve->fill($post);
        $reserve->customer_id = CURRENT_ID;
        $reserve->visit_by = 'WECHAT';
        $reserve->status = 'WAIT';

        if ($reserve->save()) {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 预约过的房源
     */
    public function precontract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $this->load->model('employeemodel');
        $filed = ['id','room_type_id','room_id','employee_id'];
        var_dump(CURRENT_ID);
        $precontract = Reserveordermodel::with('room')->with('room_type')->with('employee')
            ->where('customer_id',CURRENT_ID)
            ->whereIn('status',['WAIT','BEGIN'])->get($filed)
            ->map(function ($item){
                if (isset($item->room_type->images)){
                    var_dump($item->room_type->images);
                    $item->room_type->images = $this->fullAliossUrl(json_decode($item->room_type->images,true),true);
                }
                return $item;
            })->toArray();
        $this->api_res(0,['list'=>$precontract]);
    }

    /**
     * 看过的房源
     */
    public function visited()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $this->load->model('employeemodel');
        $filed = ['id','room_type_id','room_id','employee_id'];
        var_dump(CURRENT_ID);
        $precontract = Reserveordermodel::with('room')->with('room_type')->with('employee')
            ->where('customer_id',CURRENT_ID)
            ->where('status','END')->get($filed)
            ->map(function ($item){
                if (isset($item->room_type->images)){
                    $item->room_type->images = $this->fullAliossUrl(json_decode($item->room_type->images,true),true);
                }
                return $item;
            })->toArray();
        $this->api_res(0,['list'=>$precontract]);
    }

    /**
     * 表单验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'store_id',
                'label' => '门店ID',
                'rules' => 'trim|required',
            ),
        array(
                'field' => 'room_type_id',
                'label' => '房型ID',
                'rules' => 'trim|required',
            ),
        array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
        array(
                'field' => 'phone',
                'label' => '联系电话',
                'rules' => 'trim|required|max_length[13]',
            ),
        array(
                'field' => 'visit_time',
                'label' => '预约时间',
                'rules' => 'trim|required',
            ),
        );
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}