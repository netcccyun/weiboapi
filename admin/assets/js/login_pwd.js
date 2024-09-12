var comm_data = {
	servertime:'',
	nonce:'',
	rsakv:'',
	pubkey:'',
	cid:'',
	csrf_token:'',
}
var ajax={
	get: function(url, dataType, callback) {
		dataType = dataType || 'html';
		$.ajax({
			type: "GET",
			url: url,
			async: true,
			dataType: dataType,
			cache:false,
			success: function(data,status) {
				if (callback == null) {
					return;
				}
				callback(data);
			},
			error: function(error) {
				alert('创建连接失败');
			}
		});
	},
	post: function(url, parameter, dataType, callback) {
		dataType = dataType || 'html';
		$.ajax({
			type: "POST",
			url: url,
			async: true,
			dataType: dataType,
			data: parameter,
			cache:false,
			success: function(data,status) {
				if (callback == null) {
					return;
				}
				callback(data);
			},
			error: function(error) {
				alert('创建连接失败');
			}
		});
	}
}
function invokeSettime(obj){
    var countdown=60;
    settime(obj);
    function settime(obj) {
        if (countdown == 0) {
            $(obj).attr("data-lock", "false");
			$(obj).attr("disabled",false);
            $(obj).text("获取验证码");
            countdown = 60;
            return;
        } else {
			$(obj).attr("data-lock", "true");
            $(obj).attr("disabled",true);
            $(obj).text(countdown + "秒后重试");
            countdown--;
        }
        setTimeout(function() { settime(obj) } ,1000)
    }
}
function trim(str){ //去掉头尾空格
	return str.replace(/(^\s*)|(\s*$)/g, "");
}
function encryptpwd(pwd){
	return SinaEncrypt("".concat([comm_data.servertime,comm_data.nonce].join("\t"), "\n").concat(pwd), comm_data.pubkey);
}
function getconfig(){
	var ii = layer.load(2, {shade: [0.1,'#fff']});
	var getvcurl="ajax.php?act=weiboLogin&do=getconfig";
	ajax.get(getvcurl, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			var data = d.data;
			comm_data.servertime = data.servertime;
			comm_data.nonce = data.nonce;
			comm_data.rsakv = data.rsakv;
			comm_data.pubkey = data.pubkey;
			comm_data.csrf_token = d.csrf_token;
		}else{
			layer.alert(d.msg, {icon: 2});
		}
	});
}
function login(user,pwd){
	var ii = layer.msg('正在登录，请稍候...', {icon: 16,shade: 0.5,time: 15000});
	var encpwd = encryptpwd(pwd);
	var loginurl="ajax.php?act=weiboLogin&do=login&r="+Math.random(1);
	ajax.post(loginurl, {user:user, pwd:encpwd, rsakv:comm_data.rsakv, cid:comm_data.cid, csrf_token:comm_data.csrf_token}, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			$('#login').hide();
			$('#submit').hide();
			$('#security').hide();
			$('#submit2').hide();
			showresult(d);
		}else if(d.code ==1){
			comm_data.cid = '';
			$('#load').html("您已开启登录保护，请验证手机后登录："+d.mobile);
			$('#load').show();
			$('#submit').hide();
			$('#code').val("");
			$('#security').show();
			$('#security').attr('type',d.type);
			$('#security').attr('token',d.token);
			$('#security').attr('encrypt_mobile',d.encrypt_mobile);
		}else if(d.code ==2){
			comm_data.cid = d.cid;
			initGeetest4({
				captchaId: '8b4a2bef633eb0264367b3ba9fa1dd3d',
				product: 'bind',
				hideSuccess: true
			},function (captcha) {
				captcha.onReady(function(){
					captcha.showCaptcha();
				}).onSuccess(function(){
					var result = captcha.getValidate();
					if (!result) {
						layer.closeAll();
						return alert('请先完成验证');
					}
					var verifyurl="ajax.php?act=weiboLogin&do=verifycaptcha&r="+Math.random(1);
					ajax.post(verifyurl, {key:comm_data.cid, lot_number:result.lot_number, captcha_output:result.captcha_output, pass_token:result.pass_token, gen_time:result.gen_time}, 'json', function(d) {
						if(d.code ==0){
							login(user,pwd)
						}else{
							layer.alert(d.msg, {icon: 2});
						}
					});
				}).onError(function(){
					alert('验证码加载失败，请刷新页面重试');
				})
			});
		}else{
			comm_data.cid = '';
			$('#load').html(d.msg);
			$('#load').show();
			$('#submit').attr('do','submit');
			$('#login').show();
		}
	});
}
function sendcode(type,token,encrypt_mobile){
	var ii = layer.load(2, {shade: [0.1,'#fff']});
	var loginurl="ajax.php?act=weiboLogin&do=sendcode&r="+Math.random(1);
	ajax.post(loginurl, {type:type, token:token, encrypt_mobile:encrypt_mobile}, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			$('#smscode').focus();
			invokeSettime("#sendcode");
			layer.alert('验证码发送成功，请查收', {icon: 1}, function(){ layer.closeAll();$('#smscode').focus() });
		}else{
			layer.alert(d.msg, {icon: 2});
		}
	});
}
function confirmcode(type,token,encrypt_mobile,code){
	var ii = layer.msg('正在验证，请稍等...', {icon: 16,shade: 0.5,time: 15000});
	var loginurl="ajax.php?act=weiboLogin&do=confirmcode&r="+Math.random(1);
	ajax.post(loginurl, {type:type, token:token, encrypt_mobile:encrypt_mobile, code:code}, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			$('#login').hide();
			$('#submit').hide();
			$('#security').hide();
			$('#submit2').hide();
			showresult(d);
		}else{
			layer.alert(d.msg, {icon: 2});
			$('#login').show();
		}
	});
}
function showresult(arr){
	$('#load').html('<font color="green"><span class="glyphicon glyphicon-ok-sign"></span></font> 微博账号添加成功！<hr/>'+decodeURIComponent(arr.nick)+'（UID：'+arr.uid+'）');
	$('#load').show();
}
$(document).ready(function(){
	$('#submit').click(function(){
		var self=$(this);
		var user=trim($('#user').val()),
			pwd=trim($('#pwd').val());
		if(user==''||pwd=='') {
			alert("请确保每项不能为空！");
			return false;
		}
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		login(user,pwd);
		self.attr("data-lock", "false");
	});
	$('#submit2').click(function(){
		var self=$(this);
		var code=trim($('#smscode').val());
		if(code=='') {
			alert("验证码不能为空！");
			return false;
		}
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		var type=$('#security').attr('type'),
			token=$('#security').attr('token'),
			encrypt_mobile=$('#security').attr('encrypt_mobile');
		confirmcode(type,token,encrypt_mobile,code);
		self.attr("data-lock", "false");
	});
	$('#sendcode').click(function(){
		var self=$(this);
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		var type=$('#security').attr('type'),
			token=$('#security').attr('token'),
			encrypt_mobile=$('#security').attr('encrypt_mobile');
		sendcode(type,token,encrypt_mobile);
		self.attr("data-lock", "false");
	});
	getconfig();
});