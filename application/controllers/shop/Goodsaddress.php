<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-17
 * Time: 09:31
 * [web端]商城管理 - 地址
 */
class Goodsaddress extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodsaddressmodel');
    }

    /**
     * 增加收货地址
     */
    public function addAddress()
    {
        $post = $this->input->post(null,true);
        $customer_id = intval(strip_tags(trim($post['customer_id'])));
        if(!$this->validation())
        {
            $field = ['apartment','building','room_number','name','phone'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($field)]);
            return ;
        }
        $address                = new Goodsaddressmodel();
        $address->apartment     = trim($post['apartment']);
        $address->building      = trim($post['building']);
        $address->room_number   = trim($post['room_number']);
        $address->name          = trim($post['name']);
        $address->phone         = trim($post['phone']);
        $address->customer_id   = trim($customer_id);

        if($address->save()){
            $this->api_res(0,['id' => $address->id]);
        }else{
            $this->api_re0s(1009);
        }
    }

    /**
     * 删除收货地址
     */
    public function deleteAddress()
    {
        $id   = $this->input->post('id',true);
        if(Goodsaddressmodel::destroy($id)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 修改更新收货地址
     */
    public function updateAddress()
    {
        $post = $this->input->post(NULL,true);
        if(!$this->validation())
        {
            $field = ['apartment','building','room_number','name','phone'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($field)]);
            return ;
        }
        $id                 = intval(strip_tags(trim($post['id'])));
        $address            = Goodsaddressmodel::where('id',$id)->first();
        $address->apartment     = trim($post['apartment']);
        $address->building      = trim($post['building']);
        $address->room_number   = trim($post['room_number']);
        $address->name          = trim($post['name']);
        $address->phone         = trim($post['phone']);

        if($address->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 收货人地址列表
     */
    public function listAddress()
    {
        $post        = $this->input->post(NULL,true);
        $customer_id = intval(trim($post['customer_id']));
        $field       = ['id','customer_id','apartment','building','room_number','name','phone'];
        $listaddress = Goodsaddressmodel::where('customer_id',$customer_id)->orderBy('id','desc')->get($field);
        $this->api_res(0,['list'=>$listaddress]);
    }


    /**
     * 表单验证规则
     */
        private function validation()
        {
           $this->load->library('form_validation');
           $config = array(
               array(
                   'field' => 'customer_id',
                   'label' => '客户id',
                   'rules' => 'trim|required',
               ),
               array(
                   'field' => 'apartment',
                   'label' => '公寓',
                   'rules' => 'trim|required',
               ),
               array(
                   'field' => 'building',
                   'label' => '楼',
                   'rules' => 'trim|required',
               ),
               array(
                   'field' => 'room_number',
                   'label' => '房间号',
                   'rules' => 'trim|required',
               ),
               array(
                   'field' => 'name',
                   'label' => '收货人',
                   'rules' => 'trim|required',
               ),
               array(
                   'field' => 'phone',
                   'label' => '电系电话',
                   'rules' => 'trim|required',
               ),
           );
           return $config;
        }

}
