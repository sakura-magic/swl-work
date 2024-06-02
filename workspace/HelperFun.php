<?php
declare(strict_types=1);

namespace work;


use work\container\Container;

final class HelperFun
{
    /**
     * xss过滤
     * @param string $str
     * @return string|null
     */
    public static function xssFilter(string $str): ?string
    {
        if ($str) {
            $ra = array('/([\x00-\x08\x0b-\x0c\x0e-\x19])/', "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU","/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU");
            //把一些预定义的 HTML 实体转换为字符
            $htmlString = htmlspecialchars_decode($str);
            //将空格替换成空
            $content = str_replace("?", "", $htmlString);
            //函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
            $content = preg_replace($ra, "", $content);
            //返回字符串中的前$num字符串长度的字符
            return htmlentities(strip_tags($content));
        } else {
            return $str;
        }
    }

    /**
     * 过滤文本信息
     * @param string $str
     * @return string|null
     */
    public static function filterWords(string $str): ?string
    {
        if (!MAGIC_QUOTES_GPC) {
            $str = addslashes($str); // magic_quotes_gpc没有打开的时候把数据过滤
        }
        $farr = array(
            "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
            "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
            "/select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
        );
        return preg_replace($farr, '', $str);
    }


    /**
     * 过滤array
     * @param array $arr
     */
    public static function filterSlashesArr(array $arr): array
    {
        foreach ($arr as &$val) {
            if (is_array($val)) {
                $val = self::filterSlashesArr($val);
            }
            if (is_string($val) && !MAGIC_QUOTES_GPC) {
                $val = addslashes($val); // magic_quotes_gpc没有打开的时候把数据过滤
            }
        }
        unset($val);
        return $arr;
    }

    /**
     * 生成随机串
     * @param int $number
     * @return string
     * @throws \Exception
     */
    public static function buildToken(int $number = 16): string
    {
        return bin2hex(random_bytes($number));
    }

    /**
     * 载入php配置文件
     */
    public static function scanFolder(string $path, ?string $fileName = null): array
    {
        $result = [];
        $suffix = substr($path, -1, 1) === DS ? '' : DS;
        if ($fileName !== null) {
            $result[$fileName] = self::includeFile($path . $suffix . $fileName . '.php');
            return $result;
        }
        $arr = scandir(ROOT_PATH . DS . $path);
        foreach ($arr as $val) {
            $fileInfo = pathinfo($val);
            if (isset($fileInfo['extension']) && $fileInfo['extension'] == 'php') {
                $result[$fileInfo['filename']] = self::includeFile($path . $suffix . $fileInfo['basename']);
            }
        }
        return $result;
    }

    /**
     * 引入文件
     * @param string $path
     * @return mixed|null
     */
    public static function includeFile(string $path)
    {
        $path = ROOT_PATH . DS . $path;
        if (file_exists($path)) {
            return include_once $path;
        }
        return null;
    }

    /**
     * 漏斗限流法
     * @param int $contain //容量
     * @param float $leakRate //速率
     * @param int $water //当前水量
     * @param int|null $preTime //上次时间
     * @param int $addNum //增加量
     * @return array
     */
    public static function funnelLimitFlow(int $contain, float $leakRate, int $water = 0, ?int $preTime = null, int $addNum = 1): array
    {
        if ($preTime === null) {
            $preTime = time();
        }
        $nowTime = time();
        $leakWater = ($nowTime - $preTime) * $leakRate;//这段时间内可流出的水
        $water = $water - $leakWater;//当前的水量 - leakWater
        $water = $water >= 0 ? $water : 0;//水量不可能为负数
        $preTime = $nowTime;
        $res = ['code' => -1, 'water' => $water, 'preTime' => $preTime, 'addNum' => 0];
        if (($water + $addNum) <= $contain) {
            $res['code'] = 0;
            $res['water'] += $addNum;
            $res['addNum'] = $addNum;
        }
        return $res;
    }

