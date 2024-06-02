<?php
declare(strict_types=1);

namespace server\other;
/**
 * 命令行打印调用
 */
final class Console
{
    /**
     * 打印
     * @return void
     */
    public static function dump(array $data, int $code)
    {
        $result = '';
        foreach ($data as $value) {
            $result .= (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        echo "code:{$code}\tmsg:", $result;
    }

    /**
     * 表格方式打印
     * @return string
     */
    public static function tableDump(array $header, array $body, bool $printFlag = false): string
    {
        $result = "";
        $findMax = [];
        foreach ($header as $k => $value) {
            $findMax[$k] = mb_strlen(strval($value));
        }
        foreach ($body as $value) {
            foreach ($header as $k => $val) {
                if (!empty($value[$k]) && mb_strlen(strval($value[$k])) > $findMax[$k]) {
                    $findMax[$k] = mb_strlen(strval($value[$k]));
                }
            }
        }
        $totalRowNumber = array_sum($findMax);
        $totalRowNumber += (count($header) * 3) + 1;
        $line = str_repeat("-", $totalRowNumber);
        $line .= "\n";
        $result .= $line;
        foreach ($header as $k => $value) {
            $str = str_pad(strval($value), $findMax[$k]);
            $result .= "| $str ";
        }
        $result .= "|\n";
        $result .= $line;
        foreach ($body as $value) {
            foreach ($header as $k => $val) {
                if (isset($value[$k])) {
                    $str = str_pad(strval($value[$k]), $findMax[$k]);
                    $result .= "| $str ";
                }
            }
            $result .= "|\n";
            $result .= $line;
        }
        if ($printFlag) {
            echo $result;
        }
        return $result;
    }

    /**
     * 打印到文件内
     * @return int|false
     */
    public static function dumpFile(string $file, $data)
    {
        clearstatcache();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0733, true);
        }
        return file_put_contents($file, is_array($data) ? var_export($data, true) : $data);
    }
}