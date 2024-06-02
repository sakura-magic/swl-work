<?php
namespace app\task;
use Swoole\Coroutine;
use work\cor\Log;

class TestContext
{
    public function run(string $str)
    {
        (new Log())->info(json_encode([$str, Coroutine::getCid()]));
    }

}