    /**
     * 错误打印信息
     * @param array $infoArr
     * @param string $divide
     * @return string
     */
    public static function debugErrorInfoStr(array $infoArr, string $divide = "\n"): string
    {
        $res = "[throw]{$divide}";
        foreach ($infoArr as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $res .= $key . " : " . $val;
            } else if (is_array($val)) {
                $res .= "[{$key}]";
                foreach ($val as $v) {
                    $res .= $divide;
                    if (is_string($v)) {
                        $res .= $v;
                    } else {
                        $res .= var_export($v, true);
                    }
                }
            } else {
                $res .= var_export($val, true);
            }
            $res .= $divide;
        }
        return $res;
    }

    /**
     * 获取记录的php错误
     */
    public static function getPhpErrorInfo(): array
    {
        $info = CoLifeVariable::getManageVariable()->get('systemPhpErrorCallbackInfoMessage', []);
        return is_array($info) ? $info : [];
    }




    /**
     * 随机字符
     * @param int $len
     * @param string|null $alphabet
     * @return string
     */
    public static function character(int $len = 6, ?string $alphabet = null): string
    {
        if (is_null($alphabet)) {
            $alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789';
        }
        $randomStr = '';
        for ($i = 0; $i < $len; $i++) {
            $index = mt_rand(0, strlen($alphabet) - 1);
            $randomStr .= substr($alphabet, $index, 1);
        }
        return $randomStr;
    }

    /**
     * 获取容器
     * @param int|null $cid
     */
    public static function getContainer(?int $cid = null): Container
    {
        $manage = CoLifeVariable::getManageVariable($cid);
        $info = $manage->has('containerInstance');
        if ($info) {
            $container = $manage->get('containerInstance');
            if ($container instanceof \work\container\Container) {
                return $container;
            }
        }
        $container = new \work\container\Container();
        $manage->set('containerInstance', $container, true);
        Hook::getInstance('app')->runHook('instantiationContainer', [$container, $cid, $manage]);
        return $container;
    }

    /**
     * 释放协程内资源
     */
    public static function flushCo()
    {
        HelperFun::getContainer()->flush();//释放容器挂在的类
        CoLifeVariable::flush();//释放变量
        GlobalVariable::cleanCorManage();
    }

    /**
     * 输出错误的信息
     */
    public static function outErrorInfo(\Throwable $throwable, ?string $divide = null): string
    {
        $phpErrorInfo = HelperFun::getPhpErrorInfo();
        $msg = $phpErrorInfo && isset($phpErrorInfo['joinMsg']) ? $phpErrorInfo['joinMsg'] : '';
        $throwError = [
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTrace(),
            'php_error' => [$msg]
        ];
        if ($divide === null) {
            return HelperFun::debugErrorInfoStr($throwError);
        }
        return HelperFun::debugErrorInfoStr($throwError, $divide);
    }

    /**
     * 递归删除文件夹，谨慎使用
     * @param string $folder
     * @return bool
     */
    public static function recursionDeleteFolder(string $folder): bool
    {
        clearstatcache(true, $folder);
        if (!is_dir($folder)) {
            return false;
        }
        $files = array_diff(scandir($folder), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $folder . '/' . $file;
            if (is_dir($filePath)) {
                self::recursionDeleteFolder($filePath);
            } else {
                unlink($filePath);
            }
        }
        return rmdir($folder);
    }


    /**
     * 基于id生成码
     * @param int $id
     * @return string
     */
    public static function inviteCode6(int $id, ?string $alphabet = null): ?string
    {
        $baseNum = 1 << 35;//扩大位数
        if ($id <= 0) { //id不允许小于等于0
            return null;
        }
        $changeId = $baseNum + $id;
        //10进制转2进制字符翻转
        $changeId = base_convert(strval($changeId), 10, 2);
        $changeId = substr(strrev($changeId), 0, -1); //反转并切掉之前开头的1
        //补位避免数字翻转塌陷。
        $changeId = '1' . $changeId;
        //2进制字符转10进制
        $id = base_convert($changeId, 2, 10);
        //字典字母顺序可打乱
        if (is_null($alphabet)) {
            $alphabet = 'JQgn3mzwFkN5Dlb9BqSZrHG7OxYMfW6I2j8t4sLihAUTaPvRd0EocXKyu1VepC';
        }
        $base = strlen($alphabet);
        $code = '';
        //自定义进制转换
        do {
            $inNum = bcmod(strval($id), strval($base));
            $code = substr($alphabet, intval($inNum), 1) . $code;
            $id = bcdiv($id, strval($base));
        } while ($id > 0);
        return $code;
    }




    /**
     * 设置随机种子
     */
    public static function setRandSeed()
    {
        $pid = getmypid();
        if ($pid === false) {
            $info = HashLib::DJBHash(self::getMillisecond());
        }else {
            $info = HashLib::DJBHash(self::getMillisecond(),$pid);
        }
        srand($info);
        if (function_exists('mt_srand')) {
            mt_srand($info);
        }
    }

    /**
     * 获取微秒
     * @return string
     */
    public static function getMillisecond(): string
    {
        list($s1, $s2) = explode(' ', microtime());
        return sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}