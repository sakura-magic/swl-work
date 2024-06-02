<?php
declare(strict_types=1);

namespace server\other;

use server\ServerBase;

final class ServerTool
{

    private static ?ServerBase $base = null;

    /**
     * 扫描文件夹内文件名
     * @param string $path
     * @return array|null
     */
    public static function scanFolder(string $path, array $suffix = ['php']): ?array
    {
        if (!is_dir($path)) {
            return null;
        }
        $result = [];
        $arr = scandir($path);
        foreach ($arr as $val) {
            $fileInfo = pathinfo($val);
            $fileInfo['extension'] = strtolower($fileInfo['extension']);
            if (in_array($fileInfo['extension'], $suffix)) {
                $result[$fileInfo['extension']][] = [
                    'filename' => $fileInfo['filename'],
                    'basename' => $fileInfo['basename'],
                    'extension' => $fileInfo['extension']
                ];
            }
        }
        return $result;
    }

    /**
     * 读取文件信息
     */
    public static function readFileInfo(string $path, $filter = true): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        if (empty($content) || !$filter) {
            return $content;
        }
        $content = htmlspecialchars_decode($content);
        return strip_tags($content);
    }

    /**
     * 创建文件夹
     * @param string $fileName
     * @return bool
     */
    public static function createDir(string $fileName, bool $clean = true): bool
    {
        $clean && clearstatcache();
        $dir = dirname($fileName);
        if (!is_dir($dir)) {
            return mkdir($dir, 0744, true);
        }
        return true;
    }

    /**
     * 设置server类
     * @return bool
     */
    public static function setServer(ServerBase $base): bool
    {
        if (self::$base !== null) {
            return false;
        }
        self::$base = $base;
        return true;
    }

    /**
     * 获取server
     * @return ServerBase|null
     * @throws \Exception
     */
    public static function getServer(): ?ServerBase
    {
        if (!self::$base instanceof ServerBase) {
            throw new \Exception('serverBase is empty');
        }
        return self::$base;
    }

    /**
     * 载入php配置文件
     */
    public static function loadIncFile(string $path, ?string $fileName = null): array
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
}