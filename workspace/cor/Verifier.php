<?php
declare(strict_types=1);

namespace work\cor;

use Exception;

/**
 * 参数校验
 * Class Verifier
 * @package work\cor
 */
class Verifier
{
    public array $ruleData = [];

    public string $errorMeg = '';

    public array $customError = [];

    private array $ruleError = [
        'require' => '不能为空',
        'phone' => '不符合手机号规则',
        'number' => '必须是数字',
        'integer' => '必须是整数',
        'eq' => "必须等于%u",
        'neq' => "不能等于%u",
        'gt' => "必须大于%u",
        'egt' => "必须大于等于%u",
        'lt' => "必须小于%u",
        'elt' => "必须小于等于%u",
        'max' => "字符长度不能大于%u",
        'min' => "字符长度不能小于%u",
        'tel' => "号码不正确",
        'between' => ':必须在 %u - %u',
        'notBetween' => ':不允许在 %u - %u',
        'noChinese' => '不能含有汉字'
    ];

    private array $outputMsg = [];


    //['id' => 'requre|']
    //载入规则
    public function rule(array $rule): self
    {
        foreach ($rule as $key => &$value) {
            if (stripos($key, '|')) {
                $keys = explode("|", $key);
                $this->customError[$keys[0]] = $keys[1];
                $key = $keys[0];
            }
            if (!is_array($value)) {
                $value = explode('|', $value);
            }
            $this->ruleData[$key] = $value;
        }
        unset($value);
        return $this;
    }

    //校验数据

    /**
     * @throws Exception
     */
    public function check(array $data): bool
    {
        foreach ($this->ruleData as $key => $value) {
            if (!isset($data[$key]) && in_array('require', $value)) {
                $this->writeError($key, 'require');
                return false;
            } else if (!isset($data[$key]) && !in_array('require', $value)) {
                continue;
            }
            if (!$this->ruleScript($key, $data[$key], $value)) {
                return false;
            }
        }
        $this->ruleData = [];
        $this->customError = [];
        return true;
    }

    //一条条检查

    /**
     * @throws Exception
     */
    private function ruleScript($field, $data, $rules): bool
    {
        foreach ($rules as $key => $value) {
            $getKey = is_numeric($key) ? $value : $key;
            if (stripos($getKey, ':') && $value == $getKey) {
                $infoArr = explode(':', $getKey);
                $getKey = $infoArr[0];
                $value = $infoArr[1];
            }
            if (!$this->isValid($getKey, $data, $value)) {
                $this->writeError($field, $getKey);
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    private function isValid($key, $data, $ruleVal)
    {
        $result = false;
        switch ($key) {
            case 'require' :
                $result = !empty($data);
                break;
            case 'number' :
                $result = is_numeric($data);
                break;
            case 'integer' :
                $result = is_numeric($data) && floor($data) == $data;
                break;
            case 'eq' :
                $result = $data == $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'neq' :
                $result = $data != $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'gt' :
                $result = $data > $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'egt' :
                $result = $data >= $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'lt' :
                $result = $data < $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'elt' :
                $result = $data <= $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'max' :
                $result = mb_strlen($data) <= $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'min' :
                $result = mb_strlen($data) >= $ruleVal;
                $this->outputMsg = [$ruleVal];
                break;
            case 'tel' :
                $result = preg_match('/^1[345789]\d{9}$/ims', $data);
                break;
            case 'noChinese':
                $result = !preg_match("/[\x7f-\xff]/", $data);
                break;
            default :
                if (!method_exists($this, $key)) {
                    throw new Exception("Class Verifier {$key} Method does not exist");
                }
                $result = call_user_func_array([$this, $key], [$data, $ruleVal]);
        }
        return $result;
    }

    //错误信息
    private function writeError($field, $rule, array $msg = [])
    {
        count($msg) || $msg = $this->outputMsg;
        $this->errorMeg = isset($this->customError[$field]) ? $this->customError[$field] . vsprintf($this->ruleError[$rule], $msg) : $field . vsprintf($this->ruleError[$rule], $msg);
        $this->outputMsg = [];
    }

    public function getLastError(): string
    {
        return $this->errorMeg;
    }


    /**
     * between验证数据
     * @access public
     * @param mixed $value 字段值
     * @param mixed $rule 验证规则
     * @return bool
     */
    public function between($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        $this->outputMsg = [$rule[0], $rule[1]];

        return $value >= $min && $value <= $max;
    }

    /**
     * 使用notbetween验证数据
     * @access public
     * @param mixed $value 字段值
     * @param mixed $rule 验证规则
     * @return bool
     */
    public function notBetween($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        $this->outputMsg = [$rule[0], $rule[1]];

        return $value < $min || $value > $max;
    }

}