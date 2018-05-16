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
            $this->api_res(0,['searchgoods'=>$goods]);
        }else{
            $this->api_res(1005);
        }
    }

    /**
     * 表单验证规则
     */
 /*  private function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '商品名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'id',
                'label' => '商品id',
                'rules' => 'trim|required',
            ),
        );
        return $config;
    }*/

}