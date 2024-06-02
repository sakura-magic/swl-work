<?php
declare(strict_types=1);

namespace work\cor\http\swl;

use Swoole\Coroutine\Http\Client;
use work\cor\http\face\NormalRequestInterface;
use work\SwlBase;
use function Swoole\Coroutine\run;

class CoRequest extends Base implements NormalRequestInterface
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
     * @return array|null
     */
    public function get(string $url, array $headers = [], array $setOptList = []): ?array
    {
        if (SwlBase::inCoroutine()) {
            return $this->requestInfo('GET', $url, $headers, $setOptList);
        }
        $result = null;
        run(function ($url, $headers, $setOptList) use (&$result) {
            $result = $this->requestInfo('GET', $url, $headers, $setOptList);
            unset($result);
        }, $url, $headers, $setOptList);
        return $result;
    }

    /**
     * post请求
     * @param string $url
     * @param $data
     * @param array $headers
     * @param array $setOptList
     * @return ?array
     */
    public function post(string $url, $data = [], array $headers = [], array $setOptList = []): ?array
    {
        if (!(is_array($data) || is_string($data))) {
            $data = '';
        }
        if (SwlBase::inCoroutine()) {
            return $this->requestInfo('POST', $url, $headers, $setOptList, $data);
        }
        $result = null;
        run(function ($url, $data, $headers, $setOptList) use (&$result) {
            $result = $this->requestInfo('POST', $url, $headers, $setOptList, $data);
            unset($result);
        }, $url, $data, $headers, $setOptList);
        return $result;
    }

}