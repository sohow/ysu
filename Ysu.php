<?php
/**
 * Created by 韩宗稳.
 * 
 * Date: 2015/5/4
 * Time: 9:37
 */

class YsuController extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Dispatcher::getInstance()->autoRender(false);
    }

    public function indexAction()
    {
        $param = $this->get_param();
        if (!isset($param['FromUserName']) ||
            !isset($param['ToUserName'])) {
            echo '';exit;
            //$this->send_msg('oD9qkuMYXBY0mkvYUlBu253qhD7g',json_encode($param));
        }
        $data['from'] = $param['FromUserName'];
        $data['to'] = $param['ToUserName'];
        switch ($param['MsgType']) {
            case 'event':
                $data['event'] = $param['Event'];
                $this->deal_event($data);
                break;
            case 'text':
                $data['msg'] = $param['Content'];
                $this->deal_msg($data);
                break;
            default:
                echo '';
                exit;
        }
    }

    public function deal_event($param)
    {
        if ($param['event'] == 'subscribe') {
            Ysu_LibraryModel::attention($param['from']);
            $this->send_help($param['from']);
        }
        else if ($param['event'] == 'unsubscribe') {
            Ysu_LibraryModel::cancle_attention($param['from']);
        }
    }

    public function deal_msg($param)
    {
        if (is_numeric($param['msg'])) {
            if (!$this->exist_book($param['msg'])) {
                $this->send_msg($param['from'],'请发送正确的书刊索引号，回复问号查看帮助');
            }
            $ret = Ysu_LibraryModel::add_book($param['from'],$param['msg']);
            if ($ret == 1) {
                $this->send_msg($param['from'],'每人至多只可同时订阅5本书的借阅状态');
            }
            else if ($ret == 2) {
                $this->send_msg($param['from'],'你已经订阅过该书的借阅状态，请不要重复订阅');
            }
            else if ($ret == 3) {
                $this->send_msg($param['from'],'请发送你接收邮件通知的电子邮箱');
            }
            $ret = $this->check_state($param['from'],$param['msg'],$ret);
            if (!$ret) {
                $this->send_msg($param['from'],'订阅成功，当该书可借阅时（1小时延迟）会向你发送邮件，请注意查收');
            }
        } else {
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
            if (preg_match($pattern, $param['msg'])) {
                $ret = Ysu_LibraryModel::regist_email($param['from'],$param['msg']);
                if ($ret) {
                    $this->send_msg($param['from'],'订阅成功，当该书可借阅时（1小时延迟）会向你发送邮件，请注意查收');
                    //$this->check_state($param['from'],$param['msg']);
                }else{
                    $this->send_msg($param['from'],'电子邮箱已经存在');
                }
            }else{
                $this->send_help($param['from']);
            }
        }
    }

    //获取书刊借阅状态详情
    public function detailAction()
    {
        $url = 'http://202.206.242.99/opac/ajax_item.php?marc_no=';
        $r = $this->getRequest();
        $id = $r->getQuery('id','');
        if (empty($id)) {
            echo 'error id';exit;
        }
        header("Content-type:text/html;charset=utf-8");
        echo Helper_Http::get($url.$id);
    }

    //sae定时执行该方法
    public function timerAction()
    {
        $tasks = array();
        $list = Ysu_LibraryModel::select_all();
        if (empty($list)) {
            echo 'no task';
            exit;
        }
        $queue = new SaeTaskQueue('ysulibrary');
        foreach ($list as $v) {
            $tasks[] = array('url'=>'/ysu/task', 'postdata'=>'param='.json_encode($v));
        }
        $queue->addTask($tasks);
        $ret = $queue->push();
        if ($ret === false) {
            var_dump($queue->errno(), $queue->errmsg());
        }else {
            echo 'total task: ' . count($list);
        }
    }

    //队列任务
    public function taskAction()
    {
        $r = $this->getRequest();
        $param = $r->getPost('param','');
        if (empty($param)) {
            echo 'empty param';
            exit;
        }
        $param = json_decode($param,true);
        $this->check_state($param['uid'],$param['bookid'],$param['email']);
    }

    //判断书刊是否存在
    public function exist_book($bookId)
    {
        $url = 'http://202.206.242.99/opac/ajax_item.php?marc_no=';
        $url .= $bookId;
        $html = Helper_Http::get($url);
        if (mb_strlen($html) < 400) {
            return false;
        }
        return true;
    }

    //检查书刊的借阅状态
    public function check_state($uid,$bookId,$email)
    {
        $url = 'http://202.206.242.99/opac/ajax_item.php?marc_no=';
        $url .= $bookId;
        $html = Helper_Http::get($url);
        if (mb_strstr($html,'可借','UTF-8')) {
            Ysu_LibraryModel::find_book($uid,$bookId);
            $detail  = '状态：可借阅'."\n".'详情：'.'http://sohow.sinaapp.com/ysu/detail?id='.$bookId;
            Tool_Mail::send($email,'可借阅提醒',$detail);
            $this->send_msg($uid,$detail);
            return true;
        }
        return false;
    }

    //获取接口参数
    public function get_param()
    {
        $data = array();
        $postStr = file_get_contents('php://input');//$GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)) {
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            foreach ((array)$postObj as $key => $value) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    //发送文本消息
    public function send_msg($to,$msg)
    {
        echo Wx_BasicModel::format_msg('gh_44a32f494513',$to,$msg);
        exit;
    }

    //发送图文消息
    public function send_picmsg($to,$title,$sumary,$picurl,$url)
    {
        echo Wx_BasicModel::format_picmsg('gh_44a32f494513',$to,$title,$sumary,$picurl,$url);
        exit;
    }

    //发送帮助
    public function send_help($userId)
    {
        $sumary = '提供燕山大学图书馆书刊的借阅状态订阅服务，只需要发送需要订阅书刊的索引号（索引号示例如上图）和接收可借阅提醒的电子邮箱（当此书可借阅时会向此邮箱发送一封提醒邮件）。此项目开源在GitHub上，期待更多同学参与。GitHub:稍后放出';
        $picurl = 'https://mmbiz.qlogo.cn/mmbiz/ibSPzhS9pw7B3nbGuqBxoLJJscVofgIia0aNJgzoibOCXvySuxMALrbMLBKuF0wpgF0cnd9bxoEmnyN7CftylCafw/0?wx_fmt=jpeg';
        $this->send_picmsg($userId,'帮助',$sumary,$picurl,'http://sohow.sinaapp.com/');
    }


//    public function initappAction()
//    {
//        $echoStr = $_GET["echostr"];
//        $signature = $_GET["signature"];
//        $timestamp = $_GET["timestamp"];
//        $nonce = $_GET["nonce"];
//        $token = 'ysulibrary';
//
//        $tmpArr = array($token, $timestamp, $nonce);
//        // use SORT_STRING rule
//        sort($tmpArr, SORT_STRING);
//        $tmpStr = implode( $tmpArr );
//        $tmpStr = sha1( $tmpStr );
//
//        if( $tmpStr == $signature ){
//            echo $echoStr;
//        }
//        exit;
//    }

//    public function search_book($param)
//    {
//        $url = 'http://202.206.242.99/opac/openlink.php?doctype=ALL&strSearchType=title&match_flag=forward&displaypg=20&sort=CATA_DATE&orderby=desc&showmode=list&location=ALL&historyCount=1&strText=';
//        $html = Html_Parser::file_get_html($url.$param['msg']);
//
//        foreach($html->find('.book_list_info') as $e) {
//            $param = array();
//            $t = $e->childNodes(0)->childNodes(0);
//            $code = mb_detect_encoding($t->plaintext,array('HTML-ENTITIES','ASCII','GB2312','GBK','UTF-8'));
//            $param[] = mb_convert_encoding($t->plaintext,'UTF-8',$code);
//            $t = $e->childNodes(0)->childNodes(1)->childNodes(1);
//            $code = mb_detect_encoding($t->plaintext,array('HTML-ENTITIES','ASCII','GB2312','GBK','UTF-8'));
//            $param[] = mb_convert_encoding($t->plaintext,'UTF-8',$code);
//            $t = $e->childNodes(0)->childNodes(1)->childNodes(2);
//            $code = mb_detect_encoding($t->plaintext,array('HTML-ENTITIES','ASCII','GB2312','GBK','UTF-8'));
//            $param[] = mb_convert_encoding($t->plaintext,'UTF-8',$code);
//            $t = $e->childNodes(0)->childNodes(2);
//            $code = mb_detect_encoding($t->plaintext,array('HTML-ENTITIES','ASCII','GB2312','GBK','UTF-8'));
//            $param[] = mb_convert_encoding($t->plaintext,'UTF-8',$code);
//            print_r($param);
//            //echo $e->plaintext . '</br>';
//        }
//        exit;
//        //mb_convert_encoding ("&#20320;&#22909;", 'UTF-8', 'HTML-ENTITIES');
//    }

}