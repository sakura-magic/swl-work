<?php
declare(strict_types=1);

namespace work\cor\session;

use work\Config;
use work\cor\FileGc;
use work\cor\FileSystem;
use work\SwlBase;

class File extends Bash implements SessionControlInterface
{
    /**
     * @var string
     */
    private string $prefix = '';

    private FileSystem $file;

    /**
     * 生命周期
     * @var int
     */
    private int $life = 86400;

    /**
     * 实例化
     * File constructor.
     * @param string $id
     * @param string $prefix
     */
    public function __construct(string $id)
    {
        $life = Config::getInstance()->get('session.sessionLife');
        if (is_numeric($life) && $life > 3600) {
            $this->life = $life;
        }
        $this->sessionId = $id;
        $prefix = Config::getInstance()->get('session.prefix', '');
        if (is_string($prefix)) {
            $this->prefix = $prefix;
        }
        $this->file = new FileSystem();
    }

    /**
     * @param string $id
     */
    public function setSessionId(string $id): void
    {
        $this->sessionId = $id;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function write(string $key, $val): bool
    {
        $data = $this->get();
        $data[$key] = $val;
        $data['expire'] = time() + $this->life;
        return $this->writeFile($this->getPatch(), serialize($data));
    }

    /**
     * @param string $key
     * @return array|mixed|null
     * @throws \Exception
     */
    public function get(string $key = '')
    {
        $data = $this->readFile($this->getPatch());
        $data = empty($data) ? [] : unserialize($data);
        if (!is_array($data)) {
            $data = [];
        }
        if (isset($data['expire']) && $data['expire'] < time()) {
            $data = [];
        }
        if (empty($key)) {
            return $data;
        }
        return $data[$key] ?? null;
    }


    /**
     * @return string
     */
    private function getPatch(): string
    {
        $sessionId = $this->sessionId;
        $confPatch = (string)Config::getInstance()->get('session.sessionDir', ROOT_PATH . DS . 'logs' . DS . 'session');
        $fileName = $confPatch . DS . $this->prefix;
        if (!empty($sessionId)) {
            return $fileName . $sessionId;
        }
        if (!is_dir($confPatch)) {
            mkdir($confPatch, 0744, true);
        }
        $number = 6;
        do {
            $sessionId = $this->buildSession();
            if (!file_exists($fileName . $sessionId) && $this->writeFile($fileName . $sessionId, serialize([]))) {
                $this->setSessionId($sessionId);
                break;
            }
        } while ($number--);
        if (empty($this->sessionId)) {
            throw new \Exception('session build id error');
        }
        return $fileName . $sessionId;
    }

    /**
     * 写数据到文件中
     * @param string $path
     * @param string $content
     * @return bool
     */
    protected function writeFile(string $path, string $content): bool
    {
        return (bool) $this->file->write($path, $content, true);
    }


    /**
     * 删除key
     * @param string $key
     * @return bool
     */
    public function del(string $key): bool
    {
        $data = $this->get();
        unset($data[$key]);
        return $this->writeFile($this->getPatch(), serialize($data));
    }


    /**
     * 读取文件内容(加锁)
     * @param string $path
     * @return string
     */
    protected function readFile(string $path): string
    {
        $contents = '';
        if (!file_exists($path)) {
            return $contents;
        }
        return $this->file->read($path, true);
    }

    /**
     * 清session
     * @return bool
     */
    public function cleanSession(): bool
    {
        return $this->unlink($this->getPatch());
    }

    /**
     * 删除session文件
     * @param string $file
     * @return bool
     */
    private function unlink(string $file): bool
    {
        return file_exists($file) && unlink($file);
    }


}