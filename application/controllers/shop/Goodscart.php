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
        $this->load->model('goodsmodel');
        $post = $this->input->post(null, true);
        $uxid = intval(strip_tags(trim($post['uxid'])));
        $field = ['id', 'goods_id', 'quantity'];
        if (isset($uxid)) {
            $goodscart = Goodscartmodel::with('goods')->where('uxid', $uxid)->get($field)->toArray();

            foreach ($goodscart as $key => $value) {
                $qq = &$goodscart[$key]['goods']['goods_thumb'];
                $qq = $this->fullAliossUrl($qq);
            }
            $this->api_res(0, $goodscart);
        } else {
            $this->api_res(1005);
        }
    }

    /**
     *删除购物车
     */
    public function deleteCart()
    {
        $id = $this->input->post('id', true);
        if (Goodscartmodel::destroy($id)) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 添加购物车
     */
    public function addCart()
    {
        $post = $this->input->post(null, true);
        //$uxid = intval(strip_tags(trim($post['uxid'])));
        $goods_id = intval(strip_tags(trim($post['goods_id'])));

        $addcart = Goodscartmodel::where('uxid', 7)->where('goods_id', $goods_id)->first();
        if (isset($addcart)) {
            $addcart->increment("quantity");
            $this->api_res(0);
        } else {
            $cart = new Goodscartmodel();
            $cart->uxid = 7;
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
     *购物车商品自增
     */
    public function quantityIncre()
    {
        $post = $this->input->post(null, true);
        $cart_id = intval(strip_tags(trim($post['id'])));
        $cart = Goodscartmodel::find($cart_id);
        if (!$cart) {
            $this->api_res(1007);
            return;
        }
        if ($cart->increment('quantity')) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 购物车商品自减  -
     */
    public function quantityDecre()
    {
        $post = $this->input->post(null, true);
        $cart_id = intval(strip_tags(trim($post['id'])));
        $cart = Goodscartmodel::find($cart_id);
        if (!$cart) {
            $this->api_res(1007);
            return;
        }
        if ($cart->decrement('quantity')) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 购物车数量
     */
    public function quantityNum()
    {
        $post = $this->input->post(null, true);
        //$uxid = intval(strip_tags(trim($post['uxid'])));
        $goods_id = intval(strip_tags(trim($post['goods_id'])));
        $cart_num = intval(strip_tags(trim($post['quantity'])));
                                        //CURRENT_ID
        $num = Goodscartmodel::where('uxid', 7)->where('goods_id', $goods_id)->first();
        $num->quantity = $cart_num;
        if ($num->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 购物车结算信息
     */
    public function accounts()
    {
        $this->load->model('goodsmodel');
        $post = $this->input->post(null, true);
        //$uxid = intval(strip_tags(trim($post['uxid']))); //69 70
        $cart_id = $post['cart_id'];
        $id         = isset($cart_id)?explode(',',$cart_id):NULL;
        $goodscarts = Goodscartmodel::with('goods')->find($id)->map(function ($cart) {
            if ($cart->uxid != 7) { //CURRENT_ID
                log_message('error', '购物车跟当前用户不匹配');
                throw new Exception();
            }
            $cart->price= $cart->quantity * $cart->goods->shop_price;
            $cart->sum  = $cart->quantity++ ;
            return $cart;
        });

        foreach ($goodscarts as $key => $value) {
            $goodscarts[$key]['goods']['goods_thumb'] = $this->fullAliossUrl(($goodscarts[$key]['goods']['goods_thumb']));
        }
        $price = $goodscarts->sum('price');
        $sum = $goodscarts->sum('sum');
        $this->api_res(0, ['goodscarts' => $goodscarts, 'price' => $price ,'sum' =>$sum]);
    }
}