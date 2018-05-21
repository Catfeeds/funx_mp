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

        foreach ($goods as $key=>$value){
            //$goods[$key]['goods'] = $this->fullAliossUrl($value['goods_thumb']);
           $qq = &$goods[$key]['goods'];
            foreach ($qq as $key=>$value){
                $qq[$key]['goods_thumb'] = $this->fullAliossUrl($value['goods_thumb']);
            }
        }
        $this->api_res(0,['list'=>$goods]);
    }




}