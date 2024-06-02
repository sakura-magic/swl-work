<?php
declare(strict_types=1);

namespace work\cor;

use work\cor\anomaly\HttpResponseDie;
use work\cor\basics\face\ResponseManageInterface;
use work\HelperFun;

/**
 * 请求响应
 * @method bool     setHeader(string $key, string|array $value)
 * @method bool     setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '')
 * @method bool     status(int $http_code, string $reason = '')
 * @method bool     redirect(string $location, int $http_code = 302)
 * @method bool     write(string $content)
 * @method bool     sendfile(string $filename, int $offset = 0, int $length = 0)
 * @method bool     end(?string $content = null)
 */
class Response
{
    private ?ResponseManageInterface $response = null;
    private array $dumpData = [];
    private bool $sendTo = false;//是否已发送过响应信息

    public function __construct(?ResponseManageInterface $response = null)
    {
        if (is_null($response)) {
            $response = HelperFun::getContainer()->make(ResponseManageInterface::class);
        }
        $this->response = $response;
    }


    /**
     * 转json
     * @return void
     */
    public function sendJson(array $data)
    {
        $this->response->setHeader("Content-Type", "text/json;charset=utf-8");
        $dump = $this->dumpMessage();
        $str = json_encode($data);
        if ($str === false) {
          $str = "数据编码失败";
        }
        if (!empty($dump)) {
            $str = $dump . $str;
        }
        $this->response->end($str);
        $this->sendTo = true;
    }

    /**
     * 将字符输出到屏幕
     * @return bool
     */
    public function dump($data): bool
    {
        if ($this->sendTo) {
            return false;
        }
        $this->dumpData[] = $data;
        return true;
    }

    /**
     * @return string
     */
    private function dumpMessage(): string
    {
        $result = '';
        foreach ($this->dumpData as $val) {
            $result .= var_export($val, true);
        }
        $this->dumpData = [];
        return $result;
    }

    /**
     * @param string $str
     * @return void
     */
    public function sendStr(string $str = '')
    {
        $dump = $this->dumpMessage();
        if (!empty($dump)) {
            $str = $dump . $str;
        }
        $this->response->end($str);
        $this->sendTo = true;
    }

    /**
     * 终止执行
     */
    public function dieRun(string $str = '')
    {
        $dump = $this->dumpMessage();
        if (!empty($dump)) {
            $str = $dump . $str;
        }
        $this->response->end($str);
        $this->sendTo = true;
        throw new HttpResponseDie('die run');
    }

    /**
     * @param string $filepath
     * @param string $fileName
     * @param int $speed
     * @return bool|false
     */
    public function sendDownloadFile(string $filepath, string $fileName = '', int $speed = -1, ?string $prefix = null): bool
    {
        if ($speed !== -1 && $speed < 1) {
            return false;
        }
        $baseFile = $prefix === null ? (ROOT_PATH . DS . 'public') : $prefix;
        $nginxPath = '';
        if (!preg_match("/^(\/|\\\\).*/", $filepath)) {
            $baseFile .= DS;
            $nginxPath .= '/';
        }
        $baseFile .= str_replace(['/', '\\', '//', '\\\\'], DS, $filepath);
        $nginxPath .= str_replace(['/', '\\', '//', '\\\\'], '/', $filepath);
        if (!is_file($baseFile)) {
            return false;
        }
        $limitSize = round($speed * 1024);
        if ($fileName === '') {
            $fileName = basename($baseFile);
        }
        $fileName = rawurlencode($fileName);
        $this->response->setHeader('Content-Type', 'application/octet-stream');
        $this->response->setHeader('Content-Disposition', ' attachment;filename=' . $fileName);
        $this->response->setHeader('X-Accel-Redirect', $nginxPath);
        $this->response->setHeader('X-Accel-Buffering', 'yes');
        $this->response->setHeader('X-Accel-Expires', '3600');
        if ($speed == -1) {
            $this->response->setHeader('X-Accel-Limit-Rate', strval($limitSize));
        }
        return true;
    }

    /**
     * 调用其他方法
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->response, $method], $args);
    }

    /**
     * 如果没发送过响应信息，并且有内容
     */
    public function __destruct()
    {
        if (!$this->sendTo && count($this->dumpData) > 0) {
            $this->sendStr();
        }
    }

}