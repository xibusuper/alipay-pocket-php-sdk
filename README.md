#alipay-pocket-sdk
#支付宝钱包服务窗平台SDK
##使用帮助
	$options = array(
	  'app_id' => "201408008041",//填写支付宝服务窗app_id
	  '_cache_base_path' => '';    //填写缓存文件路径
	)
	$pocket = new AlipayPocket($options);
	$service = $AlipayPocket->getService();

	$pocket->rsaCheck(); //收到请求，先验证签名

	if ($service == "alipay.service.check") {
	   $pocket->verifygw(); //验证网关请求
	} else if ($service == "alipay.mobile.public.message.notify") {
	   //处理收到的消息

	   $alipaypocket_user_id = $pocket->getFromUserId();
	   $content = $pocket->getContent();
	   $MsgType = $pocket->getMsgType();
	   $EventType = $pocket->getEventType();

	   if ($MsgType == "text") {
	       //$this->alipaypocket->log($content);
	       $pocket->text("hello world");
	   }elseif($MsgType == "event"){
	       switch ($EventType) {
	           case "follow":   //关注事件
	               break;
	           case "unfollow": //取消关注
	               break;
	           case "enter":    //进入服务窗
	               break; 
	           case "click":    //点击事件
	               break;
	           default : 
	               break;             
	       }
	   }
	} 
