<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/25
 * Time:        10:29
 * Describe:    用户信息
 */

class Center extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customermodel');
    }

    /**
     * 个人设置
     */
    public function showInfo()
    {
        $post = $this->input->post(null,true);
        $filed = ['id', 'name', 'avatar', 'nickname', 'phone'];
        if (isset($post['id']) && !empty($post['id'])) {
            $id = trim($post['id']);
            $customer = Customermodel::where('id',$id)->get($filed);
            if ($customer) {
                $this->api_res(0, $customer);
            } else {
                $this->api_res(1009);
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 设置昵称
     */
    public function setNickname()
    {
        $post = $this->input->post(null,true);
        if(!$this->validation())
        {
            $fieldarr = ['id', 'nickname'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $id = trim($post['id']);
        $nickname = trim($post['nickname']);
        $customer = Customermodel::find($id);
        $customer->nickname = $nickname;
        if ($customer->save())
        {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 设置手机验证
     */
    public function setPhonenum()
    {
        $post = $this->input->post(null,true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = trim($post['id']);
            if (isset($post['phone']) && !empty($post['phone'])) {

            } else {
                $this->api_res(1002);
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'id',
                'label' => '用户id',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'nickname',
                'label' => '用户昵称',
                'rules' => 'trim|required|max_length[32]',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}