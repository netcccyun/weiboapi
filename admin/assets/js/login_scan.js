var interval1,interval2;
function getqrcode(){
	if($('#qrimg').attr('lock') === 'true') return;
	cleartime();
	var getvcurl='ajax.php?act=weiboLogin&do=getqrcode&r='+Math.random(1);
	$.get(getvcurl, function(d) {
		if(d.code ==0){
			$('#qrimg').attr('qrid',d.qrid);
			$('#qrimg').attr('link',d.link);
			$('#qrimg').html('<img id="qrcodeimg" onclick="getqrcode()" src="https:'+d.imgurl+'" title="点击刷新">');
			$('#login').show();
			$('#loginmsg').html('请用最新版微博客户端扫码');
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
	var qrid=$('#qrimg').attr('qrid');
	if(qrid=='')return;
	var loginurl="ajax.php?act=weiboLogin&do=qrlogin";
	$.ajax({
		type: "POST",
		url: loginurl,
		async: true,
		dataType: 'json',
		timeout: 15000,
		data: {qrid : qrid},
		cache:false,
		success: function(data) {
			if(data.code ==0){
                cleartime();
				$('#qrimg').attr('lock','true');
				$('#login').hide();
				showresult(data)
			}else if(data.code ==1){
                $('#loginmsg').html('请用最新版微博客户端扫码');
            }else if(data.code ==2){
                $('#loginmsg').html('成功扫描，请在手机点击确认以登录');
            }else if(data.code ==3){
				$('#loginmsg').html('该二维码已过期，请重新扫描');
                getqrcode();
            }else{
                cleartime();
                $('#loginmsg').html(data.msg);
				alert(data.msg);
            }
		},
		error: function(){
			cleartime();
			alert('服务器错误');
		}
	});
}
function loginload(){
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
function showresult(arr){
	$('#load').html('<font color="green"><span class="glyphicon glyphicon-ok-sign"></span></font> 微博账号添加成功！<hr/>'+decodeURIComponent(arr.nick)+'（UID：'+arr.uid+'）');
}
function mloginurl(){
	var url = $('#qrimg').attr('link');
	window.location.href='sinaweibo://browser?url='+encodeURIComponent(url);
}
$(document).ready(function(){
	getqrcode();
});