<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 10:35
 * [web端]生活服务 - 服务预约
 */

class Serviceorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('serviceordermodel');
    }

    /**
     * 维修 - 服务预约
     */
    public function serviceOrder()
    {
        $this->load->model('roomunionmodel');
        $post = $this->input->post(NULL, true);
        if(!$this->validation())
        {
            $fieldarr   = ['addr_from','addr_to','name','phone','time','remark','paths','store_id','number'];
            $this->api_res(1002,['ermsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $store_id = intval(strip_tags(trim($post['store_id'])));
        $room_number = strip_tags(trim($post['number']));
        $room = Roomunionmodel::where('store_id', $store_id)->where('number', $room_number)->first();
        if (!$room) {
            $this->api_res(1007);
            return;
        }
        $room_id = $room->id;

        $id          = new Serviceordermodel();
        $id->addr_from    = trim($post['addr_from']);
        $id->addr_to   = trim($post['addr_to']);
        $id->name    = trim($post['name']);
        $id->phone   = trim($post['phone']);
        $id->time    = trim($post['time']);
        $id->remark  = isset($post['remark'])?($post['remark']):null;
        $images   = $this->splitAliossUrl($post['paths']);
       // $images   = json_encode($images);
        $id->paths=isset($images)?($images):null;
        $id->room_id   = trim($room_id);
        $id->store_id   = trim($store_id);

        if($id->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 清洁 - 服务预约
     */
    public function cleanOrder()
    {
        $this->load->model('roomunionmodel');
        $post = $this->input->post(NULL, true);
        if(!$this->validation())
        {
            $fieldarr   = ['name','phone','time','remark'];
            $this->api_res(1002,['ermsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $store_id = intval(strip_tags(trim($post['store_id'])));
        $room_number = strip_tags(trim($post['number']));
        $room = Roomunionmodel::where('store_id', $store_id)->where('number', $room_number)->first();
        if (!$room) {
            $this->api_res(1007);
            return;
        }
        $room_id = $room->id;
        $id             = new Serviceordermodel();
        $id->name    = trim($post['name']);
        $id->phone   = trim($post['phone']);
        $id->time    = trim($post['time']);
        $id->remark  = isset($post['remark'])?($post['remark']):null;
        $id->room_id   = trim($room_id);
        $id->store_id   = trim($store_id);
        if($id->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 服务订单
     */
    public function Order()
    {
        $post        = $this->input->post(NULL,true);
        $uxid        = intval(trim($post['uxid']));
        $field       = ['id','uxid','number','store_id','room_id','sequence_number','employee_id','service_type_id','name',
            'phone','addr_from','addr_to','estimate_money','pay_money','money','status','deal','time','remark','paths'];
        $listorder = Serviceordermodel::where('uxid',$uxid)->orderBy('id','desc')->get($field);
        foreach ($listorder as $key=>$value){
            $listorder[$key]['paths'] = $this->fullAliossUrl($value['paths']);
        }
        $this->api_res(0,['list'=>$listorder]);
    }

    /**
     * 表单验证规则
     */
    private function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'store_id',
                'label' => '公寓id',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'number',
                'label' => '房间号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'addr_from',
                'label' => '房间号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'addr_to',
                'label' => '客户姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '客户姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '电话号码',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'time',
                'label' => '预约时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'paths',
                'label' => '图片上传',
                'rules' => 'trim',
            ),
             array(
                 'field' => 'remark',
                 'label' => '需求备注',
                 'rules' => 'trim',
             ),
        );
        return $config;
    }

}