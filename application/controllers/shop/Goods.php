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
    public function searchgoods()
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

}