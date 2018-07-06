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

        $resident_id = Residentmodel::where('customer_id',CURRENT_ID)->get(['id'])
            ->map(function ($re_id){
                return $re_id->id;
            })->toArray();
        if (isset($resident_id)&&!empty($resident_id)){
            $rooms = Roomunionmodel::with('store_s')->with('building_s')
                ->where('resident_id',$resident_id)
                ->get(['id','store_id','number','building_id'])->toArray();
            $this->api_res(0,['list'=>$rooms]);
        }else{
            $this->api_res(0,[]);
        }
    }

    /**
     * 根据房间获取门店
     */
    public function getStore()
    {
        $post = $this->input->post(null,true);
        $room_id = trim($post['room_id']);
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $rooms = Roomunionmodel::with('store_s')
            ->where('id',$room_id)
            ->get(['id','store_id'])->toArray();
        $this->api_res(0,['list'=>$rooms]);
    }

    /**
     * 判断房间是否有智能设备
     */
    public function withSmart()
    {
        $post = $this->input->post(null,true);
        $roomid = intval($post['id']);
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $smartdevice = Smartdevicemodel::where('room_id',$roomid)->get(['id','room_id','type','serial_number'])->toArray();
        if ($smartdevice){
            foreach ($smartdevice as $key=>$value){
                if ($smartdevice[$key]['type'] != 'LOCK'){
                    unset($smartdevice[$key]);
                }
            }
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

    /*
     * 修改密码
     */
   public function updatePwd()
   {
       $post    = $this->input->post();
       $oldpwd  = trim($post['old_pwd']);
       $newpwd  = trim($post['new_pwd']);
       $repwd   = trim($post['re_pwd']);
       if (!($newpwd ===$repwd)){
           $this->api_res(1002);
           return;
       }
       if ($post['serial_number']){
           $device_id = trim($post['serial_number']);
           $supplier = Smartdevicemodel::where('serial_number',$device_id)
               ->get(['supplier'])->map(function ($supplier){
                   return $supplier->supplier;
               });
           if ($supplier[0] == 'DANBAY'){
/*               (new Danbaylock($device_id))->handle();
               $danbay = new Danbaylock($device_id);
               $all_pwd = $danbay->getLockPwdList();

               $pwd = $danbay->editGuestPwd(1,$newpwd);*/
               $this->api_res(0,[]);
           }elseif ($supplier[0] == 'YEEUU'){
               $pwd = (new Yeeuulock($device_id))->extPwd($newpwd,1);
               $this->api_res(0,$pwd);
           }else{
               $this->api_res(0,[]);
           }
       }
   }

   /**
    * 查看开门记录
    */
    public function lockRecord()
    {
        $post = $this->input->post();
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Ymd',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Ymd',time());};
        if ($post['serial_number']){
            $device_id = trim($post['serial_number']);
            $supplier = Smartdevicemodel::where('serial_number',$device_id)
                ->get(['supplier'])->map(function ($supplier){
                    return $supplier->supplier;
                });
            if ($supplier[0] == 'DANBAY'){
                $this->api_res(0,[]);
            }elseif ($supplier[0] == 'YEEUU'){
                $pwd = (new Yeeuulock($device_id))->openRecords($bt,$et);
                $pwd = $pwd['data'];
                foreach ($pwd as $key=>$value){
                    $pwd[$key]['opTime'] = date('Y-m-d',strtotime($pwd[$key]['opTime']));
                }
                var_dump($pwd);
                //$pwd = rsort($pwd['opTime']);
                $this->api_res(0,$pwd);
            }else{
                $this->api_res(0,[]);
            }
        }
    }
}