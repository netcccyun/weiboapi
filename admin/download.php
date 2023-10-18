<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$mod=isset($_GET['mod'])?$_GET['mod']:null;

if($mod == 'faceimg'){
    $uid = isset($_GET['uid'])?$_GET['uid']:null;

    $url = 'http://tp2.sinaimg.cn/'.$uid.'/180/'.time().'/1';
    $imgurl = get_redirect_url($url, 'https://weibo.com/');
    if($imgurl){
        ob_clean();
        $seconds_to_cache = 3600*24*7;
        header("Pragma: cache");
		header("Cache-Control: max-age=$seconds_to_cache");
		header("Content-Type: image/jpeg");
        echo get_curl($imgurl, 0, 'https://weibo.com/');
    }
}