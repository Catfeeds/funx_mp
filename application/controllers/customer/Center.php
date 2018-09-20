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
        $this->load->library('m_redis');
    }

    /**
     * 个人设置
     */
    public function showInfo()
    {
        $filed = ['id', 'name', 'avatar', 'nickname', 'phone'];
        $customer = Customermodel::where('uxid', $this->current_id)->first($filed);
        if ($customer) {
            $this->api_res(0, $customer);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 显示昵称
     */
    public function showNickname()
    {
        $customer = Customermodel::where('uxid', $this->current_id)->get(['nickname']);
        if ($customer) {
            $this->api_res(0, $customer);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 设置昵称
     */
    public function setNickname()
    {
        $post = $this->input->post(null,true);
        $config = [['field' => 'nickname', 'label' => '昵称', 'rules' => 'trim|required|max_length[32]']];
        if (!$this->validationText($config)) {
            $this->api_res(1002,['error'=>$this->form_first_error(['nickname'])]);
            return false;
        } else {
            $nickname = $post['nickname'];
            $customer = Customermodel::where('uxid', $this->current_id)->first();
            $customer->nickname = $nickname;
            if ($customer->save()) {
                $this->api_res(0);
            } else {
                $this->api_res(1009);
            }
        }
    }

    /**
     * 发送手机验证码
     */
    public function setPhone()
    {
        $post = $this->input->post(null, true);
        $config = [['field' => 'phone', 'label' => '手机号', 'rules' => 'trim|required|max_length[11]|numeric']];
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error(['phone'])]);
            return false;
        } else {
            $phone = $post['phone'];
            if (!$this->m_redis->ttlCustomerPhoneCode($phone)) {
                $this->api_res(10007);
                return false;
            }
            $this->load->library('sms');
            $code = str_pad(rand(1, 9999), 4, 0, STR_PAD_LEFT);
            $str = SMSTEXT . $code;
            $this->m_redis->storeCustomerPhoneCode($phone, $code);
            $this->sms->send($str, $phone);
            $this->api_res(0);
        }
    }

    /**
     * 验证手机验证码
     */
    public function verifyPhone()
    {
        $post = $this->input->post(null,true);
        $config = $this->validation();
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error(['phone', 'code'])]);
            return false;
        } else {
            $phone = $post['phone'];
            $code = $post['code'];
            if ($this->m_redis->verifyCustomerPhoneCode($phone, $code)) {
                $customer = Customermodel::where('uxid', $this->current_id)->first();
                $customer->phone = $phone;
                if ($customer->save()) {
                    $this->api_res(0);
                } else {
                    $this->api_res(1009);
                }
            } else {
                $this->api_res(1002);
            }
        }
    }

    /**
     * 验证
     */
    public function validation()
    {
        $config = array(
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'trim|required|max_length[11]|numeric',
            ),
            array(
                'field' => 'code',
                'label' => '验证码',
                'rules' => 'trim|required|max_length[4]|numeric',
            ),
        );
        return $config;
    }
}