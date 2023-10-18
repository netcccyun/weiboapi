var interval1,interval2;
function showresult(arr){
	$('#load').html('<font color="green"><span class="glyphicon glyphicon-ok-sign"></span></font> 微博账号添加成功！<hr/>'+decodeURIComponent(arr.nick)+'（UID：'+arr.uid+'）');
}
function getqrpic(){
	cleartime();
	var getvcurl='ajax.php?act=weiboLogin&do=qq_getqrcode&r='+Math.random(1);
	$.get(getvcurl, function(d) {
		if(d.code ==0){
			$('#qrimg').attr('qrsig',d.qrsig);
			$('#qrimg').attr('qrcode',d.qrcode);
			$('#qrcode').empty()
            $('#qrcode').qrcode({
                text: d.qrcode,
                width: 150,
                height: 150,
                foreground: "#000000",
                background: "#ffffff",
                typeNumber: -1
            });
			if( /Android|SymbianOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Windows Phone|Midp/i.test(navigator.userAgent)) {
				$('#mobile').show();
			}
			interval1=setInterval(loginload,1000);
			interval2=setInterval(qrlogin,3000);
		}else{
			alert(d.msg);
		}
	}, 'json');
}
function qrlogin(){
	if ($('#login').attr("data-lock") === "true") return;
	var qrsig=$('#qrimg').attr('qrsig');
	var url = 'ajax.php?act=weiboLogin&do=qq_qrlogin&qrsig='+decodeURIComponent(qrsig)+'&r='+Math.random(1);
	$.get(url, function(d) {
		if(d.code ==0){
			$('#login').attr("data-lock", "true");
			$('#loginmsg').html('正在登录微博，请稍候...');
			cleartime();
			qqconnect(d.redirect_uri, d.crossidccode);
		}else if(d.code ==1){
			getqrpic();
			$('#loginmsg').html('请重新扫描二维码');
		}else if(d.code ==2){
			$('#loginmsg').html('使用QQ手机版扫描二维码');
		}else if(d.code ==3){
			$('#loginmsg').html('扫描成功，请在手机上确认授权登录');
		}else{
			cleartime();
			$('#loginmsg').html(d.msg);
		}
	}, 'json');
}
function qqconnect(redirect_uri, crossidccode){
	$.ajax({
		type : "POST",
		url : "ajax.php?act=weiboLogin&do=qq_connect",
		data : {redirect_uri:redirect_uri, crossidccode:crossidccode},
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				showresult(data);
				$('#qrimg').hide();
				$('#mobile').hide();
			}else{
				$('#loginmsg').html(data.msg);
			}
		}
	});
}
function loginload(){
	if ($('#login').attr("data-lock") === "true") return;
	var load=document.getElementById('loginload').innerHTML;
	var len=load.length;
	if(len>2){
		load='.';
	}else{
		load+='.';
	}
	document.getElementById('loginload').innerHTML=load;
}
function cleartime(){
	clearInterval(interval1);
	clearInterval(interval2);
}
function mloginurl(){
	var qrurl = $('#qrimg').attr('qrcode');
	$('#loginmsg').html('跳转到QQ登录后请返回此页面');
	var ua = window.navigator.userAgent.toLowerCase();
	var is_ios = ua.indexOf('iphone')>-1 || ua.indexOf('ipad')>-1;
	var schemacallback = '';
	if(is_ios){
		schemacallback = 'weixin://';
	}else if(ua.indexOf('ucbrowser')>-1){
		schemacallback = 'ucweb://';
	}else if(ua.indexOf('meizu')>-1){
		schemacallback = 'mzbrowser://';
	}else if(ua.indexOf('liebaofast')>-1){
		schemacallback = 'lb://';
	}else if(ua.indexOf('baidubrowser')>-1){
		schemacallback = 'bdbrowser://';
	}else if(ua.indexOf('baiduboxapp')>-1){
		schemacallback = 'bdapp://';
	}else if(ua.indexOf('mqqbrowser')>-1){
		schemacallback = 'mqqbrowser://';
	}else if(ua.indexOf('qihoobrowser')>-1){
		schemacallback = 'qihoobrowser://';
	}else if(ua.indexOf('chrome')>-1){
		schemacallback = 'googlechrome://';
	}else if(ua.indexOf('sogoumobilebrowser')>-1){
		schemacallback = 'SogouMSE://';
	}else if(ua.indexOf('xiaomi')>-1){
		schemacallback = 'miuibrowser://';
	}else{
		schemacallback = 'googlechrome://';
	}
	if(is_ios){
		alert('跳转到QQ登录后请手动返回当前浏览器');
		window.location.href='wtloginmqq3://ptlogin/qlogin?qrcode='+encodeURIComponent(qrurl)+'&schemacallback='+encodeURIComponent(schemacallback);
	}else{
		window.location.href='wtloginmqq://ptlogin/qlogin?qrcode='+encodeURIComponent(qrurl)+'&schemacallback='+encodeURIComponent(schemacallback);
	}
}
$(document).ready(function(){
	getqrpic();
});