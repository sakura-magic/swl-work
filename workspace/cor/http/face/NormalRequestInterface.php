<?php
namespace work\cor\http\face;

interface NormalRequestInterface
{
    /**
     * GET请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @return mixed
     */
    public function get(string $url,array $headers = [], array $setOptList = []);


    /**
     * POST请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @return mixed
     */
    public function post(string $url, $data = [], array $headers = [], array $setOptList = []);
}