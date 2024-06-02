<?php
declare(strict_types=1);

namespace work\cor\http\req;

use work\cor\facade\Request;

abstract class Base
{
    protected bool $jsonDecode = false;
    protected bool $resHeader = false;
    protected bool $ssl = false;
    protected bool $sendCookie = false;
    protected array $cookieList = [];
    protected int $timeout = 10;
    protected ?string $caPath = null;
    protected ?string $caInfo = null;

    public function loadingConfig(array $config)
    {
        if (!empty($config['timeout'])) {
            $this->timeout = intval($config['timeout']);
        }
        if (isset($config['decode'])) {
            $this->jsonDecode = (bool)$config['decode'];
        }
        if (isset($config['header'])) {
            $this->resHeader = (bool)$config['header'];
        }
        if (isset($config['ssl'])) {
            $this->ssl = (bool)$config['ssl'];
        }
        if (isset($config['cookie'])) {
            $this->sendCookie = (bool)$config['cookie'];
        }
        if (!empty($config['autoCookie'])) {
            $this->cookieList = $this->getAutoCookie();
        }
        if (!empty($config['caPath'])) {
            $this->caPath = $config['caPath'];
        }
    }

    /**
     * 获取cookie信息
     */
    protected function getAutoCookie(): array
    {
        $cookieData = Request::cookie();
        if (!is_array($cookieData)) {
            return [];
        }
        return $cookieData;
    }

    /**
     * 设置cookie值
     */
    public function setCookie(array $cookie = []): bool
    {
        foreach ($cookie as $key => $val) {
            if (!(is_string($val) || is_numeric($val))) {
                return false;
            }
            $this->cookieList[$key] = $val;
        }
        return true;
    }

    /**
     * 创建curl
     */
    protected function buildCurl(string $url, array $headers, array $setOptList = [])
    {
        $ch = curl_init($url);
        $defaultConfig = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->ssl,
            CURLOPT_SSL_VERIFYHOST => $this->ssl ? 0 : 2,
            CURLOPT_HEADER => $this->resHeader
        ];
        if (!empty($headers)) {
            $headers = $this->parseHeader($headers);
            $defaultConfig[CURLOPT_HTTPHEADER] = $headers;
        }
        if ($this->sendCookie) {
            $cookieInfo = $this->parseCookie($this->cookieList);
            $defaultConfig[CURLOPT_COOKIE] = $cookieInfo;
        }
        if ($this->ssl && !empty($this->caPath)) {
            $defaultConfig[CURLOPT_CAPATH] = $this->caPath;
        }
        if ($this->ssl && !empty($this->caInfo)) {
            $defaultConfig[CURLOPT_CAINFO] = $this->caInfo;
        }
        $diffList = [
            CURLOPT_URL,
            CURLOPT_HTTPHEADER
        ];
        foreach ($setOptList as $key => $val) {
            if (in_array($key, $diffList)) {
                continue;
            }
            $defaultConfig[$key] = $val;
        }
        curl_setopt_array($ch, $defaultConfig);
        return $ch;
    }


    /**
     * 请求头解析
     */
    protected function parseHeader(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $val) {
            if (is_array($val)) {
                $result[] = $val;
            } else if (!is_numeric($key) && !is_array($val)) {
                $result[] = "{$key}: {$val}";
            }
        }
        return $result;
    }

    /**
     * 解析cookie
     */
    protected function parseCookie(array $cookie): string
    {
        $cookieList = [];
        foreach ($cookie as $key => $val) {
            $cookieList[] = "{$key}={$val}";
        }
        return implode(';', $cookieList);
    }


    /**
     * 取结果
     * @param $ch
     * @return array
     */
    protected function resultInfo($ch, int $batch = -1): array
    {
        $exeInfo = $batch > -1 ? curl_multi_getcontent($ch) : curl_exec($ch);
        if (!curl_errno($ch)) {
            if ($this->resHeader) {
                $headerSize = intval(curl_getinfo($ch, CURLINFO_HEADER_SIZE));
                $res = [];
                $res['header'] = substr($exeInfo, 0, $headerSize);
                $res['response'] = substr($exeInfo, $headerSize);
                $res['response'] = $this->jsonDecode ? json_decode($res['response'], true) : $res['response'];

            } else {
                $res = [];
                $res['response'] = $this->jsonDecode ? json_decode($exeInfo, true) : $exeInfo;
            }
            $res['httpCode'] =  curl_getinfo($ch,CURLINFO_HTTP_CODE);
            $result = [
                'code' => 0,
                'msg' => 'ok',
                'data' => $res
            ];
        } else {
            $result = [
                'code' => -1,
                'msg' => curl_error($ch),
                'data' => ['httpCode' => curl_getinfo($ch,CURLINFO_HTTP_CODE),'response' => null]
            ];
        }
        return $result;
    }
}
