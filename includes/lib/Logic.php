<?php

namespace lib;
use Exception;

class Logic
{

    //添加或更新账号
    public static function addAccount($array){
        global $DB;

        $cookie_login = '';
        foreach($array['cookie'] as $k=>$v){
            $cookie_login .= $k.'='.$v.'; ';
        }
        $cookie_login = substr($cookie_login, 0, -2);
    
        $cookie_weibo = '';
        foreach($array['wbcookie'] as $k=>$v){
            $cookie_weibo .= $k.'='.$v.'; ';
        }
        $cookie_weibo = substr($cookie_weibo, 0, -2);
    
        $data = ['nickname'=>$array['nick'], 'cookie_login'=>$cookie_login, 'cookie_weibo'=>$cookie_weibo, 'refreshtime'=>'NOW()', 'status'=>'1'];
        if($_POST['user']){
            $data['loginname'] = $_POST['user'];
        }
    
        $aid = $DB->findColumn('account', 'id', ['uid'=>$array['uid']]);
    
        if($aid){
            $DB->update('account', $data, ['id'=>$aid]);
            $DB->insert('log', ['aid'=>$aid, 'action'=>'更新账号', 'time'=>'NOW()']);
        }else{
            $data['uid'] = $array['uid'];
            $data['addtime'] = 'NOW()';
            $aid = $DB->insert('account', $data);
            $DB->insert('log', ['aid'=>$aid, 'action'=>'添加账号', 'time'=>'NOW()']);
        }
    }

    //检测账号状态
    public static function checkAccount($id){
        global $DB,$conf;
        $row = $DB->find('account', '*', ['id'=>$id]);
        if(!$row) throw new Exception('账号不存在');
        if($row['status'] == 0) return 0;

        $DB->update('account', ['checktime'=>'NOW()'], ['id'=>$id]);

        $tool = new WeiboTool($row['cookie_weibo']);
        if($tool->checkCookie()){
            return 1;
        }else{
            $login = new WeiboLogin();
            $cookie=[];
            $rows = explode(';', $row['cookie_login']);
            foreach ($rows as $val) {
                $val = trim($val);
                if(empty($val)) continue;
                $key = substr($val, 0, strpos($val, '='));
                $cookie[$key]=substr($val, strpos($val, '=')+1);
            }
            $array = $login->cookielogin($cookie);
            if($array['code'] == 0){
                self::addAccount($array);
                return 2;
            }else{
                $DB->update('account', ['status'=>0], ['id'=>$id]);
                if($conf['mail_open'] == 1 && defined('IS_CRON')){
                    self::noticeFail($row);
                }
                return 0;
            }
        }
    }

    //账号状态失效通知
    private static function noticeFail($account){
        global $DB,$conf;
        $mail_name = $conf['mail_recv']?$conf['mail_recv']:$conf['mail_name'];
        $mail_title = '微博账号:'.$account['nickname'].' 失效提醒';
        $mail_content = '你在'.$conf['sitename'].'添加的微博账号:'.$account['nickname'].'（UID：'.$account['uid'].'）已失效，请及时更新！<br/><br/>'.date("Y-m-d H:i:s").'<br/>';
        send_mail($mail_name,$mail_title,$mail_content);
    }
}