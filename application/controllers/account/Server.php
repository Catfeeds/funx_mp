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

class Server extends Wechat
{
    //protected $app;
    protected $message;
    protected $openid;
    protected $eventKey;
    protected $event;

    public function __construct()
    {
        parent::__construct();

        //$this->app = new Application($this->getCustomerWechatConfig());
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


}
