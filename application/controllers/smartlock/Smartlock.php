<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/libraries/Danbaylock.php';
require_once APPPATH.'/libraries/Yeeuulock.php';
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/7
 * Time:        16:49
 * Describe:    住户门锁操作
 */
class Smartlock extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('smartdevicemodel');
    }

    public function rooms()
    {
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        /*$resident_id = Residentmodel::where('customer_id',CURRENT_ID)->get(['id'])
            ->map(function ($re_id){
                return $re_id->id;
            })->toArray();*/
        if (/*$resident_id||*/1){
            $rooms = Roomunionmodel::with('store_s')->with('building_s')
                ->where('resident_id',1684)
                ->get(['id','store_id','number','building_id'])->toArray();
            $this->api_res(0,['list'=>$rooms]);
        }else{
            $this->api_res(0,[]);
        }

    }

    /**
     * 判断房间是否有智能设备
     */
    public function withSmart()
    {
        $post = $this->input->post(null,true);
        $roomid = intval($post['id']);
        $smartdevice = Smartdevicemodel::where('room_id',$roomid)->get(['id','serial_number'])->toArray();
        if ($smartdevice){
            $this->api_res(0,$smartdevice);
        }else{
            $this->api_res(0,[]);
        }
    }

    /**
     * 生成临时密码
     */
    public function temporaryPwd()
    {
        $this->load->library('m_redis');
        $post = $this->input->post();
        if ($post['serial_number']){
            $device_id = trim($post['serial_number']);
            $supplier = Smartdevicemodel::where('serial_number',$device_id)
                ->get(['supplier'])->map(function ($supplier){
                    return $supplier->supplier;
                });
            if ($supplier[0] == 'DANBAY'){
                (new Danbaylock($device_id))->handle();
                $pwd = (new Danbaylock($device_id))->addTempPwd();
                $this->api_res(0,$pwd);
            }elseif ($supplier[0] == 'YEEUU'){
                $pwd = (new Yeeuulock($device_id))->cyclePwd();
                $this->api_res(0,$pwd);
            }else{
                $this->api_res(0,[]);
            }

        }
    }



}