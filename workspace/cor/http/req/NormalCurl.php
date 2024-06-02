<?php
declare(strict_types=1);

namespace work\cor\http\req;

use work\cor\http\face\NormalRequestInterface;

class NormalCurl extends Base implements NormalRequestInterface
{

    public function __construct(array $config = [])
    {
        $this->loadingConfig($config);
    }

    /**
     * get请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @return bool|mixed|string
     */
    public function get(string $url, array $headers = [], array $setOptList = [])
    {
        $ch = $this->buildCurl($url, $headers, $setOptList);
        if ($ch === false) {
            return false;
        }
        $result = $this->resultInfo($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * post请求
     * @param string $url
     * @param array|string $data
     * @param array $headers
     * @param array $setOptList
     * @return bool|mixed|string
     */
    public function post(string $url, $data = [], array $headers = [], array $setOptList = [])
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
        $result = $this->resultInfo($ch);
        curl_close($ch);
        return $result;
    }


}