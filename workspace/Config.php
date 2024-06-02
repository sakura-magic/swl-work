<?php
declare(strict_types=1);

namespace work;
class Config
{

    private array $configData = [];

    private static ?Config $configObj = null;

    const COVER_ARG = false;

    private ?string $str = null;

    private bool $init = false;

    private function __construct(?string $str = null)
    {
        $this->str = $str;
    }

    /**
     * 读取配置信息
     * @return void
     */
    private function readConfigMessage(?string $str = null): void
    {
        $result = HelperFun::scanFolder('config', $str);
        foreach ($result as $key => $value) {
            if (!empty($value) && is_array($value) && (!isset($this->configData[$key]) || !self::COVER_ARG)) {
                $this->configData[$key] = $value;
            }
        }
    }

    /**
     * 调用初始化
     */
    public function init()
    {
        if (!$this->init) {
            $this->init = true;
            $this->readConfigMessage($this->str);
        }
    }


    public function get(string $key = '', $default = null)
    {
        if (empty($key)) {
            return $this->configData;
        }
        $names = explode('.', $key);
        $config = $this->configData;
        foreach ($names as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }
        return $config;
    }


    /**
     * 初始化
     * @return Config
     */
    public static function getInstance(?string $str = null): Config
    {
        if (!self::$configObj instanceof self) {
            self::$configObj = new self($str);
        }
        return self::$configObj;
    }

}