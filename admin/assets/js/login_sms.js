var comm_data = {
	servertime:'',
	nonce:'',
	rsakv:'',
	pcid:'',
	pubkey:'',
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
function getpwd(pwd, servertime){
	var f = new sinaSSOEncoder.RSAKey();
    f.setPublic(comm_data.pubkey, "10001");
    res = f.encrypt([servertime, comm_data.nonce].join("\t") + "\n" + pwd);
	return res;
}
function prelogin(mobile){
	$('#load').html('登录中，请稍候...');
	var getvcurl="ajax.php?act=weiboLogin&do=prelogin";
	ajax.post(getvcurl, {user:mobile}, 'json', function(d) {
		if(d.code ==0){
			var data = d.data;
			if(data.smstoken){
				comm_data.servertime = data.servertime;
				comm_data.nonce = data.nonce;
				comm_data.rsakv = data.rsakv;
				comm_data.pcid = data.pcid;
				comm_data.pubkey = data.pubkey;
				comm_data.smstoken = data.smstoken;
				sendsms(mobile);
			}else{
				var msg = '该手机号无法发送验证码';
				$('#load').html(msg);
				alert(msg);
			}
		}else{
			$('#load').html(d.msg);
			alert(d.msg);
		}
	});
}
function login(user,pwd){
	$('#load').html('正在登录，请稍等...');
	var servertime = parseInt(new Date().getTime() / 1e3);
	var pwd = getpwd(pwd, servertime);
	var loginurl="ajax.php?act=weiboLogin&do=smslogin&r="+Math.random(1);
	ajax.post(loginurl, {user:user, pwd:pwd, servertime:servertime, nonce:comm_data.nonce, rsakv:comm_data.rsakv}, 'json', function(d) {
		if(d.code ==0){
			$('#login').hide();
			$('#submit').hide();
			showresult(d);
		}else{
			$('#load').html(d.msg);
			$('#login').show();
		}
	});
}
function sendsms(mobile){
	var loginurl="ajax.php?act=weiboLogin&do=sendsms&r="+Math.random(1);
	ajax.post(loginurl, {mobile:mobile, token:comm_data.smstoken}, 'json', function(d) {
		if(d.code ==0){
			$('#sms').show();
			$('#submit').attr('do','smscode');
			$('#smscode').focus();
			invokeSettime("#sendcode");
			alert('验证码发送成功，请查收');
		}else{
			alert(d.msg);
		}
	});
}
function showresult(arr){
	$('#load').html('<font color="green"><span class="glyphicon glyphicon-ok-sign"></span></font> 微博账号添加成功！<hr/>'+decodeURIComponent(arr.nick)+'（UID：'+arr.uid+'）');
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
		$('#load').show();
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		if(self.attr('do') == 'smscode'){
			if(smscode=='') {
				alert("验证码不能为空！");
				return false;
			}
			login(mobile,smscode);
		}else{
			prelogin(mobile);
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
		$('#load').show();
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		sendsms(mobile);
		self.attr("data-lock", "false");
	});
});