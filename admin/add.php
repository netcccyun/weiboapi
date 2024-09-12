<?php
include("../includes/common.php");
$title='添加账号';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$mod=isset($_GET['mod'])?$_GET['mod']:'pwd';
$id=isset($_GET['id'])?$_GET['id']:null;
if($id){
	$loginname=$DB->findColumn('account','loginname',['id'=>$id]);
}
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">

<div class="panel panel-primary">
	<div class="panel-heading" style="text-align: center;"><h3 class="panel-title">
		添加/更新微博账号
	</div>
	<div class="panel-body" style="text-align: center;">
		<div class="list-group">
			<ul class="nav nav-tabs">
				<li><a href="?mod=pwd">密码登录</a></li><li><a href="?mod=scan">扫码登录</a></li><li><a href="?mod=sms">短信验证码登录</a></li><li><a href="?mod=qq">QQ快捷登录</a></li>
			</ul>
			<div class="list-group-item"><img src="https://img.t.sinajs.cn/t6/style/images/global_nav/WB_logo-x2.png"></div>
<?php if($mod == 'pwd'){?>
			<div class="list-group-item list-group-item-info" style="display:none;font-weight: bold;" id="load">
			</div>
			<div id="login" class="list-group-item">
				<div class="form-group">
					<div class="input-group"><div class="input-group-addon">帐号</div>
					<input type="text" id="user" value="<?php echo $loginname?>" class="form-control" onkeydown="if(event.keyCode==13){submit.click()}" placeholder="邮箱/会员账号/手机号"/>
				</div></div>
				<div class="form-group">
					<div class="input-group"><div class="input-group-addon">密码</div>
					<input type="text" id="pwd" value="" class="form-control" onkeydown="if(event.keyCode==13){submit.click()}" placeholder="请输入密码"/>
				</div></div>
				<button type="button" id="submit" class="btn btn-primary btn-block">提交</button>
			</div>
			<div id="security" class="list-group-item" style="display:none;">
				<div class="form-group">
					<div class="input-group"><div class="input-group-addon">验证码</div>
					<input type="text" id="smscode" value="" class="form-control" onkeydown="if(event.keyCode==13){submit.click()}" autocomplete="off"/>
					<div class="input-group-addon" id="sendcode_button"><button type="button" id="sendcode">获取验证码</button></div>
				</div></div>
				<button type="button" id="submit2" class="btn btn-primary btn-block">提交</button>
			</div>
<?php }elseif($mod == 'scan'){?>
      <div class="list-group-item list-group-item-info" style="font-weight: bold;" id="load">
				<span id="loginmsg">正在加载</span><span id="loginload" style="padding-left: 10px;color: #790909;">.</span>
			</div>
			<div class="list-group-item" id="login" style="display:none;">
				<div class="list-group-item" id="qrimg">
				</div>
				<div class="list-group-item" id="mobile" style="display:none;"><button type="button" id="mlogin" onclick="mloginurl()" class="btn btn-warning btn-block">跳转微博客户端登录</button><br/><button type="button" onclick="qrlogin()" class="btn btn-success btn-block">我已完成登录</button></div>
			</div>
<?php }elseif($mod == 'sms'){?>
      <div class="list-group-item list-group-item-info" style="display:none;font-weight: bold;" id="load">
			</div>
			<div id="login" class="list-group-item">
				<div class="form-group">
					<div class="input-group"><div class="input-group-addon">手机号</div>
					<input type="text" id="mobile" value="<?php echo $loginname?>" class="form-control" onkeydown="if(event.keyCode==13){submit.click()}"/>
				</div></div>
				<div class="form-group" id="sms" style="display:none;">
					<div class="input-group"><div class="input-group-addon">验证码</div>
					<input type="text" id="smscode" value="" class="form-control" onkeydown="if(event.keyCode==13){submit.click()}" placeholder="输入短信验证码" autocomplete="off"/>
					<div class="input-group-addon" id="sendcode_button"><button type="button" id="sendcode">获取验证码</button></div>
					</div>
				</div>
				<button type="button" id="submit" class="btn btn-primary btn-block">提交</button>
			</div>
<?php }elseif($mod == 'qq'){?>
      <div class="list-group-item list-group-item-info" style="font-weight: bold;" id="login">
				<span id="loginmsg">使用QQ手机版扫描二维码</span><span id="loginload" style="padding-left: 10px;color: #790909;">.</span>
			</div>
			<div class="list-group-item" id="qrimg">
				<div class="qr-image" id="qrcode"></div>
			</div>
			<div class="list-group-item" id="mobile" style="display:none;"><button type="button" id="mlogin" onclick="mloginurl()" class="btn btn-warning btn-block">跳转QQ快捷登录</button><br/><button type="button" onclick="qrlogin()" class="btn btn-success btn-block">我已完成登录</button></div>
<?php }?>
			<br/><a href="./list.php">返回账号列表</a>
		</div>
	</div>
</div>
<script>
  var mod = '<?php echo $mod?>';
  $(document).ready(function(){
    $(".nav-tabs").find("a[href='?mod="+mod+"']").parent().addClass('active')
  })
</script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script src="//static.geetest.com/v4/gt4.js"></script>
<?php if($mod == 'pwd'){?>
<script src="./assets/js/encoder.js"></script>
<script src="./assets/js/login_pwd.js"></script>
<?php }elseif($mod == 'scan'){?>
<script src="./assets/js/login_scan.js"></script>
<?php }elseif($mod == 'sms'){?>
<script src="./assets/js/encoder.js"></script>
<script src="./assets/js/login_sms.js"></script>
<?php }elseif($mod == 'qq'){?>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="./assets/js/login_qq.js"></script>
<?php }?>
