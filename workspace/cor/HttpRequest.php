<?php
declare(strict_types=1);

namespace work\cor;

use work\cor\http\face\NormalRequestInterface;
use work\HelperFun;

/**
 * http 请求
 * @method mixed get(string $url, array $headers = [], array $setOptList = [])  设定当前的语言
 * @method mixed post(string $url, $data = [], array $headers = [], array $setOptList = [])  设定当前的语言
 */
class HttpRequest
{
    private ?NormalRequestInterface $request = null;

    public function __construct(array $options = [], ?NormalRequestInterface $request = null)
    {
        if (is_null($request)) {
            $request = HelperFun::getContainer()->make(NormalRequestInterface::class, [
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