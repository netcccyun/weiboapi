<?php
namespace lib;

class WeiboLogin
{
	private $referrer = 'https://weibo.com/login.php';
	private $errmsg;

	private function jsonp_decode($jsonp, $assoc = false)
	{
		$jsonp = trim($jsonp);
		if (isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
			$begin = strpos($jsonp, '(');
			if (false !== $begin) {
				$end = strrpos($jsonp, ')');
				if (false !== $end) {
					$jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
				}
			}
		}
		return json_decode($jsonp, $assoc);
	}

	//获取扫码登录二维码
	public function getqrcode()
	{
		$url = 'https://login.sina.com.cn/sso/qrcode/image?entry=weibo&size=180&callback=STK_'.time().'0000';
		$data = $this->get_curl($url, 0, $this->referrer);
		$arr = $this->jsonp_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 20000000) {
			$imgurl = $arr['data']['image'];
			parse_str(parse_url($imgurl, PHP_URL_QUERY), $query_arr);
			$link = $query_arr['data'];
			return array('code' => 0, 'imgurl' => $arr['data']['image'], 'qrid' => $arr['data']['qrid'], 'link' => $link);
		} elseif (isset($arr['msg'])) {
			return array('code' => -1, 'msg' => '获取二维码失败，' . $arr['msg']);
		} else {
			return array('code' => -1, 'msg' => '获取二维码失败');
		}
	}

	//扫码登录操作
	public function qrlogin($qrid)
	{
		if (empty($qrid)) return array('code' => -1, 'msg' => 'qrid不能为空');
		$url = 'https://login.sina.com.cn/sso/qrcode/check?entry=weibo&qrid='.$qrid.'&callback=STK_'.time().'0000';
		$data = $this->get_curl($url, 0, $this->referrer);
		$arr = $this->jsonp_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 20000000) {
			$alt = $arr['data']['alt'];
			$login_url = 'https://login.sina.com.cn/sso/login.php?entry=weibo&returntype=TEXT&crossdomain=1&cdult=3&domain=weibo.com&alt='.urlencode($alt).'&savestate=30';
			$result = $this->login_getcookie($login_url);
			return $result;
		} elseif ($arr['retcode'] == 50114001) {
			return array('code' => 1, 'msg' => '请用最新版微博客户端扫码');
		} elseif ($arr['retcode'] == 50114002) {
			return array('code' => 2, 'msg' => '成功扫描，请在手机点击确认以登录');
		} elseif ($arr['retcode'] == 50114003 || $arr['retcode'] == 50114004) {
			return array('code' => 3, 'msg' => '该二维码已过期，请重新扫描');
		} elseif (isset($arr['msg'])) {
			return array('code' => -1, 'msg' => $arr['msg']);
		} else {
			return array('code' => -1, 'msg' => '登录失败，原因未知');
		}
	}

	//通用登录后获取cookie
	private function login_getcookie($url)
	{
		$url = str_replace('returntype=CROSSDOMAIN_BY_LOCATION&', 'returntype=TEXT&', $url);
		$url = str_replace('returntype=META&', 'returntype=TEXT&', $url);
		$data = $this->get_curl($url, 0, $this->referrer, 0, 1, 1);
		$arr = json_decode($data['body'], true);
		if (isset($arr['retcode']) && $arr['retcode'] == 0) {
			$cookie=[];
			preg_match_all('/Set-Cookie: (.*?);/i',$data['header'],$matchs);
			foreach ($matchs[1] as $val) {
				if(strpos($val, '=deleted') || substr($val,-1)=='=') continue;
				$key = substr($val, 0, strpos($val, '='));
				$cookie[$key]=substr($val, strpos($val, '=')+1);
			}

			if(isset($arr['ticket'])){
				$ticket = $arr['ticket'];
			}elseif(isset($arr['crossDomainUrlList'])){
				foreach($arr['crossDomainUrlList'] as $row){
					if(strpos($row, '//passport.weibo.com/')){
						parse_str(parse_url($row, PHP_URL_QUERY), $queryArr);
						$ticket = $queryArr['ticket'];
						break;
					}
				}
			}
			if(!$ticket){
				return array('code' => -1, 'msg' => '登录成功，获取微博ticket失败');
			}
			$wbcookie = $this->weibosso($ticket);
			if(!$wbcookie){
				return array('code' => -1, 'msg' => $this->errmsg);
			}
			return array('code' => 0, 'cookie' => $cookie, 'wbcookie' => $wbcookie, 'uid' => $arr['uid'], 'nick' => $arr['nick']);
		} elseif (isset($arr['retcode'])) {
			return array('code' => -1, 'msg' => '登录成功，获取用户信息失败（'.$arr['retcode'].'）');
		} else {
			return array('code' => -1, 'msg' => '登录成功，获取用户信息失败');
		}
	}

	//获取预登录数据
	public function prelogin($user)
	{
		$url = 'https://login.sina.com.cn/sso/prelogin.php?entry=weibo&callback=sinaSSOController.preloginCallBack&su='.urlencode(base64_encode($user)).'&rsakt=mod&checkpin=1&client=ssologin.js(v1.4.19)';
		$data = $this->get_curl($url, 0, $this->referrer);
		$arr = $this->jsonp_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 0 && isset($arr['pcid'])) {
			if(isset($arr['smsurl'])){
				parse_str(parse_url($arr['smsurl'], PHP_URL_QUERY), $query_arr);
				$arr['smstoken'] = $query_arr['s'];
			}
			return array('code' => 0, 'data' => $arr);
		}elseif(isset($arr['msg'])){
			return array('code' => -1, 'msg' => '获取预登录数据失败，'.$arr['msg']);
		}else{
			return array('code' => -1, 'msg' => '获取预登录数据失败');
		}
	}

	//获取验证码
	public function getpin($pcid)
	{
		if (empty($pcid)) return '';
		$url = 'https://login.sina.com.cn/cgi/pin.php?r='.rand(11111111,99999999).'&s=0&p='.urlencode($pcid);
		$cookie = 'ULOGIN_IMG='.$pcid;
		return $this->get_curl($url, 0, $this->referrer, $cookie);
	}

	//密码登录
	public function login($user, $pwd, $servertime, $nonce, $rsakv, $pcid, $door)
	{
		$url = 'https://login.sina.com.cn/sso/login.php?client=ssologin.js(v1.4.19)';
		$param = [
			'entry' => 'weibo',
			'gateway' => '1',
			'from' => '',
			'savestate' => '7',
			'qrcode_flag' => 'false',
			'useticket' => '1',
			'vsnf' => '1',
			'su' => base64_encode($user),
			'service' => 'miniblog',
			'servertime' => $servertime,
			'nonce' => $nonce,
			'pwencode' => 'rsa2',
			'rsakv' => $rsakv,
			'sp' => $pwd,
			'sr' => '1536*960',
			'encoding' => 'UTF-8',
			'url' => 'https://weibo.com/ajaxlogin.php?framelogin=1&callback=parent.sinaSSOController.feedBackUrlCallBack',
			'returntype' => 'TEXT'
		];
		if(!empty($pcid)){
			$param += [
				'pcid' => $pcid,
				'door' => $door
			];
			$cookie = 'ULOGIN_IMG='.$pcid;
		}
		$data = $this->get_curl($url, http_build_query($param), $this->referrer, $cookie, 1, 1);
		$arr = json_decode($data['body'], true);
		if (isset($arr['retcode']) && $arr['retcode'] == 0) {
			$cookie=[];
			preg_match_all('/Set-Cookie: (.*?);/i',$data['header'],$matchs);
			foreach ($matchs[1] as $val) {
				if(strpos($val, '=deleted') || substr($val,-1)=='=') continue;
				$key = substr($val, 0, strpos($val, '='));
				$cookie[$key]=substr($val, strpos($val, '=')+1);
			}
			$ticket = $arr['ticket'];
			$wbcookie = $this->weibosso($ticket);
			if(!$wbcookie){
				return array('code' => -1, 'msg' => $this->errmsg);
			}
			return array('code' => 0, 'cookie' => $cookie, 'wbcookie' => $wbcookie, 'uid' => $arr['uid'], 'nick' => $arr['nick']);
		}elseif($arr['retcode'] == 2071 && isset($arr['protection_url'])){
			$protection_url = urldecode($arr['protection_url']);
			parse_str(parse_url($protection_url, PHP_URL_QUERY), $query_arr);
			$token = $query_arr['token'];
			$data2 = $this->get_curl($protection_url, 0, $this->referrer);
			preg_match('!<input name="encrypt_mobile".*?value="(.*?)".*?<span>(.*?)</span>!s', $data2, $match);
			if($match[0]){
				return array('code' => 1, 'msg' => $arr['reason'], 'token'=>$token, 'mobile'=>$match[2], 'encrypt_mobile'=>$match[1]);
			}else{
				return array('code' => -1, 'msg' => $arr['reason']);
			}
		}elseif($arr['retcode'] == 4049){
			return array('code' => 2, 'msg' => $arr['reason']);
		}elseif(isset($arr['reason'])){
			return array('code' => -1, 'msg' => $arr['reason']);
		}else{
			return array('code' => -1, 'msg' => '登录失败，原因未知');
		}
	}

	//登录异常-发送手机验证码
	public function sendcode($token, $encrypt_mobile)
	{
		$url = 'https://passport.weibo.com/protection/mobile/sendcode?token='.$token;
		$post = 'encrypt_mobile='.$encrypt_mobile;
		$data = $this->get_curl($url, $post, $this->referrer);
		$arr = json_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 20000000) {
			return array('code' => 0, 'msg' => 'succ');
		} elseif (isset($arr['msg'])) {
			return array('code' => -1, 'msg' => $arr['msg']);
		} else {
			return array('code' => -1, 'msg' => '获取验证码失败，原因未知');
		}
	}

	//登录异常-提交手机验证码
	public function confirmcode($token, $encrypt_mobile, $code)
	{
		$url = 'https://passport.weibo.com/protection/mobile/confirm?token='.$token;
		$post = 'encrypt_mobile='.$encrypt_mobile.'&code='.$code;
		$data = $this->get_curl($url, $post, $this->referrer);
		$arr = json_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 20000000) {
			$result = $this->login_getcookie($arr['data']['redirect_url']);
			return $result;
		} elseif (isset($arr['msg'])) {
			return array('code' => -1, 'msg' => $arr['msg']);
		} else {
			return array('code' => -1, 'msg' => '验证失败，原因未知');
		}
	}


	//短信登录-发送手机验证码
	public function sendsms($mobile, $token)
	{
		$url = 'https://login.sina.com.cn/sso/msglogin?entry=weibo&mobile='.$mobile.'&s='.$token.'&_t=1';
		$data = $this->get_curl($url, 0, $this->referrer);
		$arr = json_decode($data, true);
		if (isset($arr['retcode']) && $arr['retcode'] == 20000000) {
			return array('code' => 0, 'msg' => 'succ');
		} elseif (isset($arr['msg'])) {
			return array('code' => -1, 'msg' => $arr['msg']);
		} else {
			return array('code' => -1, 'msg' => '获取验证码失败，原因未知');
		}
	}

	//手机验证码登录
	public function smslogin($user, $pwd, $servertime, $nonce, $rsakv)
	{
		$url = 'https://login.sina.com.cn/sso/login.php?client=ssologin.js(v1.4.19)';
		$param = [
			'entry' => 'weibo',
			'gateway' => '1',
			'from' => '',
			'savestate' => '7',
			'qrcode_flag' => 'false',
			'useticket' => '1',
			'cfrom' => '1',
			'vsnf' => '1',
			'su' => base64_encode($user),
			'service' => 'miniblog',
			'servertime' => $servertime,
			'nonce' => $nonce,
			'pwencode' => 'rsa2',
			'rsakv' => $rsakv,
			'sp' => $pwd,
			'sr' => '1536*960',
			'encoding' => 'UTF-8',
			'url' => 'https://weibo.com/ajaxlogin.php?framelogin=1&callback=parent.sinaSSOController.feedBackUrlCallBack',
			'returntype' => 'TEXT'
		];
		$data = $this->get_curl($url, http_build_query($param), $this->referrer, 0, 1, 1);
		$arr = json_decode($data['body'], true);
		if (isset($arr['retcode']) && $arr['retcode'] == 0) {
			$cookie=[];
			preg_match_all('/Set-Cookie: (.*?);/i',$data['header'],$matchs);
			foreach ($matchs[1] as $val) {
				if(strpos($val, '=deleted') || substr($val,-1)=='=') continue;
				$key = substr($val, 0, strpos($val, '='));
				$cookie[$key]=substr($val, strpos($val, '=')+1);
			}
			$ticket = $arr['ticket'];
			$wbcookie = $this->weibosso($ticket);
			if(!$wbcookie){
				return array('code' => -1, 'msg' => $this->errmsg);
			}
			return array('code' => 0, 'cookie' => $cookie, 'wbcookie' => $wbcookie, 'uid' => $arr['uid'], 'nick' => $arr['nick']);
		}elseif(isset($arr['reason'])){
			if($arr['retcode'] == 101) $arr['reason']='验证码输入有误';
			return array('code' => -1, 'msg' => $arr['reason']);
		}else{
			return array('code' => -1, 'msg' => '登录失败，原因未知');
		}
	}

	public function cookielogin($cookie){
		$cookies = '';
		foreach($cookie as $key=>$value){
			$cookies .= $key.'='.$value.'; ';
		}
		$url = 'https://login.sina.com.cn/sso/login.php?url=https%3A%2F%2Fwww.weibo.com%2F&_rand='.time().'&gateway=1&service=miniblog&entry=miniblog&useticket=1&returntype=TEXT&sudaref=&_client_version=0.6.33';
		$data = $this->get_curl($url, 0, $this->referrer, $cookies, 1, 1);
		$arr = json_decode($data['body'], true);
		if (isset($arr['retcode']) && $arr['retcode'] == 0) {
			preg_match_all('/Set-Cookie: (.*?);/i',$data['header'],$matchs);
			foreach ($matchs[1] as $val) {
				if(strpos($val, '=deleted') || substr($val,-1)=='=') continue;
				$key = substr($val, 0, strpos($val, '='));
				$cookie[$key]=substr($val, strpos($val, '=')+1);
			}
			$ticket = $arr['ticket'];
			$wbcookie = $this->weibosso($ticket);
			if(!$wbcookie){
				return array('code' => -1, 'msg' => $this->errmsg);
			}
			return array('code' => 0, 'cookie' => $cookie, 'wbcookie' => $wbcookie, 'uid' => $arr['uid'], 'nick' => $arr['nick']);
		} elseif (isset($arr['retcode'])) {
			return array('code' => -1, 'msg' => '登录失败，登录COOKIE已失效（'.$arr['retcode'].'）');
		} else {
			return array('code' => -1, 'msg' => '登录失败，登录COOKIE已失效');
		}
	}

	private function weibosso($ticket){
		$ssosavestate = time()+2592000;
		$url = 'https://passport.weibo.com/wbsso/login?ticket='.$ticket.'&ssosavestate='.$ssosavestate.'&callback=sinaSSOController.doCrossDomainCallBack&scriptId=ssoscript0&client=ssologin.js(v1.4.2)';
		$data = $this->get_curl($url, 0, $this->referrer, 0, 1, 1);
		$arr = $this->jsonp_decode($data['body'], true);
		if (isset($arr['result']) && $arr['result']==true) {
			preg_match_all('/Set-Cookie: (.*?);/i',$data['header'],$matchs);
			$cookie = [];
			foreach ($matchs[1] as $val) {
				if(strpos($val, '=deleted') || substr($val,-1)=='=') continue;
				$key = substr($val, 0, strpos($val, '='));
				$cookie[$key]=substr($val, strpos($val, '=')+1);
			}
			return $cookie;
		} elseif (!empty($arr['reason'])) {
			$this->errmsg = '登录成功，获取微博cookie失败（'.$arr['reason'].'）';
			return false;
		} else {
			$this->errmsg = '登录成功，获取微博cookie失败';
			return false;
		}
	}

	public function qq_getqrcode(){
		//$url='https://ssl.ptlogin2.qq.com/ptqrshow?appid=716027609&e=2&l=M&s=4&d=72&v=4&t=0.2616844'.time().'&daid=383&pt_3rd_aid=101019034';
		$url='https://xui.ptlogin2.qq.com/ssl/ptqrshow?s=8&e=0&appid=716027609&type=1&t=0.492909'.time().'&daid=383&pt_3rd_aid=101019034';
		$refer='https://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=716027609&daid=383&style=33&login_text=%E7%99%BB%E5%BD%95&hide_title_bar=1&hide_border=1&target=self&s_url=https%3A%2F%2Fgraph.qq.com%2Foauth2.0%2Flogin_jump&pt_3rd_aid=101019034&pt_feedback_link=https%3A%2F%2Fsupport.qq.com%2Fproducts%2F77942%3FcustomInfo%3Dweibo.com.appid101019034&theme=2&verify_theme=';
		$data=$this->get_curl($url,0,$refer,0,1,1);
		preg_match('/qrsig=(.*?);/',$data['header'],$match);
		if($qrsig=$match[1]){
			$arr = $this->jsonp_decode($data['body'], true);
			return array('code'=>0,'qrsig'=>$qrsig,'qrcode'=>$arr['qrcode']);
		}
		else{
			return array('code'=>-1,'msg'=>'二维码获取失败');
		}
	}

	public function qq_qrlogin($qrsig){
		if(empty($qrsig))return array('code'=>-1,'msg'=>'qrsig不能为空');
		$url='https://ssl.ptlogin2.qq.com/ptqrlogin?u1=https%3A%2F%2Fgraph.qq.com%2Foauth2.0%2Flogin_jump&ptqrtoken='.$this->getqrtoken($qrsig).'&ptredirect=0&h=1&t=1&g=1&from_ui=1&ptlang=2052&action=4-1-'.time().'000&js_ver=22072900&js_type=1&login_sig=&pt_uistyle=40&aid=716027609&daid=383&pt_3rd_aid=101019034&';
		$refer='https://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=716027609&daid=383&style=33&login_text=%E7%99%BB%E5%BD%95&hide_title_bar=1&hide_border=1&target=self&s_url=https%3A%2F%2Fgraph.qq.com%2Foauth2.0%2Flogin_jump&pt_3rd_aid=101019034&pt_feedback_link=https%3A%2F%2Fsupport.qq.com%2Fproducts%2F77942%3FcustomInfo%3Dweibo.com.appid101019034&theme=2&verify_theme=';
		$ret = $this->get_curl($url,0,$refer,'qrsig='.$qrsig.'; ');
		if(preg_match("/ptuiCB\('(.*?)'\)/", $ret, $arr)){
			$r=explode("','",str_replace("', '","','",$arr[1]));
			if($r[0]==0){
				preg_match('/uin=(\d+)&/',$ret,$uin);
				$uin=$uin[1];
				$data=$this->get_curl($r[2],0,$refer,0,1);
				if($data) {
					$cookie='';
					preg_match_all('/Set-Cookie: (.*?);/i',$data,$matchs);
					foreach ($matchs[1] as $val) {
						if(substr($val,-1)=='=')continue;
						$cookie.=$val.'; ';
					}
					preg_match('/p_skey=(.*?);/',$cookie,$pskey);
					$cookie = substr($cookie,0,-2);
					$data=$this->get_curl('https://passport.weibo.com/othersitebind/authorize?entry=miniblog&site=qq',0,0,0,1);
					preg_match('/crossidccode=(.*?);/',$data,$match);
					if($crossidccode = $match[1]){
						$url = 'https://graph.qq.com/oauth2.0/authorize';
						$post = 'response_type=code&client_id=101019034&redirect_uri=https%3A%2F%2Fpassport.weibo.com%2Fothersitebind%2Fbind%3Fsite%3Dqq%26state%3D'.$crossidccode.'%26bentry%3Dminiblog%26wl%3D&scope=get_info%2Cget_user_info&state=&switch=&from_ptlogin=1&src=1&update_auth=1&openapi=80901010&g_tk='.$this->getGTK($pskey[1]).'&auth_time='.time().'304&ui=E4077228-8A59-4020-A957-B5830A9509D3';
						$data=$this->get_curl($url,$post,0,$cookie,1);
						preg_match("/Location: (.*?)\r\n/i", $data, $match);
						if($redirect_uri = $match[1]){
							return array('code'=>0,'msg'=>'succ','uin'=>$uin,'redirect_uri'=>$redirect_uri,'crossidccode'=>$crossidccode);
						}else{
							return array('code'=>-1,'uin'=>$uin,'msg'=>'登录QQ成功，回调网站失败！');
						}
					}else{
						return array('code'=>-1,'uin'=>$uin,'msg'=>'登录QQ成功，获取crossidccode失败！');
					}
				}else{
					return array('code'=>-1,'uin'=>$uin,'msg'=>'登录QQ成功，获取相关信息失败！');
				}
			}elseif($r[0]==65){
				return array('code'=>1,'msg'=>'二维码已失效。');
			}elseif($r[0]==66){
				return array('code'=>2,'msg'=>'二维码未失效。');
			}elseif($r[0]==67){
				return array('code'=>3,'msg'=>'正在验证二维码。');
			}else{
				return array('code'=>-1,'msg'=>$r[4]);
			}
		}else{
			return array('code'=>-1,'msg'=>$ret);
		}
	}

	public function qq_connect($redirect_uri, $crossidccode){
		if(empty($redirect_uri) || parse_url($redirect_uri, PHP_URL_HOST)!='passport.weibo.com')return array('code'=>-1,'msg'=>'回调地址错误');
		if(empty($crossidccode))return array('code'=>-1,'msg'=>'crossidccode不能为空');
		$data=$this->get_curl($redirect_uri,0,0,'crossidccode='.$crossidccode,1);
		preg_match("/Location: (.*?)\r\n/i", $data, $match);
		if($login_url = $match[1]){
			if(strpos($login_url, '/sso/login.php?')){
				$result = $this->login_getcookie($login_url);
				return $result;
			}else{
				return array('code'=>-1,'msg'=>'该QQ未绑定微博账号！');
			}
		}else{
			return array('code'=>-1,'msg'=>'登录QQ成功，获取微博登录信息失败！');
		}
	}

	private function getqrtoken($qrsig){
        $len = strlen($qrsig);
        $hash = 0;
        for($i = 0; $i < $len; $i++){
            $hash += (($hash << 5) & 2147483647) + ord($qrsig[$i]) & 2147483647;
			$hash &= 2147483647;
        }
        return $hash & 2147483647;
    }
	private function getGTK($skey){
        $len = strlen($skey);
        $hash = 5381;
        for ($i = 0; $i < $len; $i++) {
            $hash += ($hash << 5 & 2147483647) + ord($skey[$i]) & 2147483647;
            $hash &= 2147483647;
        }
        return $hash & 2147483647;
    }


	private function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $split = 0)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: application/json";
		$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: close";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		if ($header) {
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
		}
		if ($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		if ($referer) {
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36');
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($ch);
		if($split){
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($ret, 0, $headerSize);
			$body = substr($ret, $headerSize);
			$ret=array();
			$ret['header']=$header;
			$ret['body']=$body;
		}
		curl_close($ch);
		return $ret;
	}
}
