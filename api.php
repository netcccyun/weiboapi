<?php
include("./includes/common.php");
define('IS_CRON', true);
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

@header('Content-Type: application/json; charset=UTF-8');

$opentype = explode(',', $conf['opentype']);
$key = isset($_POST['key'])?trim($_POST['key']):trim($_GET['key']);
if(!$key) exit('{"code":-1,"msg":"No key"}');

switch($act){
case 'getcookie': //获取微博COOKIE
	$uid = isset($_POST['uid'])?trim($_POST['uid']):null;
	if($conf['cookie_open']!=1)exit('{"code":-1,"msg":"未开启获取COOKIE接口"}');
	if($key !== $conf['cookie_key'])exit('{"code":-1,"msg":"密钥错误"}');
	if(!empty($uid)){
		$account = $DB->find('account', '*', ['uid'=>$uid]);
		if(!$account) exit('{"code":-1,"msg":"微博账号不存在"}');
		if($account['status']!=1) exit('{"code":-1,"msg":"微博账号状态不正常"}');
		$uid = $account['uid'];
		$nickname = $account['nickname'];
		$cookie = $account['cookie_weibo'];
	}else{
		$account = $DB->getRow("SELECT * FROM weiboapi_account WHERE `status`=1 ORDER BY usetime ASC LIMIT 1");
		if(!$account) exit('{"code":-1,"msg":"暂无可用的微博账号"}');
		$uid = $account['uid'];
		$nickname = $account['nickname'];
		$cookie = $account['cookie_weibo'];
	}
	exit(json_encode(['code'=>0, 'uid'=>$uid, 'nickname'=>$nickname, 'cookie'=>$cookie]));
break;
case 'gethotsearch': //获取热搜列表
	if($key !== $conf['apikey'])exit('{"code":-1,"msg":"密钥错误"}');
	$result = weibotool_call('hotline', [], 60);
	exit(json_encode($result));
break;
case 'parsevideo': //解析微博视频
	$oid = isset($_POST['oid'])?trim($_POST['oid']):exit('{"code":-1,"msg":"视频ID不能为空"}');
	if($key !== $conf['apikey'])exit('{"code":-1,"msg":"密钥错误"}');
	$result = weibotool_call('parseVideo', [$oid], 3600);
	exit(json_encode($result));
break;
case 'getuserinfo': //获取用户信息
	$uid = isset($_POST['uid'])?trim($_POST['uid']):exit('{"code":-1,"msg":"用户ID不能为空"}');
	if($key !== $conf['apikey'])exit('{"code":-1,"msg":"密钥错误"}');
	$result = weibotool_call('getUserInfo', [$uid], 3600);
	exit(json_encode($result));
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}