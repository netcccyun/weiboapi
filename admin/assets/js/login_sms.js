var comm_data = {
	servertime:'',
	nonce:'',
	rsakv:'',
	pubkey:'',
	cid:'',
	csrf_token:'',
	smstoken:''
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
function login(user,code){
	var ii = layer.msg('正在登录，请稍候...', {icon: 16,shade: 0.5,time: 15000});
	var loginurl="ajax.php?act=weiboLogin&do=smslogin&r="+Math.random(1);
	ajax.post(loginurl, {user:user, code:code, csrf_token:comm_data.csrf_token}, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			$('#login').hide();
			$('#submit').hide();
			showresult(d);
		}else{
			$('#load').html(d.msg);
			$('#load').show();
			$('#login').show();
		}
	});
}
function sendsms(mobile){
	var ii = layer.load(2, {shade: [0.1,'#fff']});
	var loginurl="ajax.php?act=weiboLogin&do=sendsms&r="+Math.random(1);
	ajax.post(loginurl, {mobile:mobile, cid:comm_data.cid, csrf_token:comm_data.csrf_token}, 'json', function(d) {
		layer.close(ii);
		if(d.code ==0){
			$('#sms').show();
			$('#submit').attr('do','smscode');
			invokeSettime("#sendcode");
			layer.alert('验证码发送成功，请查收', {icon: 1}, function(){ layer.closeAll();$('#sendcode').focus() });
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
					$('#validate_data').val(window.btoa(JSON.stringify(result)));
					var verifyurl="ajax.php?act=weiboLogin&do=verifycaptcha&r="+Math.random(1);
					ajax.post(verifyurl, {key:comm_data.cid, lot_number:result.lot_number, captcha_output:result.captcha_output, pass_token:result.pass_token, gen_time:result.gen_time}, 'json', function(d) {
						if(d.code ==0){
							sendsms(mobile)
						}else{
							layer.alert(d.msg, {icon: 2});
						}
					});
				}).onError(function(){
					alert('验证码加载失败，请刷新页面重试');
				})
			});
		}else{
			layer.alert(d.msg, {icon: 2});
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
		var mobile=trim($('#mobile').val()),
			smscode=trim($('#smscode').val());
		if(mobile=='') {
			alert("手机号不能为空！");
			return false;
		}
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		if(self.attr('do') == 'smscode'){
			if(smscode=='') {
				alert("验证码不能为空！");
				return false;
			}
			login(mobile,smscode);
		}else{
			sendsms(mobile);
		}
		self.attr("data-lock", "false");
	});
	$('#sendcode').click(function(){
		var self=$(this);
		var mobile=trim($('#mobile').val());
		if(mobile=='') {
			alert("手机号不能为空！");
			return false;
		}
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		sendsms(mobile);
		self.attr("data-lock", "false");
	});
	getconfig();
});