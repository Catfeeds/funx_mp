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
    protected $app;
    protected $oauth;

    const TARGET_URL = 'target_url';

    public function __construct()
    {
        parent::__construct();
        $this->app = new Application($this->getCustomerWechatConfig());
    }



    public function login()
    {
        //登陆页面调起oauth
        $this->oauth = $this->app->oauth;
        $target_url = trim($this->input->get('target_url', true));
        $target_url = empty($target_url) ? site_url() : $target_url;
        if ($this->auth->isAuth()) {
            redirect($target_url);
            return;
        }

        $this->session->set_userdata(self::TARGET_URL, $target_url);
        $this->oauth->redirect()->send();
    }

    public function callback()
    {
        //授权通过回调
        $user = $this->oauth->user();
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

    /**
     * 客户端微信公众号配置
     */
    public static function getCustomerWechatConfig(){
        $debug  = (ENVIRONMENT!=='development'?false:true);
        return array(
            'debug'     => $debug,
            'app_id'    => config_item('wx_map_appid'),
            'secret'    => config_item('wx_map_secret'),
            'token'     => config_item('wx_map_token'),
            'aes_key'   => config_item('wx_map_aes_key'),
            'log' => [
                'level' => 'debug',
                'file'  => APPPATH.'cache/wechat.log',
            ],
            //调用授权
            'oauth' => [
                'scopes'   => config_item('wx_customer_oauth_scopes') ,
                'callback' => config_item('wx_oauth_callback'),
            ],
            /*'payment' => [
                'merchant_id'   => CUSTOMER_WECHAT_PAYMENT_MERCHANT_ID,
                'key'           => CUSTOMER_WECHAT_PAYMENT_KEY,
                'cert_path'     => CUSTOMER_WECHAT_PAYMENT_CERT_PATH,
                'key_path'      => CUSTOMER_WECHAT_PAYMENT_KEY_PATH,
            ],*/
            'guzzle' => [
                'timeout' => 3.0,
            ]
        );
    }

    /**
     * 生成菜单
     */
    public function menu(){
        exit('Hello-Baby');

        $app    = new Application($this->getCustomerWechatConfig());
        $menu   = $app->menu;
        var_dump($menu->current());exit;

        $url_resident_guide = 'https://mp.weixin.qq.com/s?__biz=MzI3MTMwODIyNw==&mid=2247484131&idx=2&sn=aed494e10935d13e9af15a73060df69e&chksm=eac2864fddb50f593a5787021f64f4dd668f2fb745d876d7698e835460e177478bbd88c2f444#rd';

        $url_strawberry_market = 'https://mp.weixin.qq.com/s?__biz=MzI3MTMwODIyNw==&mid=2247484131&idx=1&sn=bd1eb5a51e848aded59d588abcb3d315&chksm=eac2864fddb50f59ab1d6140f7bbf678918e47d836e25607c7e9f247df24961bc369f22dc599#rd';

        $buttons = [
            [
                'name'       => '关于草莓',
                'sub_button' => [
                    [
                        'name' => '草莓作品',
                        'type' => 'click',
                        'key'  => 'STRAWBERRY_WORKS',
                    ],
                    [
                        'name' => '草莓故事',
                        'type' => 'click',
                        'key'  => 'STRAWBERRY_STORIES',
                    ],
                    /*[
                        'name' => '草莓公约',
                        'type' => 'view',
                        'url'  => $url_resident_guide,
                    ],*/
                    [
                        'name' => '合作联系',
                        'type' => 'click',
                        'key'  => 'COOPERATE_AND_CONTACT',
                    ],
                    [
                        'name' => '投诉信箱',
                        'type' => 'click',
                        'key'  => 'EMAIL_FOR_COMPLAINT',
                    ],
                ],
            ],
            [
                'name'       => '预约看房',
                'sub_button' => [
                    [
                        'name' => '找房源',
                        'type' => 'view',
                        'url'  => wechat_url(),
                    ],
                    [
                        'name' => '近期活动',
                        'type' => 'click',
                        'key'  => 'RECENT_ACTIVITIES',
                    ],
                ],
            ],
            [
                'name'       => '我是草莓',
                'sub_button' => [
                    [
                        'name' => '个人中心',
                        'type' => 'view',
                        'url'  => wechat_url('center'),
                    ],
                    [
                        'name' => '生活服务',
                        'type' => 'view',
                        'url'  => wechat_url('service'),
                    ],
                    [
                        'name' => '金地商城',
                        'type' => 'view',
                        'url'  => wechat_url('shop'),
                    ],
                ],
            ],
        ];

        var_dump($menu->add($buttons));

    }
}
