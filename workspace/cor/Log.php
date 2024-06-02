<?php
declare(strict_types=1);

namespace work\cor;

use Swoole\Coroutine\System;
use work\GlobalVariable;
use work\SwlBase;

/**
 * 日志记录
 * Class Log
 * @package work\cor
 */
class Log
{

    protected string $path = ROOT_PATH . DS . 'logs' . DS . 'log';

    private string $suffix = 'txt';

    private bool $debugFolder = false;

    private bool $infoFolder = false;
    //阈值防止内存溢出
    private int $infoThreshold = 12;

    private array $infoData = [];

    private ?int $dataStr;

    private ?int $month;

    public function __construct()
    {
        $this->dataStr = intval(date('Ymd'));
        $this->month = intval(date('Ym'));
    }

    /**
     * 实写性能差,重要信息请采用error级别
     * @param $data
     * @return false|int
     */
    public function error($data)
    {
        $name = 'error';
        if (!$this->debugFolder && !$this->debugFolder = $this->folderBuild($name)) {
            return false;
        }
        return $this->writeFile($name, $this->argumentsHandle($name, $data) . "\n");
    }

    /**
     * 系统级致命错误
     * @param $data
     * @return false|int
     */
    public function systemError($data)
    {
        $name = 'system_error';
        if (!$this->debugFolder && !$this->debugFolder = $this->folderBuild($name)) {
            return false;
        }
        return $this->writeFile($name, $this->argumentsHandle($name, $data) . "\n");
    }

    /**
     * 暂存写,可能有内存溢出问题，需注意
     * @return bool
     */
    public function info($data)
    {
        $name = 'info';
        if (!$this->infoFolder && !$this->infoFolder = $this->folderBuild($name)) {
            return false;
        }
        $this->infoData[] = $this->argumentsHandle($name, $data);
        if (count($this->infoData) >= $this->infoThreshold) {
            return $this->infoWrite();
        }
        return true;
    }

    /**
     * info写数据
     * @return false|int
     */
    public function infoWrite()
    {
        if (count($this->infoData) < 1) {
            return false;
        }
        $result = $this->writeFile('info', implode("\n", $this->infoData) . "\n");
        $this->infoData = [];
        return $result;
    }

    /**
     * 写文件信息
     * @param string $method
     * @param string $fileContent
     * @return false|int
     */
    private function writeFile(string $method, string $fileContent)
    {
        $workerId = GlobalVariable::getManageVariable('_sys_')->get('workerId', 0);
        $fileName = $this->path . DS . $method . DS . $workerId . DS . $this->month . DS . $this->dataStr . '.' . $this->suffix;
        $dir = dirname($fileName);
        if (!is_dir($dir)) {
            mkdir($dir, 0744, true);
        }
        if (SwlBase::inCoroutine()) {
            return System::writeFile($fileName, $fileContent, FILE_APPEND);
        }
        return file_put_contents($fileName, $fileContent, FILE_APPEND);
    }

    /**
     * 参数处理
     * @param string $methode
     * @param $data
     * @return string
     */
    private function argumentsHandle(string $methode, $data): string
    {
        $str = '[' . date('Y-m-d H:i:s') . '] ';
        $str .= $methode . ' --> ';
        $str .= is_array($data) ? json_encode($str) : (is_string($data) ? $data : serialize($data));
        return $str;
    }

    /**
     * 构建文件夹
     * @return bool
     */
    private function folderBuild(string $nextName): bool
    {
        $pathInfo = $this->path . DS . $nextName;
        if (!is_dir($pathInfo)) {
            return mkdir($pathInfo, 0744, true);
        }
        return true;
    }

    /**
     * 消除前处理info数据
     */
    public function __destruct()
    {
        $this->infoWrite();
    }
}