<?php
declare(strict_types=1);

namespace work\cor;

use work\cor\basics\face\RequestManageInterface;
use work\HelperFun;

/**
 * 请求参数获取
 * Class Request
 * @package work\cor
 */
class Request
{
    protected string $method = '';
    private $filter;
    private ?RequestManageInterface $requestManage = null;
    /**
     * @var false
     */
    private ?bool $mergeParam = null;
    private string $url = '';
    private string $baseUrl = '';
    private string $input = '';
    private ?array $put = null;
    private array $param = [];
    protected array $route = [];
    protected ?string $domain = null;
    protected ?string $pathinfo = null;

    public function __construct(?RequestManageInterface $requestManage = null)
    {
        if (is_null($requestManage)) {
            $requestManage = HelperFun::getContainer()->make(RequestManageInterface::class);
        }
        $this->requestManage = $requestManage;
    }


    /**
     * 是否为GET请求
     * @access public
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() == 'GET';
    }

    /**
     * 是否为POST请求
     * @access public
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() == 'POST';
    }

    /**
     * 是否为PUT请求
     * @access public
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @access public
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @access public
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @access public
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @access public
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * 是否为cli
     * @access public
     * @return bool
     */
    public function isCli(): bool
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 当前是否Ajax请求
     * @access public
     * @param bool $ajax true 获取原始ajax请求
     * @return bool
     */
    public function isAjax(bool $ajax = false): bool
    {

        $value = $this->server('HTTP_X_REQUESTED_WITH', '', 'strtolower');
        if (empty($value)) {
            $value = $this->header('X_REQUESTED_WITH', '');
        }
        $value = strtolower($value);
        $result = 'xmlhttprequest' == $value;
        if (true === $ajax) {
            return $result;
        } else {
            $result = $this->requestManage->getGet('_varAjax') !== null ? true : $result;
            $this->mergeParam = false;
            return $result;
        }
    }

    /**
     * @param bool $method
     * @return array|mixed|string
     */
    public function method(bool $method = false)
    {
        if (true === $method) {
            // 获取原始请求类型
            return $this->server('REQUEST_METHOD') ?: 'GET';
        } elseif (!$this->method) {
            $post = $this->requestManage->getPost();
            if (isset($post['_pretend'])) {
                $method = strtoupper($post['post']['_pretend']);
                if (in_array($method, ['GET', 'POST', 'DELETE', 'PUT', 'PATCH'])) {
                    $this->method = $method;
                    $this->{$this->method}($post['post']);
                } else {
                    $this->method = 'POST';
                }
                $this->requestManage->setPost('_pretend', null);
            } elseif (!empty($this->requestManage->getSever('HTTP_X_HTTP_METHOD_OVERRIDE'))) {
                $this->method = strtoupper($this->requestManage->getSever('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }
        return $this->method;
    }

    /**
     * 获取server参数
     * @access public
     * @param string|array $name 数据名称
     * @param string|null $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function server($name = '', string $default = null, $filter = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->requestManage->setSever($key, $value);
            }
            return $this->requestManage->getSever();
        }
        if (!empty($this->requestManage->getSever($name))) {
            return $this->requestManage->getSever($name);
        }
        $server = $this->requestManage->getSever();
        $data = [];
        foreach ($server as $key => $vl) {
            $key = strtoupper($key);
            $data[$key] = $vl;
        }
        return $this->input($data, false === $name ? false : $name, $default, $filter);
    }

    /**
     * 获取变量 支持过滤和默认值
     * @param array $data 数据源
     * @param string|false $name 字段名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤函数
     * @return mixed
     */
    public function input(array $data = [], $name = '', $default = null, $filter = '')
    {
        if (false === $name) {
            // 获取原始数据
            return $data;
        }
        $name = (string)$name;
        if ('' != $name) {
            // 解析name
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            } else {
                $type = 's';
            }
            // 按.拆分成多维数组进行判断
            foreach (explode('.', $name) as $val) {
                if (isset($data[$val])) {
                    $data = $data[$val];
                } else {
                    // 无输入数据，返回默认值
                    return $default;
                }
            }
            if (is_object($data)) {
                return $data;
            }
        }
        // 解析过滤器
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }
        if (isset($type) && $data !== $default) {
            // 强制类型转换
            $this->typeCast($data, $type);
        }
        return $data;
    }

    /**
     * 类型转换
     * @param $data
     * @param $type
     * @return void
     */
    private function typeCast(&$data, $type)
    {
        switch (strtolower($type)) {
            // 数组
            case 'a':
                $data = (array)$data;
                break;
            // 数字
            case 'd':
                $data = (int)$data;
                break;
            // 浮点
            case 'f':
                $data = (float)$data;
                break;
            // 布尔
            case 'b':
                $data = (boolean)$data;
                break;
            // 字符串
            case 's':
            default:
                if (is_scalar($data)) {
                    $data = (string)$data;
                } else {
                    throw new \InvalidArgumentException('variable type error：' . gettype($data));
                }
        }
    }

    /**
     * 设置或获取当前的过滤规则
     * @param mixed $filter 过滤规则
     * @return mixed
     */
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        } else {
            $this->filter = $filter;
        }
        return $this->filter;
    }

