<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'set':
	if(isset($_POST['opentype'])){
		$_POST['opentype'] = implode(',',$_POST['opentype']);
	}
	foreach($_POST as $k=>$v){
		saveSetting($k, $v);
	}
	exit('{"code":0,"msg":"succ"}');
break;

case 'checkAccount':
	$id = intval($_POST['id']);
	try{
		$result = \lib\Logic::checkAccount($id);
	}catch(Exception $e){
		exit(json_encode(['code'=>-1, 'msg'=>$e->getMessage()]));
	}
	if($result == 2){
		exit(json_encode(['code'=>0, 'msg'=>'账号COOKIE更新成功！']));
	}elseif($result == 1){
		exit(json_encode(['code'=>0, 'msg'=>'账号状态检测正常！']));
	}else{
		exit(json_encode(['code'=>-1, 'msg'=>'账号状态已失效，请点击更新按钮进行登录']));
	}
break;

case 'deleteAccount':
	$id = intval($_POST['id']);
	$row = $DB->find('account', '*', ['id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"账号不存在"}');
	$DB->delete('account', ['id'=>$id]);
	$DB->delete('log', ['aid'=>$id]);
	exit(json_encode(['code'=>0]));
break;

case 'weiboLogin':
	$login=new \lib\WeiboLogin();
	if($_GET['do']=='getqrcode'){
		$array=$login->getqrcode();
	}
	if($_GET['do']=='qrlogin'){
		$array=$login->qrlogin($_POST['qrid']);
		if($array['code'] == 0){
			\lib\Logic::addAccount($array);
		}
	}
	if($_GET['do']=='prelogin'){
		$array=$login->prelogin($_POST['user']);
	}
	if($_GET['do']=='getpin'){
		ob_clean();
		header('content-type: image/jpeg');
		echo $login->getpin($_GET['pcid']);
		exit;
	}
	if($_GET['do']=='login'){
		$array=$login->login($_POST['user'],$_POST['pwd'],$_POST['servertime'],$_POST['nonce'],$_POST['rsakv'],$_POST['pcid'],$_POST['door']);
		if($array['code'] == 0){
			\lib\Logic::addAccount($array);
		}
	}
	if($_GET['do']=='sendcode'){
		$array=$login->sendcode($_POST['token'],$_POST['encrypt_mobile']);
	}
	if($_GET['do']=='confirmcode'){
		$array=$login->confirmcode($_POST['token'],$_POST['encrypt_mobile'],$_POST['code']);
	}
	if($_GET['do']=='sendsms'){
		$array=$login->sendsms($_POST['mobile'],$_POST['token']);
	}
	if($_GET['do']=='smslogin'){
		$array=$login->smslogin($_POST['user'],$_POST['pwd'],$_POST['servertime'],$_POST['nonce'],$_POST['rsakv']);
		if($array['code'] == 0){
			\lib\Logic::addAccount($array);
		}
	}
	if($_GET['do']=='qq_getqrcode'){
		$array=$login->qq_getqrcode();
	}
	if($_GET['do']=='qq_qrlogin'){
		$array=$login->qq_qrlogin($_GET['qrsig']);
	}
	if($_GET['do']=='qq_connect'){
		$array=$login->qq_connect($_POST['redirect_uri'],$_POST['crossidccode']);
		if($array['code'] == 0){
			\lib\Logic::addAccount($array);
		}
	}
	echo json_encode($array);
break;

case 'upload':
	if(!isset($_FILES['file']))exit('{"code":-1,"msg":"请选择文件"}');
	$file = $_FILES["file"]["tmp_name"];
	if($_FILES['file']['error']>0 || $file == ""){
		exit('{"code":-1,"msg":"文件损坏！"}');
	}
	if($_FILES['file']['size']>10*1024*1024){
		exit('{"code":-1,"msg":"文件最大10M"}');
	}
	$result = weibotool_call('upload', [$file]);
	echo json_encode($result);
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}