<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 10:12
 * [web端]生活服务
 */

class Servicetype extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('servicetypemodel');
    }

    /**
     *服务项目类型
     */
    public function servicetype(){

        $post   = $this->input->post(NULL,TRUE);
        $where  = [];
        isset($post['id'])?$where['id']=intval($post['id']):$where=[];
        $filed  = ['id','name'];
        $type   = Servicetypemodel::where($where)->orderBy('id','desc')->get($filed)->toArray();
        $this->api_res(0,['list'=>$type]);
    }



}