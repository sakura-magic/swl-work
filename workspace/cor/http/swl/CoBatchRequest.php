<?php
declare(strict_types=1);

namespace work\cor\http\swl;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use work\cor\http\face\BatchRequestInterface;
use work\SwlBase;
use function Swoole\Coroutine\run;

class CoBatchRequest extends Base implements BatchRequestInterface
{
    private array $requestList = [];

    public function __construct(array $config = [])
    {
        $this->loadingConfig($config);
    }

    /**
     * get请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @return bool
     */
    public function addGetRequest(string $url, array $headers = [], array $setOptList = []): bool
    {
        $this->requestList[] = [
            'method' => 'GET',
            'url' => $url,
            'headers' => $headers,
            'setOptList' => $setOptList
        ];
        return true;
    }

    /**
     * post请求
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param array $setOptList
     * @return bool
     */
    public function addPostRequest(string $url, $data = [], array $headers = [], array $setOptList = []): bool
    {
        $this->requestList[] = [
            'method' => 'POST',
            'url' => $url,
            'headers' => $headers,
            'setOptList' => $setOptList,
            'data' => $data
        ];
        return true;
    }

    /**
     * 调用执行
     * @param float $uTime
     * @return array|null
     */
    public function execute(float $uTime = 0.8): ?array
    {
        if (empty($this->requestList)) {
            return null;
        }
        $uTime = (float)sprintf("%.3f", $uTime);
        $uTime = max($uTime, 0.001);
        if (SwlBase::inCoroutine()) {
            return $this->executeGo($uTime);
        } else {
            $result = null;
            run(function () use (&$result, $uTime) {
                $result = $this->executeGo($uTime);
                unset($result);
            });
            return $result;
        }
    }

    /**
     * 创建协程请求
     */
    private function executeGo(float $uTime): ?array
    {
        $closeChannel = false;
        $size = count($this->requestList);
        $channel = new Channel($size);
        $buildGoNumber = 0;
        for ($i = 0; $i < $size; $i++) {
            if (!isset($this->requestList[$i])) {
                continue;
            }
            Coroutine::create(function () use ($channel, $i, $uTime, &$closeChannel) {
                $res = [];
                try {
                    $info = $this->requestList[$i];
                    $res = $this->requestInfo($info['method'], $info['url'], $info['headers'], $info['setOptList'], $info['data'] ?? null);
                } catch (\Throwable | \Exception | \Error $e) {
                    $res = [
                        'code' => -98,
                        'msg' => $e->getMessage(),
                        'data' => []
                    ];
                } finally {
                    if (!$closeChannel) {
                        $channel->push(['index' => $i, 'res' => $res], $uTime);
                    }
                    unset($closeChannel);
                }
            });
            $buildGoNumber++;
        }
        $result = [];
        $maxWhileNumber = 100000000;
        do {
            $item = $channel->pop($uTime);
            if (is_array($item)) {
                $result[intval($item['index'])] = $item['res'];
                $buildGoNumber--;
            }
            if ($buildGoNumber <= 0) {
                break;
            }
        } while ($maxWhileNumber--);
        $closeChannel = true;
        foreach ($this->requestList as $key => $value) {
            if (!array_key_exists($key, $result)) {
                $result[intval($key)] = [
                    'code' => -99,
                    'msg' => 'channel pop is empty',
                    'data' => []
                ];
            }
        }
        ksort($result);
        $this->requestList = [];
        $channel = null;
        return [
            'code' => 0,
            'msg' => 'ok',
            'data' => $result
        ];
    }

}