<?php

class Wodemo {
	//请求头信息
	public $header = array();
	//要获取地址的wodemo
	protected $url;
	//hpst
	protected $parse;
	//是否开启自动获取下一页
	public $status = false;
	//遍历页数
	public $limt = 1;
	//是都设置ssl
	public $ssl = false;

	function __construct($url = '') {
		$header = array();
		$this->url = $url;
		$this->parse = parse_url($this->url); //解析url
		$this->header = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
			'Accept-Language: zh-CN,zh;q=0.8',
			'Cookie: __cfduid=de7955049553ed982adfff031c47526861452832290; tz=America%2FLos_Angeles; username=chen; usernick=chen',
		);
		$this->header[] = 'Host: ' . $this->parse['host'];
		$this->header[] = 'Referer: ' . $this->url;
	}

	protected function curl($url) {
		$data = array(); //初始化空数组
		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_URL, $url); //需要请求的地址
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header); //设置header头
		// curl_setopt($ch, CURLOPT_PROXY, 'http://127.0.0.1:8089');
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置超时时间
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //设定是否显示头信息
		curl_setopt($ch, CURLOPT_HEADER, false); //设定是否输出页面内容
		curl_setopt($ch, CURLOPT_NOBODY, false); //是否设置为不显示html的body
		if ($this->ssl) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem'); // CA根证书（用来验证的网站证书是否是CA颁布）
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
		}
		$data['info'] = curl_exec($ch);
		$data['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $data;
	}
	//获取html
	protected function get_html($url) {
		$html = $this->curl($url); //获取列表
		$h = $html['info']; //获取html内容
		//判断是否开启了自动获取下页，并且最大页数是否已经循环完
		if ($this->status && $this->limt > 0) {
			preg_match('/\<a\shref=["|\'](\/cat\/\d+\?next=\d+)["|\']>下一页\<\/a\>/i', $h, $nexturl);
			//判断是否存在下页链接
			if (!empty($nexturl[1])) {
				$u = ($this->ssl) ? 'https://' : 'http://' . $this->parse['host'] . $nexturl[1];
				$h .= $this->get_html($u); //获取列表
				$this->limt -= 1; //
			}
		}
		return $h;
	}
	//获取原始列表
	public function get_list() {
		$html = $this->get_html($this->url); //获取列表
		//匹配列表
		preg_match_all('/(?<=\<li\>)&nbsp;\<a\shref=["|\'](http[s]?:\/\/.*?\/)file\/(\d+)\?cid\=\d+["|\']\>(.*?)\<\/a\>\<span\>\((mp3)\)\<\/span\>(?=\<\/li\>)/i', $html, $match, PREG_SET_ORDER);
		return $match;
	}
	//获取歌手
	protected function get_artist($file) {
		$i = explode(' - ', $file);
		$artist = !empty($i[0]) ? $i[0] : '未知';
		return $artist;
	}
	//获取歌名
	protected function get_title($file) {
		$i = explode(' - ', $file);
		$artist = !empty($i[1]) ? $i[1] : $file;
		return $artist;
	}
	//获取文件信息
	public function get_file() {
		$match = $this->get_list(); //获取列表
		$list = array();
		if (is_array($match)) {
			foreach ($match as $key => $value) {
				$list[$key]['title'] = $this->get_title($value[3]);
				$list[$key]['artist'] = $this->get_artist($value[3]);
				$list[$key]['album'] = $value[3] . '.' . $value[4];
				$list[$key]['cover'] = $value[1] . 'file/' . $value[2] . '/pkg/icon.jpeg';
				$list[$key]['mp3'] = $value[1] . 'down/' . date('Ymd') . '/' . $value[2] . '/' . urlencode($value[3]) . '.' . $value[4];
			}
		}
		return $list;
	}
}
$m = new Wodemo('https://musical.wodemo.com/cat/4561110');
$m->ssl = true;
print_r($m->get_file());