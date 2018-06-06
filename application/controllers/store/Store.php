<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
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
        if(!defined('COMPANY_ID')){
            define('COMPANY_ID',4);
        }
    }

    /**
     * 调取门店地图的config
     */
    public function mapConfig(){
        $app    = new Application(getCustomerWechatConfig());
        $jssdk  = $app->js->config(['getLocation', 'openLocation'], false);
        $this->api_res(0,['jssdk'=>$jssdk]);
    }

    /**
     * 城市
     */
    public function showCity()
    {
        $where  = ['company_id'=>COMPANY_ID];
        $city   = Storemodel::where($where)->groupBy('city')->get(['city'])->map(function($c){
            return $c->city;
        });
        $this->api_res(0,['city'=>$city]);
    }

    /**
     * 获取门店名
     */
    public function showStore(){
        $city   = $this->input->post('city',true);
        $where  = ['company_id'=>COMPANY_ID];
        $city?$where['city']=$city:null;
        $store  = Storemodel::where($where)->get(['id','name','province','city','district']);
        $this->api_res(0,['stores'=>$store]);
    }

    /**
     * 门店列表
     */
    public function listStore()
    {
        $field  = ['id','name','province','city','district','theme',];
        $post   = $this->input->post(null,true);
        $name   = isset($post['name'])?trim(strip_tags($post['name'])):'';
        $where  = [];
        isset($post['city'])?$where['city']=trim(strip_tags($post['city'])):null;
        $store  = Storemodel::where('name','like',"%$name%")->where($where)->get($field);
        $this->api_res(0,['list'=>$store]);
    }


    /**
     * 获取门店和门店下房型信息
     */
    public function get()
    {
        $field  = ['id','name','rent_type','province','city','district','theme','address','contact_user','counsel_phone',
            'counsel_time','describe','images','shop','relax','bus','history'];
        $post   = $this->input->post(null,true);
        $store_id   = intval($post['store_id']);
        $store  = Storemodel::select($field)->find($store_id);
        /*$rent_type  = $store->rent_type;
        if($rent_type=='UNION'){

        }*/
        if(!$store)
        {
            $this->api_res(1007);
        }
        $store->images  = $this->fullAliossUrl(json_decode($store->images),true);
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $min_price  = $store->roomunion()->min('rent_price');
        $max_price  = $store->roomunion()->max('rent_price');
        $room_types = $store->roomtype()->get(['id','name','feature','images',])->map(function($room_type){
            $room_type->images  = $this->fullAliossUrl(json_decode($room_type->images),true);
            return $room_type;
        });
        $this->api_res(0,['store'=>$store,'price'=>compact('min_price','max_price'),'room_types'=>$room_types]);
    }


}
