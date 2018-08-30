<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */
class MY_Controller extends CI_Controller {
    const GET_ACCESS_TOKEN = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=";
    const GET_TICKET       = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=";
    public function __construct() {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    //API返回统一方法
    public function api_res($code, $data = false) {
        switch ($code) {
            case 0:
                break;
            case 1002:
            case 1004:
            case 1005:
            case 10008:
                $this->output->set_status_header(400);
                break;
            case 1001:
            case 1006:
            case 11005:
                $this->output->set_status_header(401);
                break;
            case 1011:
            case 10011:
                $this->output->set_status_header(403);
                break;
            default:
                $this->output->set_status_header(500);
                break;
        }

        $msg = $this->config->item('api_code')[$code];
        if ($data) {
            $this->output
                ->set_output(json_encode(array('rescode' => $code, 'resmsg' => $msg, 'data' => $data)));
        } else {
            $this->output
                ->set_output(json_encode(array('rescode' => $code, 'resmsg' => $msg, 'data' => [])));
        }
        
    }

    /**
     *  $url         curl请求的网址
     *  $type        curl请求的方式，默认get
     *  $res        curl 是否把返回的json数据转换成数组
     *  $arr        curl post传递的数据
     */
    public function httpCurl($url, $method = 'get', $res = '', $arr = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_REFERER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type: application/x-www-form-urlencoded;charset=UTF-8"));
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true); // 开启post提交
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr); //post 数据  http_build_query($data)
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //CURLOPT_SSL_VERIFYHOST 设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            echo curl_error($ch);
            curl_close($ch);
            return false;
        } else {
            $encode = mb_detect_encoding($output, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
            if ($encode !== "UTF-8") {
                $output = mb_convert_encoding($output, "UTF-8", $encode);
            }
            if ($res == 'json') {
                $output = json_decode($output, true);
            }
            curl_close($ch);
            return $output;
        }
    }

    /**
     * 内部服务调用
     * $func       curl请求的方法，如 index/index
     * $form       curl请求的表单内容
     */
    public function internalCurl($func, $form = '') {
        $apikey    = config_item('internal_api_key');
        $apisecret = config_item('internal_api_secret');
        $apiurl    = config_item('internal_api_url') . 'innserservice/' . $func;

        $timestamp   = time();
        $hash        = $apihash        = hash('sha256', "$apikey.$timestamp.$apisecret");
        $x_api_token = "$apikey.$timestamp.$hash";

        //调用curl
        $header[0] = "x-api-token: $x_api_token";
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiurl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true); // 开启post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $form); //post 数据  http_build_query($data)

        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            log_message("error", "internal curl $func failed, " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        $output = json_decode($output, true);
        curl_close($ch);
        return $output;
    }

    /**
     * 表单验证
     * 传入config数组
     * config数组中包含 field 和config 两个类型
     * example $config=['filed'=>['a','b','c'],'config'=>[['field'=>'a'....],['field'=>'b'....]]]
     */
    public function validationText($config, $data = []) {
        if (!empty($data) && is_array($data)) {
            $this->load->library('form_validation');
            $this->form_validation->set_data($data)->set_rules($config);
            if (!$this->form_validation->run()) {
                return false;
            } else {
                return true;
            }
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules($config);
        if (!$this->form_validation->run()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param array $fieldarr 传入验证字段的数组
     * @return string 返回验证表单的第一个错误信息方便 ajax返回
     */
    public function form_first_error($fieldarr = []) {
        if (FALSE === ($OBJ = &_get_validation_object())) {
            return '';
        }
        foreach ($fieldarr as $field) {
            if ($OBJ->error($field)) {
                return $OBJ->error($field, NULL, NULL);
            }
        }
    }

    /**
     * 将alioss路径 拼接成完整的URL
     */
    public function fullAliossUrl($oss_path, $bool = false) {
        if ($bool == true && is_array($oss_path)) {
            foreach ($oss_path as $path) {
                $full_url[] = config_item('cdn_path') . $path;
            }
        } else {
            $full_url = config_item('cdn_path') . $oss_path;
        }
        return $full_url;
    }

    /**
     * 对上传的文件url拆解成alioss路径
     * true传入数组 对数组进行遍历拆解
     */
    public function splitAliossUrl($full_path, $bool = false) {
        if ($bool == true) {
            $alioss_path = [];
            foreach ($full_path as $path) {
                $split = substr($path, strlen(config_item('cdn_path')));
                if (!$split) {
                    throw new Exception('截取url失败');
                }
                $alioss_path[] = $split;
            }
        } else {
            if (!$alioss_path = substr($full_path, strlen(config_item('cdn_path')))) {
                throw new Exception('截取url失败');
            }
        }
        return $alioss_path;
    }

    /**
     * 核对当前操作用户
     */
    /*  public function checkUser($uxid){
    if($uxid!=CURRENT_ID){
    return flase;
    //            throw new Exception('核对当前操作用户异常');
    }else{
    return true;
    }
    }*/
    /*
     * 获取access_token
     */
    public function get_access_token($appid, $secret) {
        $access_token = self::GET_ACCESS_TOKEN . $appid . "&secret=" . $secret;
        $ticket       = json_decode($this->httpCurl($access_token));
        $jsapi_ticket = $this->get_ticket($ticket->access_token);
        return $jsapi_ticket;
    }
    /*
     *获取ticket
     * */
    protected function get_ticket($access_token) {
        $jsapi_ticket = json_decode($this->httpCurl(self::GET_TICKET . $access_token . "&type=jsapi"));
        return $jsapi_ticket->ticket;
    }
    /*
     * 获取随机字符串
     * */
    public function random_str() {
        $chars    = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = "";
        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
