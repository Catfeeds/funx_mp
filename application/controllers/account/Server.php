<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__.'Wechat.php';
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\News;
use EasyWeChat\Message\Text;
use EasyWeChat\Message\Material;
use EasyWeChat\Message\Image;

use Carbon\Carbon;
use GuzzleHttp\Client;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/16 0016
 * Time:        9:23
 * Describe:    微信Server
 */

class Server extends MY_Controller
{
    //protected $app;
    protected $message;
    protected $openid;
    protected $eventKey;
    protected $event;

    public function __construct()
    {
        parent::__construct();

        $this->app = new Application(getCustomerWechatConfig());
        $this->load->model('customermodel');
    }

    /**
     * 确定当前微信的 openid
     */
    private function setOpenid($openid)
    {
        $this->openid = $openid;

        return $this;
    }

    /**
     * 设置事件的 eventKey
     */
    private function setEventKey($key)
    {
        $this->eventKey = $key;

        return $this;
    }

    /**
     * 为 message 属性赋值
     */
    private function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }


    /**
     * 设置事件类型
     */
    private function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }


    public function index()
    {
        $app = $this->app;
        $server = $app->server;

        $server->setMessageHandler(function ($message) use ($app) {

            $this->setMessage($message)
                ->setOpenid($message->FromUserName)
                ->setEvent($message->Event)
                ->setEventKey($message->EventKey);

            switch ($message->MsgType) {
                case 'event':
                    switch ($message->Event) {
                        case 'subscribe':
                            return $this->subscribe();
                            break;
                        case 'unsubscribe':
                            return $this->unsubscribe();
                            break;
                        case 'SCAN':
                            try {
                                if (empty($message->EventKey)) {
                                    return new Text([
                                        'content' => '哈喽，欢迎来到草莓社区，请点击菜单栏【草莓社区】查看房源介绍。预约看房，请在【预约看房】下选择你感兴趣的门店，填写预约信息并提交，会有工作人员联系您，谢谢!']);
                                }

                                $id = (int)$message->EventKey;


                                if (10 == strlen($id) && 1 == substr($id, 0, 1)) {
                                    return $this->helpFriend($app, $message, $id);
                                }

                                return $this->checkInOrBookingEvent($message, $id);

                            } catch (Exception $e) {
                                log_message('error', $e->getMessage());
                                return new Text(array('content' => '没有找到记录!'));
                            }
                            break;
                        case 'CLICK':
                            switch ($message->EventKey) {
                                case 'COOPERATE_AND_CONTACT':
                                    return new Text(array(
                                        'content' =>"品牌/合作/媒体，请发送邮件至\nhupan@gemdalepi.com；\nliuxiafang@gemdalepi.com"
                                    ));
                                    break;
                                case 'STRAWBERRY_WORKS':
                                    return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-LliqVfjfvjYaooup1hBXmups');
                                    break;

                                case 'STRAWBERRY_STORIES':
                                    return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-Llv4lZFIcbQNLWMNWUnYoHv0');
                                    break;

                                case 'V21_RESERVE_ROOM':
                                    return new Text(array(
                                        'content' => "「看房预约 加入草莓」\n 金地金谷公寓请联系王经理\n /爱心 13510118004 /爱心 \n 民治优城公寓请联系邹经理 \n /爱心 13714394080 /爱心\n"
                                    ));
                                    break;

                                case 'V22_JOIN_US':
                                    return new Text(array(
                                        'content' => "「草莓社区招聘」\n /玫瑰 请联系胡小姐 /玫瑰\n /爱心 13603093089 /爱心\n"
                                    ));
                                    break;

                                case 'RETURN_MATERIAL':
                                    return new Material('image', 'yJpxNfF2ENp1OPlxjaVZjaWHB3Q-sElofONGCGfJjWk');
                                    break;

                                case 'EMAIL_FOR_COMPLAINT':
                                    return new Text(['content' => '投诉/建议，请发送邮件至'."\n".'chenxin@gemdalepi.com']);
                                    break;

                                case 'RECENT_ACTIVITIES':
                                    return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-Llp1Ehaiw4DEZET0lbvOrD88');
                                    break;
                                default:
                                    break;
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case 'text':
                    //如果是纯英文字符, 就转换成大写
                    $msgContent = trim($message->Content);
                    if (ctype_alpha($msgContent)) {
                        $msgContent = strtoupper($msgContent);
                    }
                    switch ($msgContent) {
                        case '1':
                            return new Text([
                                'content' => wechat_url(),
                            ]);
                            break;
                        case '2':
                            return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-Llv4lZFIcbQNLWMNWUnYoHv0');
                            break;
                        case '3':
                            return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-LliqVfjfvjYaooup1hBXmups');
                            break;
                        case '4':
                            return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-Llp1Ehaiw4DEZET0lbvOrD88');
                            break;
                        case 'CXDJQ':
                            return $this->inquireCoupon($message);
                            break;
                        case '优城wifi':
                            return new Text(['content' => '点击<a href="http://wportal.tpauth.cn:8080/portal/wechat_auth/?token=48b9d3b1 ">免费上网</a>']);
                            break;
                        default:
                            return $this->handleTextMessage($message, $msgContent, $app);
                            break;
                    }
                default:
                    break;
            }
        });

        $response = $server->serve();
        $response->send();
    }


    /**
     * 处理关注事件
     */
    private function subscribe()
    {
        try {
            Customermodel::where('openid', $this->openid)->update(['subscribe' => 1]);
            if (empty($this->eventKey)) {
                return $this->goToSweepstakes(config_item('new_customer_activity_id'));
            }

            return $this->scan();

        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            return new Text(['content' => '没有找到该记录!']);
        }
    }


    /**
     * 处理取关事件
     */
    private function unsubscribe()
    {
        Customermodel::where('openid', $this->openid)->update(['subscribe' => 0]);

        return 'success';
    }


    /**
     * 处理扫描带参数二维码事件
     */
    private function scan()
    {
        //扫带参数的二维码, 会携带一个场景值, 根据这个场景值展开业务
        $eventKey   = (int)str_replace('qrscene_', '', $this->eventKey);

        //好友助力的二维码, 场景值是1开头
        if (10 == strlen($eventKey) && 1 == substr($eventKey, 0, 1)) {
            return $this->helpFriend($this->app, $this->message, $eventKey);
        }

        return $this->checkInOrBookingEvent($this->message, $eventKey);
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

    private function checkInOrBookingEvent($message, $eventKey)
    {
        //$loginUrl = site_url('login?target_url=');

        //办理入住以及预订房间时的场景值
        $this->load->model('residentmodel');  
        $this->load->model('ordermodel');  
        $this->load->model('roomunionmodel');  
        $this->load->model('roomtypemodel');

        $resident   = Residentmodel::findOrFail($eventKey);

        if (0 == $resident->customer_id) {
            $customer   = Customermodel::where('openid', $message->FromUserName)->first();

            if (empty($customer)) {
                $customer           = new Customermodel();
                $customer->openid   = $message->FromUserName;
                $customer->uxid         = Customermodel::max('uxid')+1;
                $customer->save();
            }

            $resident->customer_id  = $customer->id;
            $resident->uxid  = $customer->uxid;
            $resident->save();
            $resident->orders()->where('uxid', 0)->update(['customer_id' => $customer->id,'uxid'=>$customer->uxid]);
        }

        //根据住户状态分别进行处理
        //扫码的来源: 1,办理入住; 2,预订房间的支付
        //如果是办理入住,将用户带到合同信息确认的页面
        $bookingOrdersCnt   = $resident->orders()
            ->where('status', Ordermodel::STATE_PENDING)
            ->where('type', Ordermodel::PAYTYPE_RESERVE)
            ->count();

        //有未支付的预订订单, 则应该去支付
        if (0 < $bookingOrdersCnt) {
//            $url    = $loginUrl.site_url(['order', 'status']);
            $url    = '预定订单支付页面';
        } else {
//            $url    = $loginUrl.site_url(['contract', 'preview', $resident->id]);
            $url    = '合同展示页面URL';
        }

        return new News(array(
            'title'         => $resident->room->apartment->name,
            'description'   => "您预订的【{$resident->room->number}】",
            'url'           => $url,
            'image'         => $this->fullAliossUrl(json_decode($resident->roomunion->roomtype->images,true),true),
        ));
    }



}
