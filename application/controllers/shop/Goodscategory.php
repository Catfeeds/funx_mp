<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-16
 * Time: 10:30
 * [web端]商城管理 - 商品分类
 */
class Goodscategory extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodscategorymodel');
    }

    /**
     * 商品 分类-商品
     */
    public function listgoods()
    {
        $this->load->model('goodsmodel');
        $filed  = ['id','name'];
        $goods  = Goodscategorymodel::with('goods')->get($filed)->toArray();
        $this->api_res(0,['list'=>$goods]);
    }




}