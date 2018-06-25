<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\News;
use EasyWeChat\Message\Text;
use EasyWeChat\Message\Material;
use EasyWeChat\Message\Image;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as DB;
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
        $this->load->model('customermodel');
        $this->load->helper('wechat');
        $this->app = new Application(getCustomerWechatConfig());
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


//                                if (10 == strlen($id) && 1 == substr($id, 0, 1)) {
//                                    return $this->helpFriend($app, $message, $id);
//                                }

                                log_message('error',1);
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
                                        'content' => "品牌/合作/媒体，请发送邮件至\nhupan@gemdalepi.com；\nliuxiafang@gemdalepi.com"
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
                                    return new Text(['content' => '投诉/建议，请发送邮件至' . "\n" . 'chenxin@gemdalepi.com']);
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
                                'content' => config_item('wechat_url'),
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
     * 处理粉丝在后台的回复
     */
    private function handleTextMessage($message, $content, $app)
    {
        //测试
        return $this->defaultTextResponse();

        //是否是补录合同的
        if (Util::isMobile($content)) {
            return $this->tipForContract($content);
        }

        $customer   = Customermodel::where('openid', $message->FromUserName)->first();

        //如果数据库中无此人记录, 则回复默认的消息
        if (!$customer) {
            return $this->defaultTextResponse();
        }

        //查询是否有该用户的助力记录
        $record = Helprecordmodel::where('helper_id', $customer->id)->first();

        if (!$record) {
            return $this->defaultTextResponse();
        }

        //找出记录对应的活动和活动参与者, 进行下一步的操作
        $activity   = Activitymodel::find($record->activity_id);
        $friend     = Customermodel::find($record->customer_id);

        //活动结束, 不更新
        if ($activity->end_time->lte(Carbon::now())) {
            return new Text(['content' => '活动已经结束, 感谢您的参与!']);
        }

        //如果已经回复了好友姓名, 不更新
        if (!empty($record->remark)) {
            return $this->defaultTextResponse();
        }

        //如果用户已经领过优惠券, 不操作
        if ($friend AND 0 < $friend->coupons()->where('activity_id', $activity->id)->count()) {
            return $this->defaultTextResponse();
        }

        //更新助力记录
        $record->remark     = $content;
        $record->save();

        //检查是否达标
        $this->sendCouponAccquireMessage($friend, $activity, $app);

        return new Text(['content' => '您为好友助力成功, 感谢您的参与!']);
    }



    /**
     * 默认的文字回复
     */
    private function defaultTextResponse()
    {
        return new Text(['content' => '您的问题小草莓已收到，请稍等片刻，草莓就会回复你啦[爱心]']);
    }

    /**
     * 处理关注事件
     */
    private function subscribe()
    {
        try {
            Customermodel::where('openid', $this->openid)->update(['subscribe' => 1]);
            if (empty($this->eventKey)) {
                return $this->defaultSubscribeTextPush();
//                return $this->goToSweepstakes(config_item('new_customer_activity_id'));
            }

            return $this->scan();

        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            return new Text(['content' => '没有找到该记录!']);
        }
    }

    /**
     * 根据 media_id 获取图文素材
     * 如果素材是图文 , 返回的是数组
     */
    private function getNewsById($app, $material_id)
    {
        $res = $app->material->get($material_id);

        if (!is_array($res)) {
            return false;
        }

        foreach ($res['news_item'] as $news) {
            $message[] = new News([
                'title' => $news['title'],
                'url'   => $news['url'],
                'image' => $news['thumb_url'],
            ]);
        }

        return $message;
    }

    /**
     * 默认关注的信息推送
     */
    private function defaultSubscribeTextPush()
    {
        //$siteUrl    = site_url('/');

        return new Text([
            'content' => "Halo 、你好、萨瓦迪卡！\n我是莓子酱，感谢关注青年公寓品牌【金地草莓社区】\n\n【请回复数字编号，方便进入相关信息查询】\n1.我要租房\n2.草莓故事\n3.草莓作品\n4.近期活动\n\n如果以上没能解决您的问题，没关系，您的问题已反馈到微信后台，用不了多久小编就会回复你啦（比心）"
        ]);
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
//    public function menu(){
////        exit('Hello-Baby');
//
//        $this->load->helper('wechat');
//        $app    = new Application(getCustomerWechatConfig());
//        //echo $app->getToken();exit;
//        $menu   = $app->menu;
////        var_dump($menu->current());exit;
//
//        $url_resident_guide = 'https://mp.weixin.qq.com/s?__biz=MzI3MTMwODIyNw==&mid=2247484131&idx=2&sn=aed494e10935d13e9af15a73060df69e&chksm=eac2864fddb50f593a5787021f64f4dd668f2fb745d876d7698e835460e177478bbd88c2f444#rd';
//
//        $url_strawberry_market = 'https://mp.weixin.qq.com/s?__biz=MzI3MTMwODIyNw==&mid=2247484131&idx=1&sn=bd1eb5a51e848aded59d588abcb3d315&chksm=eac2864fddb50f59ab1d6140f7bbf678918e47d836e25607c7e9f247df24961bc369f22dc599#rd';
//
//        $buttons = [
//            [
//                'name'       => '关于草莓',
//                'sub_button' => [
//                    [
//                        'name' => '草莓作品',
//                        'type' => 'click',
//                        'key'  => 'STRAWBERRY_WORKS',
//                    ],
//                    [
//                        'name' => '草莓故事',
//                        'type' => 'click',
//                        'key'  => 'STRAWBERRY_STORIES',
//                    ],
//                    /*[
//                        'name' => '草莓公约',
//                        'type' => 'view',
//                        'url'  => $url_resident_guide,
//                    ],*/
//                    [
//                        'name' => '合作联系',
//                        'type' => 'click',
//                        'key'  => 'COOPERATE_AND_CONTACT',
//                    ],
//                    [
//                        'name' => '投诉信箱',
//                        'type' => 'click',
//                        'key'  => 'EMAIL_FOR_COMPLAINT',
//                    ],
//                ],
//            ],
////            [
////                'name'       => '预约看房',
////                'sub_button' => [
////                    [
////                        'name' => '找房源',
////                        'type' => 'view',
////                        'url'  => wechat_url(),
////                    ],
////                    [
////                        'name' => '近期活动',
////                        'type' => 'click',
////                        'key'  => 'RECENT_ACTIVITIES',
////                    ],
////                ],
////            ],
////            [
////                'name'       => '我是草莓',
////                'sub_button' => [
////                    [
////                        'name' => '个人中心',
////                        'type' => 'view',
////                        'url'  => wechat_url('center'),
////                    ],
////                    [
////                        'name' => '生活服务',
////                        'type' => 'view',
////                        'url'  => wechat_url('service'),
////                    ],
////                    [
////                        'name' => '金地商城',
////                        'type' => 'view',
////                        'url'  => wechat_url('shop'),
////                    ],
////                ],
////            ],
//        ];
//
//        var_dump($menu->add($buttons));
//
//    }

    public function menu()
    {
//        exit('Hello-Baby');

        $app    = new Application(getCustomerWechatConfig());
        $menu   = $app->menu;
//        var_dump($menu->current());exit;

        // 草莓公约
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
                    [
                        'name' => '草莓活动',
                        'type' => 'click',
                        'key'  => 'RECENT_ACTIVITIES',
                    ],
                    [
                        'name' => '草莓品味',
                        'type' => 'click',
                        'key'  => 'STRAWBERRY_SAVOUR',
                    ],
                ],
            ],
            [
                'name'       => '预约看房',
                'sub_button' => [
                    [
                        'name' => '找房源',
                        'type' => 'view',
                        'url'  => config_item('wechat_url'),
                    ],
                    [
                        'name' => '礼品登记',
                        'type' => 'view',
                        'url'  => 'http://cn.mikecrm.com/nrX0JyY',
                    ],
                    [
                        'name' => '合作联系',
                        'type' => 'click',
                        'key'  => 'COOPERATE_AND_CONTACT',
                    ],
                ],
            ],
            [
                'name'       => '我是草莓',
                'sub_button' => [
                    [
                        'name' => '个人中心',
                        'type' => 'view',
                        'url'  => config_item('wechat_url').'%23/userIndex',
                    ],
                    [
                        'name' => '生活服务',
                        'type' => 'view',
//                        'url'  => wechat_url('service'),
                        'url'  => config_item('wechat_url').'#/service',
                    ],
                    [
                        'name' => '金地商城',
                        'type' => 'view',
//                        'url'  => config_item('wechat_url').'shopping',
                        'url'  => 'http://web.strongberry.cn/#/shopping',
                    ],
                    [
                        'name' => '投诉信箱',
                        'type' => 'click',
                        'key'  => 'EMAIL_FOR_COMPLAINT',
                    ],
                ],
            ],

        ];
        log_message('error',config_item('wechat_url').'service');
        var_dump($menu->add($buttons));
    }






    private function checkInOrBookingEvent($message, $eventKey)
    //public function checkInOrBookingEvent($message='', $eventKey='')
    {
        //$loginUrl = site_url('login?target_url=');

        //办理入住以及预订房间时的场景值
        $this->load->model('residentmodel');  
        $this->load->model('ordermodel');  
        $this->load->model('roomunionmodel');  
        $this->load->model('roomtypemodel');
        $this->load->model('storemodel');

        //$eventKey=182;
        $resident   = Residentmodel::findOrFail($eventKey);

//        if (0 == $resident->uxid ) {
            try{
                DB::beginTransaction();
                if (0 == $resident->uxid ) {
                    $customer = Customermodel::where('openid', $message->FromUserName)->first();
                    //$customer   = Customermodel::where('openid', 1)->first();

                    if (empty($customer)) {
                        $customer = new Customermodel();
                        $customer->openid = $message->FromUserName;
                        $customer->company_id = 1;
                        //$customer->openid   =1;
//                    $customer->uxid         = Customermodel::max('uxid')+1;
                        $customer->uxid = $customer->max('id') + 1;
                        $customer->save();
                    }

                    $resident->customer_id = $customer->id;
                    $resident->uxid = $customer->uxid;
                    $resident->save();
                    $resident->orders()->where('uxid', 0)->update(['customer_id' => $customer->id, 'uxid' => $customer->uxid]);
                }
                DB::commit();
            }catch (Exception $e){
                log_message('error',$e->getMessage());
                DB::rollBack();
                throw  $e;
            }

//        }else{
//
//            return new Text(['content' => '该入住信息已经被确认']);
//        }
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
            $url    = 'http://tweb.funxdata.com/#/myBill';
        } else {
//            $url    = $loginUrl.site_url(['contract', 'preview', $resident->id]);
            $url    = 'tweb.funxdata.com/#/generates?resident_id='.$resident->id;
        }
        return new News(array(
            'title'         => $resident->roomunion->store->name,
            'description'   => "您预订的【{$resident->roomunion->number}】",
            'url'           => $url,
            'image'         => $this->fullAliossUrl(json_decode($resident->roomunion->roomtype->images,true),true),
        ));
    }


}
