<?php
declare(strict_types=1);

namespace work\cor;
/**
 * 变量管理
 * Class ManageVariable
 * @package work\cor
 */
class ManageVariable
{
    private array $data = [];

    private array $readOnlyField = [];
    //最大可设置层数
    private int $plie;

    public function __construct($plie = 2)
    {
        $this->plie = $plie;
    }

    /**
     * 设置信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function set(string $name, $value, bool $readOnly = false): bool
    {
        if (empty($name)) {
            return false;
        }
        $names = array_filter(explode('.', $name));
        $count = count($names);
        $firstStr = reset($names);
        if (in_array($firstStr, $this->readOnlyField, true)) {
            return false;
        }
        if ($readOnly) {
            $this->readOnlyField[] = $firstStr;
        }
        if ($count === 1) {
            $this->data[$names[0]] = $value;
            return true;
        }
        if ($count > $this->plie) {
            return false;
        }
        $this->data = self::recursionEach($this->data, array_reverse($names), $value, $count - 1, $this->plie);
        return true;
    }

    /**
     * 配置变量
     */
    private function recursionEach($customArr, $names, $val, int $index, int $num)
    {
        //退出递归调用，勿动
        if (!is_numeric($index) || $index < 0 || $num <= 0) {
            return $val;
        }
        $customArr[$names[$index]] = $this->recursionEach(($customArr[$names[$index]] ?? []), $names, $val, $index - 1, $num - 1);
        return $customArr;
    }

    /**
     * 获取配置参数
     */
    public function get(string $name = '', $default = null)
    {
        if (empty($name)) {
            return $this->data;
        }
        $names = explode('.', $name);
        $info = $this->data;
        foreach ($names as $val) {
            if (isset($info[$val])) {
                $info = $info[$val];
            } else {
                return $default;
            }
        }
        return $info;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        if (empty($name)) {
            return true;
        }
        $names = explode('.', $name);
        $info = $this->data;
        foreach ($names as $val) {
            if (!isset($info[$val])) {
                return false;
            }
            $info = $info[$val];
        }
        return true;
    }

}