<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-16
 * Time: 10:20
 * [web端]商城管理 - 商品
 */
class Goods extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodsmodel');
    }

    /**
     *商品 商品详情描述
     */
    public function goodsInfo()
    {
        $post = $this->input->post(null,true);
        $goods_id = intval(strip_tags(trim($post['id'])));
        $filed = ['id', 'name', 'shop_price', 'description', 'detail', 'goods_thumb'];
        if(isset($goods_id)) {
            $goods = Goodsmodel::where('id',$goods_id)->get($filed);
            foreach ($goods as $key=>$value){
                $goods[$key]['goods_thumb'] = $this->fullAliossUrl($value['goods_thumb']);
            }
            $this->api_res(0,['goodslist'=>$goods]);
        }else{
            $this->api_res(1005);
        }
    }

    /**
     *查找商品 按商品名 模糊查找
     */
    public function searchGoods()
    {
        $name = $this->input->post('name',true);
        $field = ['id', 'name', 'shop_price', 'description', 'goods_thumb'];
        if (isset($name)){
            $goods = Goodsmodel::where('name', 'like', "%$name%")->orderBy('id', 'desc')->get($field);
            foreach ($goods as $key=>$value){
                $goods[$key]['goods_thumb'] = $this->fullAliossUrl($value['goods_thumb']);
            }
            $this->api_res(0,['searchgoods'=>$goods]);
        }else{
            $this->api_res(1005);
        }
    }

    /**
     * 商城订单 状态 - 个人中心
     */
    public function goodsSta()
    {
        $this->load->model('goodsordermodel');


        $uxid = CURRENT_ID;
        $field = ['number','goods_money','created_at','status'];

        if(isset($uxid)){
            $goods = Goodsordermodel::where('uxid',$uxid)->whereIn(
                'status',[
                    Goodsordermodel::STATE_PENDING,
                    Goodsordermodel::STATE_PAYMENT,
                    Goodsordermodel::STATE_DELIVERED,
                    Goodsordermodel::STATE_COMPLETE,
                    Goodsordermodel::STATE_CLOSE
                ]
            )->get($field)->groupBy('status');
            $this->api_res(0,$goods);
        }else{
            $this->api_res(1005);
        }
    }

//    /**
//     * 个人中心 商城订单 通过订单号点击查看订单
//     */
//    public function numorder()
//    {
//        $this->load->model('goodsordermodel');
//        $this->load->model('goodscartmodel');
//        $this->load->model('goodsaddressmodel');
//        $this->load->model('goodsordergoodsmodel');
//
//        $post = $this->input->post(null, true);
//        $number = trim($post['order_id']);
//        $cart_id = $post['cart_id'];
//        $field = ['id','uxid','number','address_id','goods_money','goods_quantity'];
//        $order = Goodsordermodel::with('address1')->where('uxid',7)->where('number',$number)->get($field);
//        $id         = isset($cart_id)?explode(',',$cart_id):NULL;
//        $goodscarts = Goodscartmodel::with('goods')->find($id)->map(function ($cart) {
//            if ($cart->uxid != 7) { //CURRENT_ID
//                log_message('error', '当前用户不匹配');
//                throw new Exception();
//            }
//            return $cart;
//        });
//        foreach ($goodscarts as $key => $value) {
//            $goodscarts[$key]['goods']['goods_thumb'] = $this->fullAliossUrl(($goodscarts[$key]['goods']['goods_thumb']));
//        }
//        $this->api_res(0, ['goods'=>$goodscarts,'info'=>$order]);
//    }

    /**
     * 个人中心 商城订单 点击查看订单
     */
    public function numorder()
    {
        $this->load->model('goodsordermodel');
        $this->load->model('goodscartmodel');
        $this->load->model('goodsaddressmodel');
        $this->load->model('goodsordergoodsmodel');

        $post = $this->input->post(null, true);
        $order_id = trim($post['order_id']);
        $order = Goodsordermodel::with('goods')->where('id',$order_id)->get()->map(function ($cart) {
           $cart1 = $cart->goods;
            return $cart1;
        })->toArray();
        foreach($order as $key=>$value){
            $goods = $order[$key][0]['goods_id'];
        }
        //$field1 = ['id','name','shop_price','description','goods_thumb','quantity'];
        //$infogoods = Goodsmodel::where('id',$goods)->get($field)->toArray();
        $id        = isset($goods)?explode(',',$goods):NULL;
        $infogoods = Goodsmodel::find($id)->map(function ($shop){
            return $shop;
        })->toArray();
        foreach ($infogoods as $key => $value){
            $infogoods[$key]['goods_thumb'] = $this->fullAliossUrl($infogoods[$key]['goods_thumb']);
        }
       //var_dump($infogoods);die();
        $field = ['id','uxid','number','address_id','goods_money','goods_quantity'];
        $order = Goodsordermodel::with('address1')->where('uxid',7)->where('id',$order_id)->get($field);

        $this->api_res(0, ['order&address'=>$order,'goodsinfo'=>$infogoods]);
    }

}