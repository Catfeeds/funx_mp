<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/17 0017
 * Time:        14:15
 * Describe:    基础功能 门店展示
 */
class Store extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('storemodel');
    }

    /**
     * 城市
     */
    public function city()
    {
        $city   = Storemodel::groupBy('city')->get(['city'])->map(function($c){
            return $c->city;
        });
        $this->api_res(0,['city'=>$city]);
    }

    /**
     * 门店列表
     */
    public function listStore(){
        $field  = ['id','name','province','city','district','theme',];
        $post   = $this->input->post(null,true);
        $name   = isset($post['name'])?trim(strip_tags($post['name'])):'';
        $where  = [];
        isset($post['city'])?$where['city']=trim(strip_tags($post['city'])):null;
        $store  = Storemodel::where('name','like',"%$name%")->where($where)->get($field);
        $this->api_res(0,['list'=>$store]);
    }

    /**
     * 获取门店信息
     */
    public function get()
    {
        $field  = ['id','name','province','city','district','theme','address','contact_user','counsel_phone',
            'counsel_time','describe','images','shop','relax','bus','history'];
        $post   = $this->input->post(null,true);
        $store_id   = intval($post['store_id']);
        $store  = Storemodel::select($field)->find($store_id);
        if(!$store){
            $this->api_res(1007);
        }
        $store->images  = $this->fullAliossUrl(json_decode($store->images),true);
        //$store->images  = $this->fullAliossUrl(json_decode($store->images,true),true);
        $this->api_res(0,['store'=>$store]);
    }
}
