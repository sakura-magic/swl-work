<?php
namespace work;
/**
 * hash库
 * Class HashLib
 * @package work
 */
final class HashLib
{
    /**
     * 由Justin Sobel编写的按位散列函数
     */
    public static function JSHash($string, $len = null): int
    {
        $hash = 1315423911;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash ^= (($hash << 5) + ord($string[$i]) + ($hash >> 2));
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 该哈希算法基于AT＆T贝尔实验室的Peter J. Weinberger的工作。
     * Aho Sethi和Ulman编写的“编译器（原理，技术和工具）”一书建议使用采用此特定算法中的散列方法的散列函数。
     */
    public static function PJWHash($string, $len = null): int
    {
        $bitsInUnsignedInt = 4 * 8; //（unsigned int）（sizeof（unsigned int）* 8）;
        $threeQuarters = ($bitsInUnsignedInt * 3) / 4;
        $oneEighth = $bitsInUnsignedInt / 8;
        $highBits = 0xFFFFFFFF << (int)($bitsInUnsignedInt - $oneEighth);
        $hash = 0;
        $test = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << (int)($oneEighth)) + ord($string[$i]);
        }
        $test = $hash & $highBits;
        if ($test != 0) {
            $hash = (($hash ^ ($test >> (int)($threeQuarters))) & (~$highBits));
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 类似于PJW Hash功能，但针对32位处理器进行了调整。它是基于UNIX的系统上的widley使用哈希函数。
     */
    public static function ELFHash($string, $len = null): int
    {
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << 4) + ord($string[$i]);
            $x = $hash & 0xF0000000;
            if ($x != 0) {
                $hash ^= ($x >> 24);
            }
            $hash &= ~$x;
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 这个哈希函数来自Brian Kernighan和Dennis Ritchie的书“The C Programming Language”。
     * 它是一个简单的哈希函数，使用一组奇怪的可能种子，它们都构成了31 .... 31 ... 31等模式，它似乎与DJB哈希函数非常相似。
     */
    public static function BKDRHash($string, $len = null): int
    {
        $seed = 131;  # 31 131 1313 13131 131313 etc..
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(($hash * $seed) + ord($string[$i]));
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 这是在开源SDBM项目中使用的首选算法。
     * 哈希函数似乎对许多不同的数据集具有良好的总体分布。它似乎适用于数据集中元素的MSB存在高差异的情况。
     */
    public static function SDBMHash($string, $len = null): int
    {
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(ord($string[$i]) + ($hash << 6) + ($hash << 16) - $hash);
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 由Daniel J. Bernstein教授制作的算法，首先在usenet新闻组comp.lang.c上向世界展示。
     * 它是有史以来发布的最有效的哈希函数之一
     */
    public static function DJBHash($string, $len = null): int
    {
        $hash = 5381;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(($hash << 5) + $hash) + ord(substr($string,$i,1));
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * Donald E. Knuth在“计算机编程艺术第3卷”中提出的算法，主题是排序和搜索第6.4章。
     */
    public static function DEKHash($string, $len = null): int
    {
        $len || $len = strlen($string);
        $hash = $len;
        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash << 5) ^ ($hash >> 27)) ^ ord($string[$i]);
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 参考 http://www.isthe.com/chongo/tech/comp/fnv/
     */
    public static function FNVHash($string, $len = null): int
    {
        $prime = 16777619; //32位的prime 2^24 + 2^8 + 0x93 = 16777619
        $hash = 2166136261; //32位的offset
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)($hash * $prime) % 0xFFFFFFFF;
            $hash ^= ord($string[$i]);
        }
        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }
}