<?php
declare(strict_types=1);
namespace work\cor;

use Generator;

/**
 * 对超时文件进行清除
 * Class FileGc
 * @package work\cor
 */
class FileGc
{
    /**
     * @var int
     */
    private int $life;


    private string $path;

    private int $baseTime;

    public function __construct(string $path,int $life = 86400,int $baseTime = -1)
    {
        $this->path = $path;
        $this->life = $life;
        if ($baseTime < 0) {
            $baseTime = time();
        }
        $this->baseTime = $baseTime;
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


    /**
     * 查找文件
     * @param string $root
     * @param \Closure $filter
     * @return Generator
     */
    protected function findFiles(string $root, \Closure $filter): Generator
    {
        $items = new \FilesystemIterator($root);
        /** @var \SplFileInfo $item */
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                yield from $this->findFiles($item->getPathname(), $filter);
            } else {
                if ($filter($item)) {
                    yield $item;
                }
            }
        }
    }

    /**
     * gc销毁过期session
     */
    public function gc(): int
    {
        $num = 0;
        if (!is_dir($this->path)) {
            return -1;
        }
        $files = $this->findFiles($this->path, function (\SplFileInfo $item)  {
            return $this->baseTime - $this->life > $item->getMTime();
        });
        foreach ($files as $file) {
            $flag = $this->unlink($file->getPathname());
            if ($flag) {
                $num++;
            }
        }
        return $num;
    }
}