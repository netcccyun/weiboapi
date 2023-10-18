var comm_data = {
	servertime:'',
	nonce:'',
	rsakv:'',
	pcid:'',
	pubkey:''
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
function prelogin(user,pwd){
	$('#load').html('登录中，请稍候...');
	var getvcurl="ajax.php?act=weiboLogin&do=prelogin";
	ajax.post(getvcurl, {user:user}, 'json', function(d) {
		if(d.code ==0){
			var data = d.data;
			comm_data.servertime = data.servertime;
			comm_data.nonce = data.nonce;
			comm_data.rsakv = data.rsakv;
			comm_data.pcid = data.pcid;
			comm_data.pubkey = data.pubkey;
			if(data.showpin == 1){
				getpin(data.pcid);
			}else{
				login(user,pwd,'','');
			}
		}else{
			$('#load').html(d.msg);
			$('#codeForm').hide();
			alert(d.msg);
		}
	});
}
function getpin(pcid){
	$('#codeimg').html('<img onclick="this.src=\'ajax.php?act=weiboLogin&do=getpin&pcid='+pcid+'&r=\'+Math.random();" src="ajax.php?act=weiboLogin&do=getpin&pcid='+pcid+'&r='+Math.random(1)+'" title="点击刷新">');
	$('#submit').attr('do','code');
	$('#code').val("");
	$('#codeForm').show();
}
function login(user,pwd,pcid,vcode){
	$('#load').html('正在登录，请稍等...');
	var servertime = parseInt(new Date().getTime() / 1e3);
	var pwd = getpwd(pwd, servertime);
	var loginurl="ajax.php?act=weiboLogin&do=login&r="+Math.random(1);
	ajax.post(loginurl, {user:user, pwd:pwd, servertime:servertime, nonce:comm_data.nonce, rsakv:comm_data.rsakv, pcid:pcid, door:vcode}, 'json', function(d) {
		if(d.code ==0){
			$('#login').hide();
			$('#codeForm').hide();
			$('#submit').hide();
			$('#security').hide();
			$('#submit2').hide();
			showresult(d);
		}else if(d.code ==1){
			$('#load').html("您已开启登录保护，请验证手机后登录："+d.mobile);
			$('#submit').hide();
			$('#codeForm').hide();
			$('#code').val("");
			$('#security').show();
			$('#security').attr('token',d.token);
			$('#security').attr('encrypt_mobile',d.encrypt_mobile);
		}else if(d.code ==2){
			$('#load').html(d.msg);
			getpin(comm_data.pcid);
		}else{
			$('#load').html(d.msg);
			$('#submit').attr('do','submit');
			$('#codeForm').hide();
			$('#login').show();
		}
	});
}
function sendcode(token,encrypt_mobile){
	var loginurl="ajax.php?act=weiboLogin&do=sendcode&r="+Math.random(1);
	ajax.post(loginurl, {token:token, encrypt_mobile:encrypt_mobile}, 'json', function(d) {
		if(d.code ==0){
			$('#smscode').focus();
			invokeSettime("#sendcode");
			alert('验证码发送成功，请查收');
		}else{
			alert(d.msg);
		}
	});
}
function confirmcode(token,encrypt_mobile,code){
	$('#load').html('正在验证，请稍等...');
	var loginurl="ajax.php?act=weiboLogin&do=confirmcode&r="+Math.random(1);
	ajax.post(loginurl, {token:token, encrypt_mobile:encrypt_mobile, code:code}, 'json', function(d) {
		if(d.code ==0){
			$('#login').hide();
			$('#submit').hide();
			$('#security').hide();
			$('#submit2').hide();
			showresult(d);
		}else{
			$('#load').html(d.msg);
			$('#login').show();
		}
	});
}
function showresult(arr){
	$('#load').html('<font color="green"><span class="glyphicon glyphicon-ok-sign"></span></font> 微博账号添加成功！<hr/>'+decodeURIComponent(arr.nick)+'（UID：'+arr.uid+'）');
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
		$('#load').show();
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		if(self.attr('do') == 'code'){
			var vcode=trim($('#code').val()),
				pcid=comm_data.pcid;
			login(user,pwd,pcid,vcode);
		}else{
			prelogin(user,pwd);
		}
		self.attr("data-lock", "false");
	});
	$('#submit2').click(function(){
		var self=$(this);
		var code=trim($('#smscode').val());
		if(code=='') {
			alert("验证码不能为空！");
			return false;
		}
		$('#load').show();
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		var token=$('#security').attr('token'),
			encrypt_mobile=$('#security').attr('encrypt_mobile');
		confirmcode(token,encrypt_mobile,code);
		self.attr("data-lock", "false");
	});
	$('#sendcode').click(function(){
		var self=$(this);
		$('#load').show();
		if (self.attr("data-lock") === "true") return;
		else self.attr("data-lock", "true");
		var token=$('#security').attr('token'),
			encrypt_mobile=$('#security').attr('encrypt_mobile');
		sendcode(token,encrypt_mobile);
		self.attr("data-lock", "false");
	});
});