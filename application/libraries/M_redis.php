<?php

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/2
 * Time:        21:13
 * Describe:    使用原生redis，扩展方法写在这里  此文件在autoload自动加载
 */

class M_redis
{
    public $redis;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('redis', TRUE);
 
        $this->redis    = new Redis();
        $this->redis->connect($CI->config->item('redis')['host'],
                                $CI->config->item('redis')['port'],
                                $CI->config->item('redis')['timeout']);

        $this->redis->auth($CI->config->item('redis')['password']);

    }


    /**
     * redis中存储短信验证码
     * @param $phone int    手机号
     * @param $code int     短信验证码
     * return   bool
     */
    public function storeSmsCode($phone,$code){
        $key    = PHONECODE.$phone;
        $val    = $code;
        $this->redis->set($key,$val,600);
        return;
    }

    /**
     * 验证手机验证码
     * @param $phone int    手机号
     * @param $code int     短信验证码
     * return   bool
     */
    public function verifySmsCode($phone,$code){
        $key    = PHONECODE.$phone;
        if($code != $this->redis->get($key)){
            return false;
        }
        $this->redis->expire($key,-1);
        return true;
    }

    /**
     * 刷新短信验证码
     * @param $phone
     * return bool
     */
    public function ttlSmsCode($phone){
        $key    = PHONECODE.$phone;
        if( $this->redis->exists($key) ){
            if( ($this->redis->ttl($key))>540) {
                return false;
            }
        }
        return true;
    }


    /**
     * 存储用户信息
     * @param $bxid
     * @param $user_info
     */
    public function storeUserInfo($bxid,$user_info){
        $key    = USERINFO.$bxid;
        $this->redis->set($key,$user_info,2*60*60);
        return;
    }

    /**
     * 获取用户信息
     * @param $bxid
     */
    public function getUserInfo($bxid){
        $key    = USERINFO.$bxid;
        $user   = $this->redis->get($key);
        return $user;
    }

    /**
     * 刷新用户信息
     */


    /**
     * 存储公司信息
     * @param $company_id int 公司id
     * @param $json_info    string 公司信息
     *  return bool
     */
    public function storeCompanyInfo($company_id,$json_info){

        $key    = COMPANYINFO.$company_id;
        $this->redis->set($key,$json_info,2*60*60);
        return;
    }

    /**
     * 获取公司信息
     * @param $company_id int 公司id
     * @param $bool boolean 获取的同时是否刷新redis过期时间
     *  return $info    json格式的公司信息
     */
    public function getCompanyInfo($company_id,$bool=false){

        $key    = COMPANYINFO.$company_id;
        $info   = $this->redis->get($key);
        if(true==$bool && $info){
            $this->redis->expire($key,2*60*60);
        }
        return $info;
    }


    /**
     * 存储公司权限
     * @param $company_id
     * @param $privilege
     */
    public function storePrivilege($company_id,$privilege){
        $key    = COMPANYPRIVILEGE.$company_id;
        $this->redis->set($key,$privilege,2*60*60);
        return;
    }

    /**
     * 获取公司权限
     * @param $company_id
     */
    public function getPrivilege($company_id){
        $key    = COMPANYPRIVILEGE.$company_id;
        $privilege  = $this->redis->get($key);
        return $privilege;
    }

    /**
     * 存储客户信息
     */
    public function storeCustomerInfo($uxid,$info){
        $key    = CUSTOMERINFO.$uxid;
        $this->redis->set($key,$info,2*60*60);
        return;
    }

    /**
     * 获取客户信息
     */
    public function getCustomerInfo($uxid){
        $key    = CUSTOMERINFO.$uxid;
        $info   = $this->redis->get($key);
        return $info;
    }

    /*
     * 住户端 个人中心绑定手机号时存储验证码
     */
    public function storeCustomerPhoneCode($phone,$code){
        $key    = CUSTOMERPHONECODE.$phone;
        $val    = $code;
        $this->redis->set($key,$val,600);
        return;
    }

    /**
     * 住户端 绑定手机号验证个人手机号
     */
    public function verifyCustomerPhoneCode($phone,$code){
        $key    = CUSTOMERPHONECODE.$phone;
        if($code != $this->redis->get($key)){
            return false;
        }
        $this->redis->expire($key,-1);
        return true;
    }

    /**
     * 住户端 个人中心绑定手机号 刷新短信验证码
     * @param $phone
     * return bool
     */
    public function ttlCustomerPhoneCode($phone){
        $key    = CUSTOMERPHONECODE.$phone;
        if( $this->redis->exists($key) ){
            if( ($this->redis->ttl($key))>540) {
                return false;
            }
        }
        return true;
    }

    /**
     * 住户端 住户确认合同时存储手机验证码
     */
    public function storeResidentPhoneCode($phone,$code){
        $key    = RESIDENTPHONECODE.$phone;
        $val    = $code;
        $this->redis->set($key,$val,600);
        return;
    }

    /**
     * 住户端 住户确认合同时验证手机验证码
     */
    public function verifyResidentPhoneCode($phone,$code){
        $key    = RESIDENTPHONECODE.$phone;
        if($code != $this->redis->get($key)){
            return false;
        }
        $this->redis->expire($key,-1);
        return true;
    }

    /**
     * 住户端 住户确认合同时刷新短信验证码
     * @param $phone
     * return bool
     */
    public function ttlResidentPhoneCode($phone){
        $key    = RESIDENTPHONECODE.$phone;
        if( $this->redis->exists($key) ){
            if( ($this->redis->ttl($key))>540) {
                return false;
            }
        }
        return true;
    }

    /**
     * 存储danbytoken
     */
    public function storeDanbyToken($token){
        $key    = DANBYTOKEN;
        $this->redis->set($key,$token,30*60);
        return true;
    }

    /**
     * 获取danby Token
     */
    public function getDanBYToken(){
        $key    = DANBYTOKEN;
        $token  = $this->redis->get($key);
        return $token;
    }
    /*
 * 存储jsapi_ticket
 * */
    public function setjsapi_ticket($token){
        $key   = JSPAPI_TICKET;
        $this->redis->set($key,$token,2*60*60);
        return true;
    }
    /*
     * 获取jsapi_ticket
     * */
    public function getjsapi_ticket(){
        $key   = JSPAPI_TICKET;
        $token  = $this->redis->get($key);
        return $token;
    }
	
	/*
	 * 获取jsapi_ticket
	 * */
	public function getAccessToken(){
		$key    = ACCESSTOKEN;
		$token  = $this->redis->get($key);
		return $token;
	}
}