<?php
declare(strict_types=1);
namespace work\fusing;
interface FusingFace
{
    /**
     * 当前是否可调用
     * @return bool
     */
    public function allowRequest(): bool;

    /**
     * 成功调用
     */
    public function recordSuccess() : void;


    /**
     * 失败调用
     */
    public function recordFailure() : void;

    /**
     * 获取key
     * @return string
     */
    public function getKey(): string;
}