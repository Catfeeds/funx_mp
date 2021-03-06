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
        //$uxid = intval(strip_tags(trim($post['uxid'])));
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
        $address->uxid   = 35;

        if($address->save()){
            $this->api_res(0,['id' => $address->id]);
        }else{
            $this->api_res(1009);
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
       // $uxid = intval(trim($post['uxid']));
        $field       = ['id','uxid','apartment','building','room_number','name','phone'];
        $listaddress = Goodsaddressmodel::where('uxid',35)->orderBy('id','desc')->get($field);//$this->current_id;
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
               'field' => 'uxid',
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
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }

}
