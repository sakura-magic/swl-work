<?php
namespace app\task;
use work\cor\Log;

class TestTask {

    public function run($in = null)
    {
        try{
            $log = new Log();
            $log->info('task info write fpm');
            $log->infoWrite();
            sleep(10);
            $log->error('die sleep');
        }catch (\Exception $e) {
            file_put_contents('taskError','log error');
        }
        return ['code' => 0,'msg' => 'ok','sd' => 'test'];
    }
}