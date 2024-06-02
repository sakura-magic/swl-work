<?php
declare(strict_types=1);
namespace work\cor\steam;
class SteamSelect
{
    /**
     * @var array
     */
    private array $steamInfo = [];

    private int $lastSteamNum = 0;

    /**
     * 加入steam流
     * @param SteamInterface $steam
     * @param string|null $key
     * @return bool
     */
    public function addSteam(SteamInterface $steam,int $opt = 1, ?string $key = null): bool
    {

        foreach ($this->steamInfo as $value) {
            if ($value['instance'] === $steam) {
                return false;
            }
            if (!is_null($key) && $value['name'] === $key) {
                return false;
            }
        }
        $steam->async(true);
        $this->steamInfo[] = [
          'instance' => $steam,
           'steam' => $steam->getSteam(),
          'name' => is_null($key) ? $this->lastSteamNum++ : $key,
           'opt' => $opt
        ];
        return true;
    }

    /**
     * 开始监听
     */
    public function steamSelect(?float $timeout = 0.5): array
    {
        $read = [];
        $write = [];
        $except = NULL;
        foreach ($this->steamInfo as $key => $value) {
            if ($value['opt'] === 1) {
                $read[] = $value['steam'];
            } else {
                $write[] = $value['steam'];
            }
        }
        $second = !is_null($timeout) && intval($timeout) == $timeout ? intval($timeout) : (is_null($timeout) ? null : 0);
        $microseconds = !is_null($timeout) && intval($timeout) == $timeout ? 0 : (is_null($timeout) ? 0 : (round($timeout * 1000)));
        $info = stream_select($read,$write,$except,$second,$microseconds);
        if ($info === false) {
            return ['code' => -1,'status' => $info];
        }
        $readyReadList = [];
        $reedyWriteList = [];
        foreach ($read as $value) {
            foreach ($this->steamInfo as $val) {
                if ($value === $val['steam']) {
                    $readyReadList[] = [
                        'instance' => $val['instance'],
                        'name' => $val['name'],
                        'opt' => $val['opt'],
                        'ready' => 'read'
                    ];
                    break;
                }
            }
        }
        foreach ($write as $value) {
            foreach ($this->steamInfo as $val) {
                if ($value === $val['steam']) {
                    $readyReadList[] = [
                        'instance' => $val['instance'],
                        'name' => $val['name'],
                        'opt' => $val['opt'],
                        'ready' => 'write'
                    ];
                    break;
                }
            }
        }
        return ['code' => 0 ,'status' => $info,'readList' => $readyReadList,'writeList' => $reedyWriteList];
    }

    /**
     * 清除所有
     */
    public function cleanAll()
    {
        $this->steamInfo = [];
    }

    /**
     * 清除
     * @param string|null $key
     */
    public function clean(string $key = null)
    {
        foreach ($this->steamInfo as $key => $value) {
            if ($value['name'] == $key) {
                unset($this->steamInfo[$key]);
                break;
            }
        }
        $this->steamInfo = array_values($this->steamInfo);
    }
}