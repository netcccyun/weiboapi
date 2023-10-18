<?php
namespace lib;

class WeiboTool
{
    private $referrer = 'https://weibo.com/';
	private $cookie;
	public $cookiefail;

	function __construct($cookie){
		$this->cookie = $cookie;
	}

	public function checkCookie(){
		$url = 'https://weibo.com/ajax/statuses/config?type=push_active';
		$data = $this->get_curl($url, 0, $this->referrer, $this->cookie);
        $arr = json_decode($data, true);
        if(isset($arr['ok']) && $arr['ok']==1){
            return true;
        }
        return false;
	}

	public function getBasicInfo(){
		$url = 'https://weibo.com/ajax/setting/getBasicInfo';
		$data = $this->get_curl($url, 0, $this->referrer, $this->cookie);
		$arr = json_decode($data, true);
		if(isset($arr['ok']) && $arr['ok']==1){
			return ['code'=>0, 'data'=>$arr['data']];
        }elseif(isset($arr['ok']) && $arr['ok']==-100){
			$this->cookiefail = true;
			return ['code'=>-1, 'msg'=>'获取个人基本信息失败：COOKIE已失效'];
		}elseif(isset($arr['message'])){
			return ['code'=>-1, 'msg'=>'获取个人基本信息失败：'.$arr['message']];
		}else{
			return ['code'=>-1, 'msg'=>'获取个人基本信息失败：接口请求失败'];
		}
	}

	public function hotline(){
		$url = 'https://weibo.com/ajax/side/hotSearch';
		$data = $this->get_curl($url, 0, $this->referrer, $this->cookie);
        $arr = json_decode($data, true);
        if(isset($arr['ok']) && $arr['ok']==1 && isset($arr['data']['realtime'])){
			$list = [];
			foreach($arr['data']['realtime'] as $row){
				if($row['is_ad']) continue;
				$list[] = ['rank'=>$row['realpos'], 'category'=>$row['category'], 'content'=>$row['word'], 'time'=>$row['onboard_time'], 'num'=>$row['num'], 'label'=>$row['label_name'], 'mid'=>$row['mid']];
			}
			return ['code'=>0, 'data'=>$list];
        }elseif(isset($arr['ok']) && $arr['ok']==-100){
			$this->cookiefail = true;
			return ['code'=>-1, 'msg'=>'获取热搜列表失败：COOKIE已失效'];
		}elseif(isset($arr['message'])){
			return ['code'=>-1, 'msg'=>'获取热搜列表失败：'.$arr['message']];
		}else{
			return ['code'=>-1, 'msg'=>'获取热搜列表失败：接口请求失败'];
		}
	}

	public function parseVideo($oid){
		$page = '/tv/show/'.$oid;
		$url = 'https://weibo.com/tv/api/component?page='.urlencode($page);
		$post = 'data={"Component_Play_Playinfo":{"oid":"'.$oid.'"}}';
		$referer = 'https://weibo.com/tv/show/'.$oid;
		$data = $this->get_curl($url, $post, $referer, $this->cookie);
		$arr = json_decode($data, true);
		if(isset($arr['code']) && $arr['code']=='100000' && isset($arr['data']['Component_Play_Playinfo'])){
			$info = $arr['data']['Component_Play_Playinfo'];
			$result = ['title'=>$info['title'], 'author'=>$info['author'], 'author_id'=>$info['user']['id'], 'author_avatar'=>$info['avatar'], 'urls'=>$info['urls'], 'cover'=>$info['cover_image'], 'time'=>$info['real_date'], 'duration'=>$info['duration']];
			return ['code'=>0, 'data'=>$result];
		}elseif(isset($arr['msg'])){
			return ['code'=>-1, 'msg'=>'解析视频失败：'.$arr['msg']];
		}else{
			return ['code'=>-1, 'msg'=>'解析视频失败：接口请求失败'];
		}
	}

	public function getUserInfo($uid){
		$url = 'https://weibo.com/ajax/profile/info?uid='.$uid;
		$data = $this->get_curl($url, 0, $this->referrer, $this->cookie);
		$arr = json_decode($data, true);
		if(isset($arr['ok']) && $arr['ok']==1 && isset($arr['data']['user'])){
			$info = $arr['data']['user'];
			$result = ['uid'=>$info['idstr'], 'name'=>$info['screen_name'], 'avatar'=>$info['avatar_hd'], 'gender'=>$info['gender'], 'location'=>$info['location'], 'description'=>$info['description'], 'domain'=>$info['domain'], 'friends_count'=>$info['friends_count'], 'followers_count'=>$info['followers_count'], 'statuses_count'=>$info['statuses_count'], 'verified'=>$info['verified'], 'verified_reason'=>$info['verified_reason'], 'verified_type'=>$info['verified_type'], 'is_muteuser'=>$info['is_muteuser']];
			return ['code'=>0, 'data'=>$result];
		}elseif(isset($arr['ok']) && $arr['ok']==-100){
			$this->cookiefail = true;
			return ['code'=>-1, 'msg'=>'获取用户信息失败：COOKIE已失效'];
		}elseif(isset($arr['message'])){
			return ['code'=>-1, 'msg'=>'获取用户信息失败：'.$arr['message']];
		}else{
			return ['code'=>-1, 'msg'=>'获取用户信息失败：接口请求失败'];
		}
	}
	
	public function upload($file_path){
		$file_content = file_get_contents($file_path);
		$params = [
			'file_source' => '1',
			'cs' => crc32($file_content),
			'ent' => 'miniblog',
			'appid' => '339644097',
			'uid' => '',
			'raw_md5' => md5_file($file_path),
			'ori' => '1',
			'mpos' => '1',
			'pri' => '0',
			'request_id' => self::getMillisecond(),
			'file_size' => filesize($file_path)
		];
		$url = 'https://picupload.weibo.com/interface/upload.php?' . http_build_query($params);
		$data = $this->get_curl($url, $file_content, $this->referrer, $this->cookie);
        $arr = json_decode($data, true);
		if(isset($arr['ret']) && $arr['ret']==true){
			$pid = $arr['pic']['pid'];
			return ['code'=>0, 'data'=>'https://fc.sinaimg.cn/large/'.$pid.'.jpg'];
		}else{
			if($arr['errno'] == -1){
				$this->cookiefail = true;
				return ['code'=>-1, 'msg'=>'上传失败，COOKIE已失效'];
			}else{
				return ['code'=>-1, 'msg'=>'上传失败，请稍后再试'];
			}
		}
		return false;
	}

	static private function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    private function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0)
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
		curl_close($ch);
		return $ret;
	}

}