    /**
     * @param $filter
     * @param $default
     * @return array|false|string[]
     */
    protected function getFilter($filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ? $filter : $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array)$filter;
            }
        }
        if (!empty($default)) {
            $filter[] = $default;
        }
        return $filter;
    }

    /**
     * 递归过滤给定的值
     * @param mixed $value 键值
     * @param mixed $key 键名
     * @param array $filters 过滤方法+默认值
     * @return void
     */
    private function filterValue(&$value, $key, array $filters)
    {
        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                // 调用函数或者方法过滤
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (false !== strpos($filter, '/')) {
                    // 正则过滤
                    if (!preg_match($filter, $value)) {
                        // 匹配不成功返回默认值
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    // filter函数不存在时, 则使用filter_var进行过滤
                    // filter为非整形值时, 调用filter_id取得过滤id
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }
        $this->filterExp($value);
    }

    /**
     * 过滤表单中的表达式
     * @param mixed $value
     * @return void
     */
    public function filterExp(&$value)
    {
        // 过滤查询特殊字符
        if (is_string($value) && preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
        // TODO 其他安全过滤
    }

    /**
     * 获取cookie参数
     * @access public
     * @param string|array $name 数据名称
     * @param string|null $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function cookie($name = '', string $default = null, $filter = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->requestManage->setCookie($key, $value);
            }
            return $this->requestManage->getCookie();
        } elseif (!empty($name)) {
            $data = $this->requestManage->getCookie()[$name] ?? $default;
        } else {
            $data = $this->requestManage->getCookie();
        }
        // 解析过滤器
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }
        return $data;
    }

    /**
     * 获取cookie信息
     * @param string $name
     * @return mixed
     */
    public function getCookie(string $name = '')
    {
        return $this->requestManage->getCookie($name);
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl(): bool
    {
        $requestObj = $this->requestManage;
        if ('1' == $requestObj->getSever('HTTPS') || 'on' == strtolower($requestObj->getSever('HTTPS'))) {
            return true;
        } elseif ('https' == $requestObj->getSever('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $requestObj->getSever('SERVER_PORT')) {
            return true;
        } elseif ('https' == $requestObj->getSever('HTTP_X_FORWARDED_PROTO')) {
            return true;
        } else if ('https' == $this->header('REQUEST_SCHEME')) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile(): bool
    {
        $requestObj = $this->requestManage;
        $agent = $requestObj->getSever('HTTP_USER_AGENT') === null ?
            ($this->header('USER_AGENT') === null ? $this->server('USER_AGENT') : $this->header('USER_AGENT'))
            : $requestObj->getSever('HTTP_USER_AGENT');
        if ($requestObj->getSever('HTTP_VIA') && stristr($requestObj->getSever('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($requestObj->getSever('HTTP_ACCEPT') && strpos(strtoupper($requestObj->getSever('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($requestObj->getSever('HTTP_X_WAP_PROFILE') || $requestObj->getSever('HTTP_PROFILE')) {
            return true;
        } elseif ($agent && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $agent)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置或获取当前完整URL 包括QUERY_STRING
     * @access public
     * @param string|null $url URL地址 true 带域名获取
     * @return string|object
     */
    public function url(string $url = null)
    {
        $server = $this->requestManage;
        if (!is_null($url) && true !== $url) {
            $this->url = $url;
            return $this;
        } elseif (!$this->url) {
            if (IS_CLI) {
                $this->url = $server['argv'][1] ?? '';
            } elseif ($server->getSever('HTTP_X_REWRITE_URL')) {
                $this->url = $server->getSever('HTTP_X_REWRITE_URL');
            } elseif ($server->getSever('REQUEST_URI')) {
                $this->url = $server->getSever('REQUEST_URI');
            } elseif ($server->getSever('ORIG_PATH_INFO')) {
                $this->url = $server->getSever('ORIG_PATH_INFO') . (!empty($server->getSever('QUERY_STRING')) ? '?' . $server->getSever('QUERY_STRING') : '');
            } else if ($server->getSever('PATH_INFO')) {
                $this->url = $server->getSever('PATH_INFO');
            } else {
                $this->url = '';
            }
        }
        return true === $url ? $this->domain() . $this->url : $this->url;
    }

    /**
     * 设置或获取当前URL 不含QUERY_STRING
     * @access public
     * @param string|null $url URL地址
     * @return string|object
     */
    public function baseUrl(string $url = null)
    {
        if (!is_null($url) && true !== $url) {
            $this->baseUrl = $url;
            return $this;
        } elseif (!$this->baseUrl) {
            $str = $this->url();
            $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
        }
        return true === $url ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * 设置或获取当前包含协议的域名
     * @access public
     * @param string|null $domain 域名
     * @return string
     */
    public function domain(string $domain = null): ?string
    {
        if (!is_null($domain)) {
            $this->domain = $domain;
            return $this->domain;
        } elseif (!$this->domain) {
            $this->domain = $this->scheme() . '://' . $this->host();
        }
        return $this->domain;
    }

    /**
     * 当前URL地址中的scheme参数
     * @access public
     * @return string
     */
    public function scheme(): string
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 当前请求的host
     * @access public
     * @param bool $strict true 仅仅获取HOST
     * @return string
     */
    public function host(bool $strict = false)
    {
        if ($this->requestManage->getSever('HTTP_X_REAL_HOST')) {
            $host = $this->requestManage->getSever('HTTP_X_REAL_HOST');
        } else if ($this->header('X_REAL_HOST')) {
            $host = $this->header('X_REAL_HOST');
        } else {
            $host = $this->server('HTTP_HOST');
        }
        return true === $strict && strpos($host, ':') ? strstr($host, ':', true) : $host;
    }

    /**
     * 获取当前请求URL的pathing信息（含URL后缀）
     * @access public
     * @return string
     */
    public function pathInfo(): ?string
    {
        if (is_null($this->pathinfo)) {
            $get = $this->requestManage->getGet();
            if (isset($get['s'])) {
                $this->requestManage->setSever('PATH_INFO', $get['s']);
                $this->requestManage->setGet('s', null);
            }
            // 分析PATHINFO信息
            if (empty($this->requestManage->getSever('PATH_INFO'))) {
                foreach (['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'] as $type) {
                    if (!empty($this->requestManage->getSever($type))) {
                        $this->requestManage->setSever('PATH_INFO', (0 === strpos($this->requestManage->getSever($type), $this->requestManage->getSever('SCRIPT_NAME'))) ?
                            substr($this->requestManage->getSever($type), strlen($this->requestManage->getSever('SCRIPT_NAME'))) : $this->requestManage->getSever($type));
                        break;
                    }
                }
            }
            $this->pathinfo = empty($this->requestManage->getSever('PATH_INFO')) ? '/' : ltrim($this->requestManage->getSever('PATH_INFO'), '/');
        }
        return $this->pathinfo;
    }

    /**
     * 获取当前请求的参数
     * @access public
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {

        if (empty($this->mergeParam)) {
            $method = $this->method(true);
            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }
            // 当前请求参数和URL地址中的参数合并
            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));
            $this->mergeParam = true;
        }
        if (true === $name) {
            $file = $this->file();
            $data = is_array($file) ? array_merge($this->param, $file) : $this->param;
            return $this->input($data, '', $default, $filter);
        }
        return $this->input($this->param, $name, $default, $filter);
    }

    /**
     * 设置获取GET参数
     * @access public
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            $this->param = [];
            $this->mergeParam = false;
            foreach ($name as $key => $value) {
                $this->requestManage->setGet($key, $value);
            }
            return $this->requestManage->getGet();
        }
        return $this->input($this->requestManage->getGet(), $name, $default, $filter);
    }

    /**
     * 设置获取POST参数
     * @access public
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            $this->param = [];
            $this->mergeParam = false;
            foreach ($name as $key => $value) {
                $this->requestManage->setPost($key, $value);
            }
            return $this->requestManage->getPost();
        }
        return $this->input($this->requestManage->getPost(), $name, $default, $filter);
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @access public
     * @return string
     */
    public function contentType(): string
    {
        $contentType = $this->server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }
        return '';
    }

    /**
     * 设置获取PUT参数
     * @access public
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_null($this->put)) {
            $content = $this->input;
            if (false !== strpos($this->contentType(), 'application/json')) {
                $this->put = (array)json_decode($content, true);
            } else {
                parse_str($content, $this->put);
            }
        }
        if (is_array($name)) {
            $this->param = [];
            $this->mergeParam = false;
            return $this->put = is_null($this->put) ? $name : array_merge($this->put, $name);
        }
        return $this->input($this->put, $name, $default, $filter);
    }

    /**
     * 获取当前的Header
     * @access public
     * @param string $name header名称
     * @param string|null $default 默认值
     * @return string|array
     */
    public function header(string $name = '', string $default = null)
    {
        if ('' === $name) {
            return $this->requestManage->getHeader();
        }
        $name = str_replace('_', '-', $name);
        return $this->requestManage->getHeader($name) ?? $default;
    }

    /**
     * 设置获取路由参数
     * @access public
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            $this->param = [];
            $this->mergeParam = false;
            return $this->route = array_merge($this->route, $name);
        }
        return $this->input($this->route, $name, $default, $filter);
    }


    /**
     * 获取上传的文件信息
     * @access public
     * @param string|array $name 名称
     * @return null|array|File
     */
    public function file($name = '')
    {
        if (is_array($name)) {
            return array_merge($this->requestManage->getFiles(), $name);
        }
        $files = $this->requestManage->getFiles();
        if (!empty($files)) {
            // 处理上传文件
            $array = [];
            foreach ($files as $key => $file) {
                if (is_array($file['name'])) {
                    $item = [];
                    $keys = array_keys($file);
                    $count = count($file['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if (empty($file['tmp_name'][$i]) || !is_file($file['tmp_name'][$i])) {
                            continue;
                        }
                        $temp['key'] = $key;
                        foreach ($keys as $_key) {
                            $temp[$_key] = $file[$_key][$i];
                        }
                        $item[] = (new File($temp['tmp_name']))->setUploadInfo($temp);
                    }
                    $array[$key] = $item;
                } else {
                    if ($file instanceof File) {
                        $array[$key] = $file;
                    } else {
                        if (empty($file['tmp_name']) || !is_file($file['tmp_name'])) {
                            continue;
                        }
                        $array[$key] = (new File($file['tmp_name']))->setUploadInfo($file);
                    }
                }
            }
            if (strpos($name, '.')) {
                list($name, $sub) = explode('.', $name);
            }
            if ('' === $name) {
                // 获取全部文件
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }
        return null;
    }

    /**
     * 获取file信息
     * @param string $name
     * @return array|null
     */
    public function getFileInfo(string $name = ''): ?array
    {
        return $this->requestManage->getFiles($name);
    }

    /**
     * postJson
     * @return null|array|string
     */
    public function postJson(string $name = '', $default = null, $filter = '')
    {
        $jsonData = [];
        if (!empty($this->requestManage->getRawContent())) {
            $jsonData = json_decode($this->requestManage->getRawContent(), true);
        }
        return $this->input($jsonData, $name, $default, $filter);
    }

    /**
     * 获取路由解析
     */
    public function getRouteUri(): string
    {
        return $this->requestManage->getRouteUri();
    }

    /**
     * array
     */
    public function getRequestAll(): array
    {
        return $this->requestManage->getRequestAll();
    }

    /**
     * @return RequestManageInterface
     */
    public function getRequestObj(): RequestManageInterface
    {
        return $this->requestManage;
    }


    /**
     * 获取客户端ip
     */
    public function getClientIp(): ?string
    {
        $ipInfo = $this->requestManage->getClientIp();
        if (!$ipInfo) {
            return null;
        }
        return filter_var($ipInfo, FILTER_VALIDATE_IP) ? $ipInfo : null;
    }

}