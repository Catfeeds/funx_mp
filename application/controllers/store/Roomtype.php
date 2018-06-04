<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/17 0017
 * Time:        14:19
 * Describe:    基础信息 房型
 */
class Roomtype extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomtypemodel');
    }

    /**
     * 获取房型详细信息
     */
    public function get(){
        $field  = ['id','name','feature','area','room_number','hall_number','toilet_number','toward','provides','images','description'];
        $post   = $this->input->post(null,true);
        $room_type_id   = $post['room_type_id'];
        $this->load->model('storemodel');
        $room_type  = Roomtypemodel::with('store')->select($field)->find($room_type_id);
        if(!$room_type){
            $this->api_res(1007);
            return;
        }
        $this->load->model('roomunionmodel');
        $min_price  = $room_type->roomunion()->min('rent_price');
        $max_price  = $room_type->roomunion()->max('rent_price');
        $room_type->images  = $this->fullAliossUrl(json_decode($room_type->images,true),true);
        $this->api_res(0,['room_type'=>$room_type,'price'=>compact('min_price','max_price')]);
    }



}

