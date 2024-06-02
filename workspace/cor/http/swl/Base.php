<?php
declare(strict_types=1);

namespace work\cor\http\swl;

use Swoole\Coroutine\Http\Client;
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
    protected bool $keepAlive = false;

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
        if (isset($config['keepAlive'])) {
            $this->keepAlive = (bool)$config['keepAlive'];
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
     * 解析url信息
     * @param string $url
     * @return array
     */
    protected function getUrlInfo(string $url): array
    {
        $urlInfo = parse_url($url);
        $scheme = $urlInfo['scheme'] ?? '';
        return [
            "scheme" => $scheme,
            "host" => $urlInfo['host'],
            "port" => intval($urlInfo['port'] ?? (strtolower($scheme) === 'https' ? 443 : 80)),
            "path" => $urlInfo['path'] ?? '/',
            "query" => $urlInfo['query'] ?? ''
        ];
    }


    /**
     * 请求调用
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @param string $data
     * @return array|null
     */
    protected function requestInfo(string $method, string $url, array $headers = [], array $setOptList = [], $data = ''): ?array
    {
        $urlInfo = $this->getUrlInfo($url);
        if (empty($urlInfo['host'])) {
            return [
                'code' => -2,
                'msg' => 'host is empty',
                'data' => []
            ];
        }
        $requestObj = new Client($urlInfo['host'], $urlInfo['port'], $this->ssl);
        $queryUrl = $urlInfo['path'];
        if (!empty($urlInfo['query'])) {
            $queryUrl .= '?' . $urlInfo['query'];
        }
        $headerInfo = array_merge(['host' => $urlInfo['host']], $headers);
        $requestObj->setHeaders($headerInfo);
        $this->otherOptionInfo($requestObj);
        if (!empty($setOptList)) {
            $requestObj->set($setOptList);
        }
        if ($method === 'POST') {
            $requestObj->post($queryUrl, $data);
        } else {
            $requestObj->get($queryUrl);
        }
        if ($requestObj->errCode) {
            return [
                'code' => -1,
                'msg' => $requestObj->errCode,
                'data' => []
            ];
        }
        $res = $requestObj->getBody();
        if ($this->jsonDecode && !empty($res)) {
            $res = json_decode($res, true);
        }
        $result = [];
        if ($this->resHeader) {
            $result['header'] = $requestObj->getHeaders();
        }
        $result['response'] = $res;
        $result['httpCode'] = $requestObj->getStatusCode();
        if ($result['httpCode'] === false) {
            $result['httpCode'] = 0;
        }
        $requestObj->close();
        $requestObj = null;
        return [
            'code' => 0,
            'msg' => 'ok',
            'data' => $result
        ];
    }

    /**
     * @param Client $client
     * @return void
     */
    protected function otherOptionInfo(Client $client): void
    {
        if (empty($this->cookieList)) {
            $client->setCookies($this->cookieList);
        }
        $client->set(['timeout' => $this->timeout]);
        if ($this->keepAlive) {
            $client->set(['keep_alive' => $this->keepAlive]);
        }
    }


}