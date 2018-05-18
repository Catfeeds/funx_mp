<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-17
 * Time: 15:51
 * [web端]商城购物车
 */
class Goodscart extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodscartmodel');
    }

    /**
     * 加入购物车
     */
    public function cart()
    {
        $this->load->model('Goodsmodel');
        $post = $this->input->post(null,true);
        $customer_id = intval(strip_tags(trim($post['customer_id'])));
        $field = ['id','goods_id','quantity'];
        if(isset($customer_id)){
            $goodscart = Goodscartmodel::with('goods')->where('customer_id',$customer_id)->get($field);
            $this->api_res(0,['goodslist'=>$goodscart]);
        }else{
            $this->api_res(1005);
        }
    }

    /**
     *删除购物车 - 删除购物车商品及数量
     */
    public function deletecart()
    {
        $id   = $this->input->post('id',true);
        if(Goodscartmodel::destroy($id)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }


}