<?php
declare(strict_types=1);

namespace work\cor\http\req;

use Swoole\Coroutine\System;
use work\cor\http\face\BatchRequestInterface;
use work\SwlBase;

class BatchCurl extends Base implements BatchRequestInterface
{

    /**
     * @var \CurlMultiHandle
     */
    private $multiHandle = null;
    private array $handles = [];

    public function __construct(array $config = [])
    {
        $this->loadingConfig($config);
        $this->multiHandle = curl_multi_init();
    }

    /**
     * 添加get请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     */
    public function addGetRequest(string $url, array $headers = [], array $setOptList = []): bool
    {
        $ch = $this->buildCurl($url, $headers, $setOptList);
        if ($ch === false) {
            return false;
        }
        $this->handles[] = $ch;
        $add = curl_multi_add_handle($this->multiHandle, $ch);
        if ($add < 0) {
            return false;
        }
        return true;
    }

    /**
     * 添加post请求
     * @param string $url
     * @param array|string $data
     * @param array $headers
     * @param \Closure|null $fun
     */
    public function addPostRequest(string $url, $data = [], array $headers = [], array $setOptList = []): bool
    {
        if (!(is_array($data) || is_string($data))) {
            $data = '';
        }
        $setOptList[CURLOPT_POST] = true;
        $setOptList[CURLOPT_POSTFIELDS] = $data;
        $ch = $this->buildCurl($url, $headers, $setOptList);
        if ($ch === false) {
            return false;
        }
        $this->handles[] = $ch;
        $add = curl_multi_add_handle($this->multiHandle, $ch);
        if ($add < 0) {
            return false;
        }
        return true;
    }


    /**
     * 执行请求
     */
    public function execute(float $uTime = 0.1): ?array
    {
        $uTime = (float)sprintf("%.3f", $uTime);
        $uTime = max($uTime, 0.001);
        if (is_null($this->multiHandle) || empty($this->handles)) {
            return null;
        }
        $response = [];
        $active = null;
        do {
            $mrc = curl_multi_exec($this->multiHandle, $active);
            if ($active) {
                curl_multi_select($this->multiHandle,$uTime);
            }
        } while ($active > 0);
        $errno = curl_multi_errno($this->multiHandle);
        $result = [
            'code' => $errno === false ? -1 : $errno,
            'msg' => $mrc == CURLM_OK ? 'ok' : curl_multi_strerror($errno),
            'data' => []
        ];
        foreach ($this->handles as $key => $ch) {
            $response[] = $this->resultInfo($ch, (int)$key);
            curl_multi_remove_handle($this->multiHandle, $ch);
            curl_close($ch);
        }
        $this->handles = [];
        curl_multi_close($this->multiHandle);
        $this->multiHandle = null;
        $this->multiHandle = curl_multi_init();
        $result['data'] = $response;
        return $result;
    }

    /**
     * 类销毁
     */
    public function __destruct()
    {
        foreach ($this->handles as $ch) {
            curl_multi_remove_handle($this->multiHandle, $ch);
            curl_close($ch);
        }
        curl_multi_close($this->multiHandle);
    }
}