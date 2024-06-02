<?php
namespace work\cor\http\face;
interface BatchRequestInterface
{
    /**
     * 添加GET请求
     * @param string $url
     * @param array $headers
     * @param array $setOptList
     * @return bool
     */
    public function addGetRequest(string $url,array $headers = [],array $setOptList = []):bool;

    /**
     * 添加POST请求
     * @param string $url
     * @param array|string $data
     * @param array $headers
     * @param array $setOptList
     * @return bool
     */
    public function addPostRequest(string $url, $data = [], array $headers = [], array $setOptList = []):bool;


    /**
     * 请求接口
     * @param float $uTime
     * @return array|null
     */
    public function execute(float $uTime = 0.1):?array;
}