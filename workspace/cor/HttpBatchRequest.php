<?php
declare(strict_types=1);

namespace work\cor;

use work\cor\http\face\BatchRequestInterface;
use work\HelperFun;

/**
 * 批量http请求
 * @method bool addGetRequest(string $url, array $headers = [], array $setOptList = [])  设定当前的语言
 * @method bool addPostRequest(string $url, $data = [], array $headers = [], array $setOptList = [])  设定当前的语言
 * @method array|null execute(float $uTime = 0.1) 设定当前的语言
 */
class HttpBatchRequest
{
    private ?BatchRequestInterface $request = null;

    public function __construct(array $options = [], ?BatchRequestInterface $request = null)
    {
        if (is_null($request)) {
            $request = HelperFun::getContainer()->make(BatchRequestInterface::class, [
                'config' => $options
            ]);
        }
        $this->request = $request;
    }

    /**
     * @param $method
     * @param $args
     * @return false|mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->request, $method], $args);
    }
}