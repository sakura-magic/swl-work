<?php
declare(strict_types=1);
namespace work\cor\steam;
interface SteamInterface
{
    /**
     * 开异步选项
     * @param bool $async
     * @return bool
     */
    public function async(bool $async = true): bool;

    /**
     * 获取steam实例
     * @return mixed
     */
    public function getSteam();
}