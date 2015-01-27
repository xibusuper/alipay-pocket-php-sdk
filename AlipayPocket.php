<?php
require_once './alipay/AopSdk.php';
require_once './alipay/HttpRequst.php';

/**
 * 支付宝钱包服务窗平台SDK
 * @author justsolive
 * @link https://github.com/justsolive/alipay-pocket-php-sdk.git
 * @version 1.0
 * usage:
 *     $options = array(
 *         'app_id' => "201408***008041",//填写支付宝服务窗app_id
 *         '_cache_base_path' => '';    //填写缓存文件路径
 *     )
 *     $pocket = new AlipayPocket($options);
 *     $service = $AlipayPocket->getService();
 *
 *     $pocket->rsaCheck(); //收到请求，先验证签名
 *
 *      if ($service == "alipay.service.check") {
 *          $pocket->verifygw(); //验证网关请求
 *      } else if ($service == "alipay.mobile.public.message.notify") {
 *          //处理收到的消息
 *
 *          $alipaypocket_user_id = $pocket->getFromUserId();
 *          $content = $pocket->getContent();
 *          $MsgType = $pocket->getMsgType();
 *          $EventType = $pocket->getEventType();
 *
 *          if ($MsgType == "text") {
 *              //$this->alipaypocket->log($content);
 *              $pocket->text("hello world");
 *          }elseif($MsgType == "event"){
 *              switch ($EventType) {
 *                  case "follow":   //关注事件
 *                      break;
 *                  case "unfollow": //取消关注
 *                      break;
 *                  case "enter":    //进入服务窗
 *                      break; 
 *                  case "click":    //点击事件
 *                      break;
 *                  default : 
 *                      break;             
 *              }
 *          }
 *      } 
 */
class AlipayPocket {

    private $_receive;

    public function __construct($options) {
        $this->config = array(
            'alipay_public_key_file' => dirname(__FILE__) . "/alipay_rsa_public_key.pem",
            'merchant_private_key_file' => dirname(__FILE__) . "/rsa_private_key.pem",
            'merchant_public_key_file' => dirname(__FILE__) . "/rsa_public_key.pem",
            'charset' => "GBK",
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            'app_id' => $options['app_id']
            '_cache_base_path' => $options['_cache_base_path']
        );

        $this->as = new AlipaySign();
        $this->gw = new Gateway();
        $this->push = new PushMsg();

        $this->httprequest = new HttpRequest();
        $this->aop = new AopClient ();
        $this->aop->appId = $options['app_id'];
        $this->aop->rsaPrivateKeyFilePath = $this->config['merchant_private_key_file'];
    }

    /**
     * 收到请求，先验证签名
     * @return string
     */
    public function rsaCheck() {
        if (!$this->getSign() || !$this->getSignType() || !$this->getRev() || !$this->getService() || !$this->getCharset()) {
            echo "some parameter is empty.";
            exit();
        }


        $sign_verify = $this->as->rsaCheckV2($_REQUEST, $this->config['alipay_public_key_file']);
        //$this->log("sfsdfds");
        if (!$sign_verify) {
            echo "sign verfiy fail.";
            exit();
        }
    }

    /**
     * 验证网关
     */
    public function verifygw() {
        $this->gw->verifygw();
    }

    /**
     * 获取签名
     * @return type
     */
    public function getSign() {
        return $this->httprequest->getRequest("sign");
    }

    /**
     * 获取签名类型
     * @return type
     */
    public function getSignType() {
        return $this->httprequest->getRequest("sign_type");
    }

    /**
     * 获取api服务名称
     * @return type
     */
    public function getService() {
        return $this->httprequest->getRequest("service");
    }

    /**
     * 获取编码
     * @return type
     */
    public function getCharset() {
        return $this->httprequest->getRequest("charset");
    }

    /**
     * 获取支付宝服务器发来的信息
     */
    public function getRev() {
        if ($this->_receive)
            return $this->_receive;
        $biz_content = $this->httprequest->getRequest("biz_content");

        if (!empty($biz_content)) {
            $this->_receive = $biz_content;
        }

        return $this->_receive;
    }

    /**
     * 获取输入内容
     * @return type
     */
    public function getContent() {
        return $this->getNode($this->getRev(), "Content");
    }

    public function getUserInfo() {
        return $this->getNode($this->getRev(), "UserInfo");
    }

