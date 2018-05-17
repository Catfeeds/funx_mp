<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/16 0016
 * Time:        16:18
 * Describe:
 */
class Wechat extends MY_Controller
{
    protected $wechatApp;
    protected $wechatOauth;

    const TARGET_URL = 'target_url';

    public function __construct()
    {
        parent::__construct();
        $this->wechatApp = new Application(Server::getCustomerWechatConfig());

    }

    public function menu(){

    }

    public function login()
    {
        //登陆页面调起oauth
        $this->wechatOauth = $this->wechatApp->oauth;
        $target_url = trim($this->input->get('target_url', true));
        $target_url = empty($target_url) ? site_url() : $target_url;
        if ($this->auth->isAuth()) {
            redirect($target_url);
            return;
        }

        $this->session->set_userdata(self::TARGET_URL, $target_url);
        $this->wechatOauth->redirect()->send();
    }

    public function callback()
    {
        //授权通过回调
        $user = $this->wechatOauth->user();
        $this->auth->setAuth($user->toArray());

        $target_url = $this->session->userdata(self::TARGET_URL);
        $target_url = empty($target_url) ? site_url() : $target_url;

        $customer = Customermodel::where('openid', $user->getId())->first();
        if (empty($customer)) {
            $customer = new Customermodel();
            $customer = $customer->fill($this->getFillData($user->toArray()));
            $customer->subscribe = 1;
            $customer->save();
        } else {
            $customer->avatar = $user->getAvatar();
            $customer->nickname = $user->getName();
            $customer->save();
        }

        redirect($target_url);
    }

    private function getFillData($user)
    {
        return array(
            'openid' => $user['original']['openid'],
            'nickname' => $user['original']['nickname'],
            'sex' => $user['original']['sex'],
            'province' => $user['original']['province'],
            'country' => $user['original']['country'],
            'city' => $user['original']['city'],
            'avatar' => $user['avatar'],
        );
    }
}
