<?php
namespace app\socket;
use app\socket\controller\Com;
use server\other\ServerTool;
use server\Table;
use work\cor\Log;
use work\GlobalVariable;

class Timed
{
    /**
     * 检测连接超时
     * @throws \Exception
     */
    public function detectionLink()
    {
        $data = [];
        $list = Table::getTable('wsUserInfo')->tableEachFilter(function ($val) use(&$data) {
            $data[] = $val;
            $num = is_numeric($val['ptime']) ? intval($val['ptime']) : 0;
            if ($num + 300 < time()) {
                return true;
            }
            return false;
        });
        $workerId = GlobalVariable::getManageVariable('_sys_')->get('workerId');
        $com = new Com();
        foreach ($list as $k => $val) {
            if ($val['worker'] != $workerId) {
                continue;
            }
            $com->tapeOut($val['userId']);
            ServerTool::getServer()->getSever()->close($val['fd']);
            if (!empty($val['userId'])) {
                Table::getTable('userMapInfo')->del($val['userId']);
            }
            Table::getTable('wsUserInfo')->del('user_' . $val['fd']);
        }
        (new Log())->info("detectionLink run info >>" . var_export(['time' => time(),'list' => $list],true));
    }
}