    public function getFromUserId() {
        return $this->getNode($this->getRev(), "FromUserId");
    }

    public function getCreateTime() {
        return $this->getNode($this->getRev(), "CreateTime");
    }

    /**
     * 获取输入信息类型
     * @return type
     */
    public function getMsgType() {
        return $this->getNode($this->getRev(), "MsgType");
    }

    /**
     * 获取事件类型
     * @return type
     */
    public function getEventType() {
        return $this->getNode($this->getRev(), "EventType");
    }

    public function getAgreementId() {
        return $this->getNode($this->getRev(), "AgreementId");
    }

    public function getActionParam() {
        return $this->getNode($this->getRev(), "ActionParam");
    }

    public function getAccountNo() {
        return $this->getNode($this->getRev(), "AccountNo");
    }

    /**
     * 获取用户地理位置经纬度
     * @param type $userId
     * @return type
     */
    public function getUserLocation($user_id = null) {
        $result = array();
        $gisgetrequest = new \AlipayMobilePublicGisGetRequest();
        if ($user_id) {
            $bizContent['userId'] = $user_id;
        } else {
            $bizContent['userId'] = $this->getFromUserId();
        }

        $gisgetrequest->setBizContent(json_encode($bizContent));


        $gis_result = $this->aop->execute($gisgetrequest);
        $gis_obj = $gis_result->alipay_mobile_public_gis_get_response;
        $code = $gis_obj->code;
        $longitude = $gis_obj->longitude;  //经度
        $latitude = $gis_obj->latitude;    //维度
        $accuracy = $gis_obj->accuracy;    //精确度
        $province = $gis_obj->province;    //省份
        $city = $gis_obj->city;            //城市
        if ($code == 200) {
            $result = array('longitude' => $longitude, 'latitude' => $latitude);
        }

        return $result;
    }

    /**
     * 回复文本消息
     * @param type $msg
     */
    public function text($msg = '') {
        $text_msg = $this->push->mkTextMsg($msg);
        // 发给这个关注的用户
        $biz_content = $this->push->mkTextBizContent($this->getFromUserId(), $text_msg);
        $return_msg = $this->push->sendRequest($biz_content);
        $this->log("0" . $return_msg);
    }

    /**
     * 回复图文消息
     * @param array $newsData 
     * $newsData = array(
     *     [0] => array(
     *         'Title' => "",
     *         'Description' => "",
     *         'Url' => "",
     *         'PicUrl' => "",
     *     ),
     *     [1] => array(
     *         'Title' => "",
     *         'Description' => "",
     *         'Url' => "",
     *         'PicUrl' => "",
     *     ), 
     * )
     */
    public function news($newsData = array()) {
        $image_text_msg = array();
        for ($i = 0; $i < count($newsData); $i++) {
            $image_text_msg[] = $this->push->mkImageTextMsg($newsData[$i]['Title'], $newsData[$i]['Description'], $newsData[$i]['Url'], $newsData[$i]['PicUrl'], "loginAuth");
        }
        // 发给这个关注的用户
        $biz_content = $this->push->mkImageTextBizContent($this->getFromUserId(), $image_text_msg);
        $return_msg = $this->push->sendMsgRequest($biz_content);
    }

    /**
     * 获取access_token
     * @param type $auth_code
     * @return string
     */
    public function getAccessToken($auth_code) {
        $cachename = 'access_token_'.$this->config['app_id'];
        //get access token from cache
        $access_token = $this->getCache($cachename);
        if($access_token){
            return $access_token;
        }

        $tokenrequest = new AlipaySystemOauthTokenRequest();
        $tokenrequest->setCode($auth_code);
        $tokenrequest->setGrantType('authorization_code');

        $this->aop->appId = $this->config['app_id'];
        $this->aop->rsaPrivateKeyFilePath = $this->config['merchant_private_key_file'];
        $access_token_obj = $this->aop->execute($tokenrequest);
        $access_token_response = $access_token_obj->alipay_system_oauth_token_response;
        $access_token = $access_token_response->access_token; //访问令牌
        $expires_in = $access_token_response->expires_in; //访问令牌的有效时间，单位是秒
        //缓存access token
        $this->setCache($cachename,$access_token,$expires_in);
        return $access_token;
    }

