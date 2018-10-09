<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\News;
use EasyWeChat\Message\Text;
use EasyWeChat\Message\Material;
use EasyWeChat\Message\Image;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Intervention\Image\ImageManager;
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
                                    return new Text(['content' => '投诉/建议，请发送邮件至' . "\n" . 'liuxiaofen1@gemdalepi.com']);
                                    break;

                                case 'RECENT_ACTIVITIES':
                                    return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-Llp1Ehaiw4DEZET0lbvOrD88');
                                    break;

                                case 'STRAWBERRY_SAVOUR':
                                    return $this->getNewsById($app, 'DI4QPqKm4hfeBNuMD4-LlvCCLPa_p4Vie9diY2Z-2aM');
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
                        case 'WIFI':
                            return new Text(['content' => '点击<a href="http://wportal.tpauth.cn:8080/portal/wechat_auth/?token=48b9d3b1 ">免费上网</a>']);
                            break;
                        case '练习生':
                            return new Text(['content' => '点击提交信息http://cn.mikecrm.com/Ef2NIao ，即刻加入草莓练习生计划']);
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
        //查看好友助力表里有没有助力记录
        $this->load->model('activitymodel');
        $this->load->model('attractcustomerprizemodel');
        $this->load->model('attractprizemodel');
        $this->load->model('attractrecordmodel');
        $this->load->model('customermodel');

        $customer   = Customermodel::where('openid', $message->FromUserName)->first();
        if(!$customer){
            return $this->defaultTextResponse();
        }

        //查看有没有参加吸粉活动
        $attract_record = Attractrecordmodel::where('customer_id',$customer->id)->first();
        if(!$attract_record){
            return $this->defaultTextResponse();
        }
        //如果已经回复过
        if($attract_record->valid){
            return $this->defaultTextResponse();
        }
        //查看活动是否结束
        $activity   = Activitymodel::find($attract_record->activity_id);
        if (empty($activity) || $activity->end_time->lt(Carbon::now()) || $activity->status!=Activitymodel::TYPE_NORMAL) {
            return new Text(['content' => '该活动已经结束, 感谢您的参与!']);
        }
        //记录为有效值
        if(empty($content)){
            return new Text(['content' => '请回复有效信息才可记为有效助力!']);
        }
        try{
            DB::beginTransaction();
            //更新记录信息
            $record = $this->validAttractRecord($attract_record,$content);
            //查看是否达标
            $friend_id  = $record->friend_id;
            $friend = Customermodel::find($friend_id);
            if(!empty($friend)){
                $this->handleAttractFriend($friend,$activity,$app);
            }

            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
        }
        return new Text(['content' => '您为好友助力成功, 感谢您的参与!']);
    }

    /**
     * @param $friend
     * @param $activity
     * @param $app
     * @return bool
     * @throws Exception
     * 处理
     */
    private function handleAttractFriend($friend,$activity,$app){
        if (!$friend) {
            return false;
        }
        //查询用户的助力数, 如果达到一定数量, 发送模板消息
        $helpCnt    = Attractrecordmodel::where('friend_id',$friend->id)
            ->where('is_valid',1)
            ->where('activity_id',$activity->id)
            ->count();
        $limit  = $activity->data['limit'];
        if(in_array($helpCnt,$limit)) {
            $attract_prize = Attractprizemodel::where('activity_id', $activity->id)
                ->where('limit', $helpCnt)->first();
            //剩余奖品数
            $remain = $attract_prize->count - $attract_prize->sent;
            //查看奖品剩余数量大于0
            if ($remain > 0) {

                //如果剩余奖品小于一次发放的数量,那么一次性都发完
                if ($remain <= $activity->single) {
                    $send_cnt = $remain;
                    $attract_prize->sent = $attract_prize + $send_cnt;
                    $attract_prize->status = Attractprizemodel::STATE_EMPTY;
                } else {
                    $send_cnt = $activity->single;
                    $attract_prize->sent = $attract_prize + $send_cnt;
                }
                $attract_prize->save();
                //发放优惠券
                if ($this->attractSendCoupons($activity, $friend, $attract_prize, $send_cnt)) {
                    //推送消息
                    DB::commit();
                    $this->attractSendCouponsMessage($friend, $activity, $attract_prize, $app);
                }

            } else {
                //查看状态是否是empty
                if ($attract_prize->status != Attractprizemodel::STATE_EMPTY) {
                    $attract_prize->status = Attractprizemodel::STATE_EMPTY;
                    $attract_prize->save();
                }
                //看别的奖品发完了吗？如果都发完了则活动结束
                //do something
            }
        }
    }

    /**
     * 吸粉活动发送优惠券
     */
    private function attractSendCoupons($activity,$customer,$prize,$send_cnt)
    {
        //记录用户优惠券信息
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $coupontype = Coupontypemodel::find($prize->coupontype_id);
        if(empty($coupontype)){
            return false;
        }
        $coupon = [];
        for($cnt=$send_cnt;$cnt>0;$cnt--){
            $coupon[]   = [
                'customer_id'   => $customer->id,
                'resident_id'   => 0,
                'activity_id'   => $activity->id,
                'coupon_type_id'=> $prize->coupontype_id,
                'status'        => Couponmodel::STATUS_UNUSED,
                'deadline'      => $coupontype->deadline
            ];
        }
        Couponmodel::insert($coupon);

        //记录用户获奖信息
        $customer_prize = new Attractcustomerprizemodel();
        $customer_prize->customer_id    = $customer->id;
        $customer_prize->activity_id    = $activity->id;
        $customer_prize->prize_id       = $prize->id;
        $customer_prize->data   = [
            'activity'  => $activity->toArray(),
            'prize'     => $prize->toArray(),
            'send_cnt'  => $send_cnt,
        ];
        $customer_prize->save();
        return true;
    }

    /**
     * 吸粉活动发送获得优惠消息
     */
    private function attractSendCouponsMessage($customer,$activity,$prize,$app)
    {
        $tplMessage = array(
            'first'     => "恭喜您，邀请{$prize->limit}人助力的目标达成啦！",
            'keyword1'  => $activity->name,
            'keyword2'  => "您已经完成{$prize->name}人助力者的邀请，系统将于5个工作日内识别粉丝并发放抵扣券!",
//            'remark'    => '请点击详情，领取莓子酱的见面礼！',
            'remark'    => '',
        );
        $app->notice
            ->uses(config_item('tmplmsg_customer_tasknotify'))
//            ->withUrl(site_url("coupon/acquire?activity_id={$activity->id}"))
            ->andData($tplMessage)
            ->andReceiver($customer->openid)
            ->send();

    }


    /**
     * @param $record
     * @param $remark
     * @return mixed
     * 用户回复之后记录为有效助力
     */
    private function validAttractRecord($record,$remark){
        $record->is_valid   = 1;
        $record->remark = $remark;
        $record->save();
        return $record;
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
                //发送优惠券
//                $this->sendCoupon();

                return $this->defaultSubscribeTextPush();
//              return $this->goToSweepstakes(config_item('new_customer_activity_id'));
            }

            return $this->scan();

        } catch (Exception $e) {
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
     * 吸粉活动的扫码助力
     */
    private function helpFriend($app, $message, $sceneId)
    {
        $this->load->model('activitymodel');
        $this->load->model('attractcustomerprizemodel');
        $this->load->model('attractprizemodel');
        $this->load->model('attractrecordmodel');
        //根据场景值解析出优惠活动的 id 和朋友的 id
        $activityId = (int)substr($sceneId, 1, 3);
        $friendId   = (int)substr($sceneId, 4, 6);

        //查询活动, 看活动是否结束, 如果结束, 则返回
        $activity   = Activitymodel::find($activityId);
        if (empty($activity) || $activity->end_time->lt(Carbon::now()) || $activity->status!=Activitymodel::TYPE_NORMAL) {
            return new Text(['content' => '该活动已经结束, 欢迎您关注莓子酱哟!']);
        }

        //获取扫码用户的信息, 只有是新增的粉丝才可以, 如果之前数据库中存在，则不是新关注的用户
        $myInfo = $app->user->get($message->FromUserName);
        $myself = Customermodel::where('openid', $message->FromUserName)->first();
        if (!$myself) {
            $myself = new Customermodel();
            $myself->openid     = $myInfo->openid;
            $myself->nickname   = $myInfo->nickname;
            $myself->avatar     = $myInfo->headimgurl;
            $myself->unionid    = $myInfo->unionid;
            $myself->save();

            //如果扫的是好友海报图上的二维码, 计为有效助力
            if (0 != $friendId) {
                $friend     = Customermodel::find($friendId);
                $this->storeHelpRecord($friend, $myself, $activity, $app);
            }
        }

        //通过扫描好友二维码并且是关注事件, 推送默认关注消息
        if (0 != $friendId && 'event' == $message->MsgType && 'subscribe' == $message->Event) {
            return $this->defaultSubscribeTextPush();
        }

        //如果是非关注扫活动二维码, 就发活动海报

        $textMessageStr = "感谢关注金地商置青年公寓品牌【火花草莓社区】，莓子酱将为您奉上专属福利，您只需完成下面的步骤即可获得：\n\n1.转发好友助力卡，邀请好友扫描二维码完成关注，并回复租户名字到后台即可；\n2.成功邀请30位好友可获得100元房租抵扣券，成功邀请60位好友可获得160元房租抵扣券，成功邀请100位好友可获得260元房租抵扣券；\n3.完成好友关注后，后台将会自动识别有效粉丝并于5个工作日内发放抵扣券，此抵扣券仅限租约次月使用，获得2张及以上抵扣券的需在合约次月开始后的三个月内使用完毕，每月仅限一张；\n4.活动期间每位用户只能领取一次优惠券，仅限签约半年及以上用户使用；\n5.此券仅限本人使用，不兑现，不可转让。";

        $post = $this->generatePost($app, $activity, $myInfo, $myself->id);
        $text = new Text(['content' => $textMessageStr]);

        //多条消息通过客服消息的方式发出去, 先发送文字
        $staff = $app->staff;
        $staff->message($text)->to($myself->openid)->send();

        //回复海报图片消息
        return $post;
    }

    private function generatePost($app, $activity, $myInfo, $customerId)
    {
        //处理背景图
        $this->handleActivityBackPath($activity);

        //生成响应的工具实例, 用于获取和处理图片, 用 file_get_contents 函数无法获取用户头像, 原因暂时未知
        $httpClient = new Client();
        $imgManager = new ImageManager(array('dirver' => 'gd'));

        //为扫码用户分配表征该用户身份的场景值, 用于生成临时二维码
        $sceneId    = sprintf("1%03d%06d", $activity->id, $customerId);

        //生成带有场景值的临时二维码, 该二维码的有效期是30天
        $qrcode     = $app->qrcode;
        $result     = $qrcode->temporary($sceneId, 30 * 24 * 3600);
        $qrcodeUrl  = $qrcode->url($result->ticket);

        //根据url获取图片的数据, 头像使用小头像
        $imgQrCode  = $httpClient->request('GET', $qrcodeUrl)->getBody()->getContents();
        // $imgAvatar  = $httpClient->request('GET', substr_replace($myInfo->headimgurl, '64', -1, 1))->getBody()->getContents();
        $imgAvatar  = $httpClient->request('GET', $myInfo->headimgurl)->getBody()->getContents();

        //生成画布, 同时将获取的头像和二维码缩放到指定尺寸, BIA到画布上.
        $canvas     = $imgManager->canvas(540, 960);
        //$imgBG      = $imgManager->make(FCPATH."attachment/coupon_bg_v2_{$activity->id}.png")->resize(1080, 1920);
        $imgBG      = $imgManager->make($activity->back_path)->resize(540, 960);
        $imgAvatar  = $imgManager->make($imgAvatar)->resize(79, 79);
        $imgQrCode  = $imgManager->make($imgQrCode)->resize(117, 117);

        //不同的海报, 二维码的位置不一样
        $offsetX    = 54;
        $offsetY    = 150;

        //原富杨店的海报与其余的有不同, 活动重新开始后, 与其余店相同, 本行注释.
        // if (3 == $activity->id) {
        //     $offsetY    = 250;
        // }

        //文件的临时路径, 上传到微信服务器需要该路径值.
        $tmpImgPath = FCPATH."temp/{$myInfo->openid}.png";
        $canvas->insert($imgBG)
            ->insert($imgAvatar, 'top-left', 88, 212)
            ->insert($imgQrCode, 'bottom-right', $offsetX, $offsetY)
            ->text(mb_convert_encoding($myInfo->nickname, "html-entities", "utf-8"), 190, 269, function($font) {
                $font->file(FCPATH.'fonts/simfang.ttf');
                $font->size(18);
                $font->color('#fff');
            })
            ->save($tmpImgPath,60);

        //将处理完的图片作为临时素材上传到微信服务器, 获得该图片的media_id
        $resUpload  = $app->material_temporary->uploadImage($tmpImgPath);

        // 上传完成后删除图片
        unlink($tmpImgPath);

        return new Image(['media_id' => $resUpload->media_id]);
    }

    /**
     * @param $activity
     * 处理吸粉活动的背景图
     */
    private function handleActivityBackPath($activity)
    {
        if(empty($activity->back_path)){
            $image_path = $this->downloadAttractImage($this->fullAliossUrl($activity->back_url));
            $activity->back_path    = $image_path;
            $activity->save();
            return;
        }
        if(!file_exists(realpath($activity->back_path))){
            $image_path = $this->downloadAttractImage($this->fullAliossUrl($activity->back_url));
            $activity->back_path    = $image_path;
            $activity->save();
            return;
        }
    }

    /**
     * @param $url
     * @return string
     * @throws Exception
     * 把吸粉活动对应的图片下载到本地
     */
    private function downloadAttractImage($url)
    {
        $path   = APPPATH.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'attract'.DIRECTORY_SEPARATOR.date('Y-m-d',time()).DIRECTORY_SEPARATOR;
        $pathinfo   = pathinfo($url);
        $filename   = $pathinfo['filename'].rand(10,99).'.'.$pathinfo['extension'];
        $fullpath   = $path.$filename;
        if (!is_dir(APPPATH.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'attract')) {
            if (!mkdir(APPPATH.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'attract',0777)) {
                throw new Exception('无法创建目录, 请稍后重试');
            }
        }
        if (!is_dir($path)) {
            if (!mkdir($path, 0777)) {
                throw new Exception('无法创建目录, 请稍后重试');
            }
        }
        $file   = file_get_contents($url);
        file_put_contents($fullpath,$file,0777);
        return $fullpath;
    }



    /**
     * 处理吸粉活动扫码后的记录及相关动作
     */
    private function storeHelpRecord($friend, $myself, $activity, $app)
    {
        $friend_record  = Attractrecordmodel::where('activity_id',$activity->id)
            ->where('customer_id',$friend->id)->first();
        if (empty($friend)||empty($friend_record)) {
            $friend_id  = 0;
        } else {
            $friend_id  = $friend->id;
        }

        $record = new Attractrecordmodel();
        $record->activity_id    = $activity->id;
        $record->customer_id    = $myself->id;
        $record->friend_id      = $friend_id;
        $record->save();
        return $record;

    }

    /**
     * 生成菜单
     */
    public function menu()
    {
        exit;
        $app    = new Application(getCustomerWechatConfig());
        $menu   = $app->menu;
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
                        'url'  => config_item('wechat_url').'#/index',
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
                        'url'  => config_item('wechat_url').'#/userIndex',
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
                        'url'  => config_item('wechat_url').'#/shopping',
                    ],
                    [
                        'name' => '投诉信箱',
                        'type' => 'click',
                        'key'  => 'EMAIL_FOR_COMPLAINT',
                    ],
                ],
            ],

        ];
        log_message('debug',config_item('wechat_url').'service');
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
                        $userService = $this->app->user;
                        $user = $userService->get($message->FromUserName);
                        $unionid = $user->unionid;

                        $customer = new Customermodel();
                        $customer->openid = $message->FromUserName;
                        $customer->unionid = $unionid;
                        $customer->company_id = 1;
                        $customer->subscribe  = 1;
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
        //如果是预定用户跳转到预定签合同页面
        if ($resident->reserve_contract_time>0 && $resident->contract_time==0) {
            $url    = config_item('wechat_url').'#/reservationContract?resident_id='.$resident->id;
        } else {
            $url    = config_item('wechat_url').'#/generates?resident_id='.$resident->id;
        }
        return new News(array(
            'title'         => $resident->roomunion->store->name,
            'description'   => "您预订的【{$resident->roomunion->number}】",
            'url'           => $url,
            'image'         => $this->fullAliossUrl(json_decode($resident->roomunion->roomtype->images,true),true),
        ));
    }


    /*发送优惠券*/

    private function sendCoupon(){

        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');

        //判断用户是否发送过对应的优惠券
        $customer = Customermodel::where('openid',$this->openid)->first();
        if(isset($customer)||!empty($customer)){
            $data = ['customer_id'=>$customer->id,
                'coupon_type_id'=>39
            ];
//
//            //判断这个用户是否有优惠券gir
            $sum =  Couponmodel::where($data)->get()->count();
            if($sum==0){

//                //发送优惠券
                $coupon = Coupontypemodel::where('id',39)->first();
                $update_coupon = [
                    'customer_id'=>$customer->id,
                    'coupon_type_id' => 39,
                    'status' => 'unused',
                    'deadline' => $coupon->deadline
                ];
                $activity = new Couponmodel();
                $activity->fill($update_coupon);
                $activity->save();
//                //发送二维码
            }
        }


    }


}
