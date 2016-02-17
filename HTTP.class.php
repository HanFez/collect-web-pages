<?php
class HttpCline
{
    //初始化url信息
    protected $url = '';
    //path请求信息
    protected $path;
    //get请求信息
    protected $query = '';
    //post请求信息
    protected $data = '';
    //协议行
    protected $line;
    //host
    protected $host;
    //port端口
    protected $port;
    //锚点
    protected $fragment;
    //协议版本默认1.1
    protected $version = 'HTTP/1.1';
    //Request header
    protected $header = array();
    //整个请求句柄
    protected $fb;
    //错误
    protected $errno = 1;
    //错误内容
    protected $errstr = 'test';
    //超时时间
    protected $timeout = 3;
    //返回内容
    protected $response = '';

    public function __construct($url)
    {
        /*自定义错误*/
        set_error_handler(
            function ($errno, $errstr) {
                echo 'ErrorCode:' . $errno . ',ErrorInfo:' . $errstr;
                exit();
            },
            E_USER_NOTICE
        );
        /*自定义错误结束*/
        if ($this->isUrl($url)) {
            //解析url
            $this->url = parse_url($url);
            //获取主机
            $this->host = (isset($this->url['host']) && '' != $this->url['host']) ? $this->url['host'] : '';
            //获取端口
            $this->port = $this->getPort();
            //请求路径
            $this->path = isset($this->url['path']) ? $this->url['path'] : '/';
            //锚点
            $this->fragment = isset($this->url['fragment']) ? '#' . $this->url['fragment'] : '';
        } else {
            trigger_error("URL不合法", E_USER_NOTICE);
        }
    }
    //检测是否有效url
    public function isUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            return true;
        } else {
            return false;
        }
    }
    //设置请求行
    protected function setLine($method)
    {
        //协议行
        $this->line = $method . ' ' . $this->path . $this->query . $this->fragment . ' ' . $this->version;
    }

    //header
    public function setHeader($header = '')
    {
        if (is_array($header)) {
            $this->header = $header;
        } else {
            trigger_error("Header头不符合规范", E_USER_NOTICE);
        }
    }

    //获取url端口
    protected function getPort()
    {
        //判断是否存在端口号
        if (isset($this->url['port']) && '' != $this->url['port']) {
            $port = $this->url['port'];
            //判断是否存在请求类型
        } else if (isset($this->url['scheme'])) {
            //如果为http则为80端口
            if (preg_match('/http/sim', $this->url['scheme'])) {
                $port = 80;
                //如果为https则为443端口
            } else if (preg_match('/https/sim', $this->url['scheme'])) {
                $port = 443;
            }
        } else {
            $port = 80; //
        }
        return $port;
    }

    //get
    public function Get($querystring = '')
    {
        //传递过来的query
        $query = is_array($querystring) ? http_build_query($querystring) : $querystring;
        //url里的query
        $this->query = isset($this->url['query']) ? '?' . $this->url['query'] . '&' . $query : ((isset($query) && '' != $query) ? '?' . $query : '');
        $this->setLine('GET');
    }

    //post
    public function Post($data = '')
    {
        //post数据
        $this->data = is_array($data) ? http_build_query($data) : $data;
        $this->header[] = 'Content-Length: ' . strlen($this->data);
        //url里的querystring
        $this->query = isset($this->url['query']) ? '?' . $this->url['query'] : '';
        $this->setLine('POST');
    }

    //请求
    public function Request()
    {
        $this->header[] = 'Host: ' . $this->host;
        $this->header[] = 'Connection: close'; //keep-alive';//响应完就断开
        sort($this->header);
        array_unshift($this->header, $this->line);
        $this->header[] = '';
        $this->header[] = $this->data;
        $response_header = implode(PHP_EOL, $this->header);
        $this->fb = fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);
        //     //集阻塞/非阻塞模式流,$block==true则应用流模式
        stream_set_blocking($this->fb, true);
        //     //设置流的超时时间
        stream_set_timeout($this->fb, $this->timeout);
        @fwrite($this->fb, $response_header);
        //     //从封装协议文件指针中取得报头／元数据
        $status = stream_get_meta_data($this->fb);
        while (!feof($this->fb)) {
            $this->response .= fread($this->fb, 8096000);
        }
        return $this->response;
    }
}
$s = new HttpCline('http://127.0.0.1');
$s->setHeader(array('Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language:zh-CN,zh;q=0.8',
    'Cache-Control:max-age=0',
    'Proxy-Connection:keep-alive',
    'Upgrade-Insecure-Requests:1',
    'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36'));
$s->get();
$html = $s->Request();
echo $html;
// preg_match('/[]/')
// echo 0x31e;
