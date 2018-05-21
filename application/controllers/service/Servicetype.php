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
        $post   = $this->input->post(null,true);
        $where = [];
        isset($post['id'])?$where['id']=intval($post['id']):$where=[];
        $filed  = ['id','name','image_url'];
        $type   = Servicetypemodel::where($where)->orderBy('id','desc')->get($filed)->toArray();

        foreach ($type as $key=>$value){
            $type[$key]['image_url'] = $this->fullAliossUrl($value['image_url']);
        }
        $this->api_res(0,['list'=>$type]);
    }



}