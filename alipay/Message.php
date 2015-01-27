<?php
require_once 'PushMsg.php';

class Message {

    public function Message($biz_content) {
        file_put_contents("log.txt", $biz_content . "\r\n", FILE_APPEND);
        $UserInfo = $this->getNode($biz_content, "UserInfo");
        $FromUserId = $this->getNode($biz_content, "FromUserId");
        $AppId = $this->getNode($biz_content, "AppId");
        $CreateTime = $this->getNode($biz_content, "CreateTime");
        $MsgType = $this->getNode($biz_content, "MsgType");
        $EventType = $this->getNode($biz_content, "EventType");
        $AgreementId = $this->getNode($biz_content, "AgreementId");
        $ActionParam = $this->getNode($biz_content, "ActionParam");
        $AccountNo = $this->getNode($biz_content, "AccountNo");

        $push = new PushMsg ();
        //收到用户发送的对话消息
        if ($MsgType == "text") {
            $text_msg = $push->mkTextMsg("你好，这是测试消息");

            // 发给这个关注的用户
            $biz_content = $push->mkTextBizContent($FromUserId, $text_msg);
            file_put_contents("log.txt", iconv("UTF-8", "GBK", "\r\n发送的biz_content：" . $biz_content . "\r\n"), FILE_APPEND);
// 			$return_msg = $push->sendMsgRequest ( $biz_content );
            $return_msg = $push->sendRequest($biz_content);
            // 日志记录
            file_put_contents("log.txt", $return_msg . "\r\n", FILE_APPEND);
        }

        //收到用户发送的关注消息
        if ($EventType == "follow") {
            // 处理关注消息
            // 一般情况下，可推送一条欢迎消息或使用指导的消息。
            // 如：
            $image_text_msg1 = $push->mkImageTextMsg("标题1", "描述1", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth");
            $image_text_msg2 = $push->mkImageTextMsg("标题2", "描述2", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth");
            // 组装多条图文信息
            $image_text_msg = array(
                $image_text_msg1,
                $image_text_msg2
            );
            // 发给这个关注的用户
            $biz_content = $push->mkImageTextBizContent($FromUserId, $image_text_msg);

            $return_msg = $push->sendMsgRequest($biz_content);
            // 日志记录
            file_put_contents("log.txt", $return_msg . "\r\n", FILE_APPEND);
        } elseif ($EventType == "unfollow") {
            // 处理取消关注消息
        } elseif ($EventType == "enter") {
            // 处理进入消息，扫描二维码进入
            // 处理关注消息
            // 一般情况下，可推送一条欢迎消息或使用指导的消息。
            // 如：
            $image_text_msg1 = $push->mkImageTextMsg("标题", "描述", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth");
            $image_text_msg2 = $push->mkImageTextMsg("标题", "描述", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth");
            // 组装多条图文信息
            $image_text_msg = array(
                $image_text_msg1,
                $image_text_msg2
            );

            // 发给这个关注的用户
            $biz_content = $push->mkImageTextBizContent($FromUserId, $image_text_msg);

            $return_msg = $push->sendMsgRequest($biz_content);
            // 日志记录
            file_put_contents("log.txt", $return_msg . "\r\n", FILE_APPEND);
        } elseif ($EventType == "click") {
            // 处理菜单点击的消息
        }
    }

    public function getNode($xml, $node) {
        $xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $xml;
        $dom = new DOMDocument("1.0", "GBK");
        $dom->loadXML($xml);
        $event_type = $dom->getElementsByTagName($node);
        return $event_type->item(0)->nodeValue;
    }

}
