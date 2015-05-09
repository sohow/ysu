<?php
/**
 * Created by 韩宗稳.
 * 
 * Date: 2015/5/8
 * Time: 14:29
 */
 class Wx_BasicModel {

     //获取参数
     public function get_param()
     {
         $data = array();
         $post = file_get_contents('php://input');
         if (!empty($post)) {
             libxml_disable_entity_loader(true);
             $obj = simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
             foreach ((array)$obj as $key => $value) {
                 $data[$key] = $value;
             }
         }
         return $data;
     }

     //格式化文本消息
     public function format_msg($from,$to,$msg)
     {
         $format = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>';
         return sprintf($format,$to, $from, time(), 'text', $msg);
     }

     //格式化图文消息
     public function format_picmsg($from,$to,$title='',$sumary='',$picurl='',$url='')
     {
         $format = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>1</ArticleCount>
                    <Articles>
                    <item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>
                    </Articles>
                    </xml> ';
         return sprintf($format,$to, $from, time(), $title, $sumary,$picurl,$url);
     }
 }