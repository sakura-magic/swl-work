<?php
declare(strict_types=1);

namespace work;

use server\other\Console;
use work\traits\SingleObject;

class DocParserFactory
{
    use SingleObject;

    private static ?DocParserFactory $singleton = null;
    private array $rule = ['mapping', 'param', 'middleware', 'verifier','fusing'];
    private array $parseJson = ['mapping', 'middleware', 'verifier','fusing'];
    private array $params = array();


    /**
     * @throws \ReflectionException
     */
    public function getNote(string $path): ?array
    {
        if (!class_exists($path)) {
            return null;
        }
        $reflection = new \ReflectionClass($path);
        $docInfo = $reflection->getDocComment();
        $classData = $this->parse(is_string($docInfo) ? $docInfo : '');
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $result = [];
        foreach ($methods as $value) {
            try {
                $docStr = $value->getDocComment();
                if (!is_string($docStr)) {
                    continue ;
                }
                $result[$value->name] = $this->parse($docStr);
                if (isset($classData['mapping']['route']) && isset($result[$value->name]['mapping']['route'])) {
                    $result[$value->name]['mapping']['route'] = $classData['mapping']['route'] . $result[$value->name]['mapping']['route'];
                }
                if (!empty($classData['middleware'])) {
                    $middleware = (array)($result[$value->name]['middleware'] ?? []);
                    $middleware = array_merge($classData['middleware'], $middleware);
                    $result[$value->name]['middleware'] = array_values(array_unique($middleware));
                }
            } catch (\Exception $e) {
                if (isset($result[$value->name])) {
                    unset($result[$value->name]);
                }
                Console::dump([$path . '->' . $value->name . ' controller reload route error'], -1);
            }
        }
        $this->params = [];
        return $result;
    }


    /**
     * 切割注解
     * @param string $doc 注解
     * @return array
     */
    public function parse(string $doc = ''): array
    {
        // 清空单例缓存
        $this->params = [];

        if ($doc == '') return $this->params;

        // 使用正则匹配出/***/
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false) return $this->params;
        // 获取注解
        $comment = trim($comment[1]);

        // 将注解按*号切割
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) return $this->params;
        // 开始解析注解
        $this->parseLines($lines[1]);

        return $this->params;
    }

    /**
     * 注解按行解析
     * @param array $lines 注解
     */
    private function parseLines(array $lines)
    {

        foreach ($lines as $line) {
            $this->parseLine($line);
        }
    }

    /**
     * 注解解析行
     * @param string $line 每行的注解
     */
    private function parseLine(string $line): void
    {
        // 删除左右两侧空格
        $line = trim($line);
        if (empty($line) || strpos($line, '@') !== 0) {
            return;
        }
        $string = substr($line, 1);
        $msg = explode(' ', $string);
        if (count($msg) <= 1) {
            return;
        }
        $filed = trim($msg[0]);
        if (!in_array($filed, $this->rule)) {
            return;
        }
        if (in_array($filed, $this->parseJson)) {
            $data = json_decode(trim($msg[1]), true);
            $this->params[$filed] = !is_array($data) ? [] : $data;
            return;
        }
        if ($msg[0] == 'param') {
            unset($msg[0]);
            $data = array_values($msg);
            $package = [];
            foreach ($data as &$value) {
                $value = trim($value);
                //去掉$符号
                if (preg_match('/^\$(\d)?/', $value)) {
                    $package['argument'] = preg_replace('/^\$/', '', $value);
                }
                if (preg_match('/^mode=(GET|POST|PARAM|COOKIE|SISSEION|DELETE|PUT|WS)$/', $value)) {
                    $package['mode'] = trim(explode('=', $value)[1]);
                }
            }
            unset($value);
            if (isset($package['mode']) && isset($package['argument'])) {
                $this->params[$filed][$package['argument']] = $package['mode'];
            }
        }
    }


    public static function clear()
    {
        self::$singleton = null;
    }


}