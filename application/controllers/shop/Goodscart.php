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
     * 购物车列表
     */
    public function listCart()
    {
        $this->load->model('Goodsmodel');
        $post = $this->input->post(null,true);
        $uxid = intval(strip_tags(trim($post['uxid'])));
        $field = ['id','goods_id','quantity'];
        if(isset($uxid)){
            $goodscart = Goodscartmodel::with('goods')->where('uxid',$uxid)->get($field);
            $this->api_res(0,['goodslist'=>$goodscart]);
        }else{
            $this->api_res(1005);
        }
    }

    /**
     *删除购物车
     */
    public function deleteCart()
    {
        $id   = $this->input->post('id',true);
        if(Goodscartmodel::destroy($id)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 添加购物车
     */
    public function addCart()
    {
        $post = $this->input->post(null,true);
        $uxid = intval(strip_tags(trim($post['uxid'])));
        $goods_id = intval(strip_tags(trim($post['goods_id'])));
        if(!$this->validation())
        {
            $field = ['id','goods_id','uxid'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($field)]);
            return ;
        }
        $addcart = Goodscartmodel::where('uxid',$uxid)->where('goods_id',$goods_id)->first();
        if(isset($addcart))
        {
            $addcart->increment("quantity");
            $this->api_res(0);
        }else{
            $cart = new Goodscartmodel();
            $cart->uxid = $uxid;
            $cart->goods_id = $goods_id;
            $cart->quantity = 1;
            if ($cart->save()) {
                $this->api_res(0);
            } else {
                $this->api_res(1009);
            }
        }
    }

    /**
     *购物车商品自增  +
     */
    public function quantityIncre(){
        $post = $this->input->post(null,true);
        $cart_id = intval(strip_tags(trim($post['id'])));
        $cart       = Goodscartmodel::find($cart_id);
        if(!$cart){
            $this->api_res(1007);
            return ;
        }
        if($cart->increment('quantity')){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 购物车商品自减  -
     */
    public function quantityDecre(){
        $post = $this->input->post(null,true);
        $cart_id = intval(strip_tags(trim($post['id'])));
        $cart       = Goodscartmodel::find($cart_id);
        if(!$cart){
            $this->api_res(1007);
            return ;
        }
        if($cart->decrement('quantity')){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
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
                'field' => 'goods_id',
                'label' => '商品id',
                'rules' => 'trim|required',
            ),
        );
        return $config;
    }

}