    /**
     * 保存access token
     * @param [string] $cachename 缓存名称
     * @param [string] $value     access_token值
     * @param [int] $expired   缓存有效时间
     */
    private function setCache($cachename,$value,$expired){
        //TODO: set cache implementation
        $file_base_path = $this->config['_cache_base_path'];
        @mkdir($file_base_path, 0777, TRUE);
        $handle = fopen($file_base_path . '/' . $cachename . '.txt', "wb");
        $data = json_encode(array('access_data' => $value,'createdat' => time(),'expired' => $expired));
        fwrite($handle, $data);
        fclose($handle);
    }

    /**
     * 获取access token
     * @param  [string] $cachename 缓存名称
     * @return [string] 
     */
    private function getCache($cachename){
        //TODO: get cache implementation
        $file_base_path = $this->config['_cache_base_path'];
        @mkdir($file_base_path, 0777, TRUE);
        $file = $file_base_path . '/' . $cachename . '.txt';
        if (!file_exists($file)) {
            return false;
        }
        $data = json_decode(file_get_contents($file),true);
        $save_time = $data['createdat'];
        $access_data = $data['access_data'];
        $expired = $data['expired'];
        if (time() - intval(trim($save_time)) > $expired) {
            return false;
        } else {
            return $access_data;
        }
        return false;
    }


    /**
     * 获取用户信息
     * @param type $auth_code
     */
    public function getUserBasicInfo($auth_code) {
        //header("Content-Type:text/html;charset=utf-8");
        $result = array();
        $access_token = $this->getAccessToken($auth_code);
        $userinfo = new AlipayUserUserinfoShareRequest();
        $_result = $this->aop->execute($userinfo, $access_token);
        $response = $_result->alipay_user_userinfo_share_response;

        if ($response) {
            $arr = array(
                'deliver_fullname','user_type_value','is_mobile_auth','user_id','zip','is_licence_auth','deliver_province','deliver_city','is_certified','deliver_area','is_bank_auth','deliver_mobile','address','user_status','is_id_auth',
            );
            foreach ($arr as $_arr) {
                $_tmp_res = property_exists($response, $_arr);
                if ($_tmp_res) {
                    $result[$_arr] = $response->$_arr;
                }
            }
        }
        return $result;
    }

    /**
     * 获取支付宝用户的共享地址
     * @return array()
     */
    public function getUserDeliverAddress(){
        $result = array();
        $access_token = $this->getAccessToken($auth_code);
        $userinfo = new AlipayUserUserinfoShareRequest();
        $_result = $this->aop->execute($userinfo, $access_token);
        $response = $_result->alipay_user_userinfo_share_response;
        if ($response) {
            $address_infos = property_exists($response, 'deliver_address_list');
            if ($address_infos) {
                $deliver_address_list_obj = $response->deliver_address_list;
                $deliver_addresses = $deliver_address_list_obj->deliver_address;
                foreach ($deliver_addresses as $deliver_address) {
                    $_tmp = array();
                    $_tmp['address'] = $deliver_address->address;
                    $_tmp['address_code'] = $deliver_address->address_code;
                    $_tmp['default_deliver_address'] = $deliver_address->default_deliver_address;
                    $_tmp['deliver_area'] = $deliver_address->deliver_area;
                    $_tmp['deliver_city'] = $deliver_address->deliver_city;
                    $_tmp['deliver_fullname'] = $deliver_address->deliver_fullname;
                    $_tmp['deliver_mobile'] = $deliver_address->deliver_mobile;
                    $_tmp['deliver_province'] = $deliver_address->deliver_province;
                    $_tmp['zip'] = $deliver_address->zip;
                    $result[] = $_tmp;
                }
            }
        }
        return $result;
    }


    /**
     * 获取xml节点帮助函数
     * @param string $xml
     * @param type $node
     * @return type
     */
    private function getNode($xml, $node) {
        $xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $xml;
        $dom = new DOMDocument("1.0", "GBK");
        $dom->loadXML($xml);
        $event_type = $dom->getElementsByTagName($node);
        return $event_type->item(0)->nodeValue;
    }

    /**
     * 记录日志
     * @param type $content
     */
    public function log($content) {
        file_put_contents(Yii::getPathOfAlias('webroot') . "/log.txt", "\r\n内容：" . var_export($content, TRUE) . "\r\n", FILE_APPEND);
    }

}

?>
