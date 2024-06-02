<?php
declare(strict_types=1);

namespace work\cor;

use work\Config;
use work\cor\facade\Request;
use work\cor\facade\Response;
use work\cor\session\SessionControlInterface;
use work\HelperFun;

/**
 * session处理
 * Class Session
 * @package work\cor
 */
class Session
{

    public string $keyName = 'SESSION_ID';

    private ?SessionControlInterface $sessionObj;

    private string $namespaceInfo = '\\work\\cor\\session\\';

    public function __construct()
    {
        $optDrive = Config::getInstance()->get('session.saveOpt', 'File');
        $className = $this->namespaceInfo . $optDrive;
        if (!class_exists($className)) {
            throw new \Exception('session ' . $className . ' drive is not found');
        }
        $sessionId = Request::cookie($this->keyName);
        $class = HelperFun::getContainer()->make($className, [
            'id' => empty($sessionId) ? '' : $sessionId
        ]);
        $this->sessionObj = $class;
    }

    /**
     * @throws \Exception
     */
    public function set(string $key, $val)
    {
        $write = $this->sessionObj->write($key, $val);
        $this->setCookieInfo();
        return $write;
    }

    //清缓存

    /**
     * @throws \Exception
     */
    public function del(string $key)
    {
        return $this->sessionObj->del($key);
    }


    /**
     * @throws \Exception
     */
    public function get(string $key = '')
    {
        return $this->sessionObj->get($key);
    }

    /**
     * @return bool
     */
    public function cleanSession(): bool
    {
        return $this->sessionObj->cleanSession();
    }


    /**
     * 获取sessionKey值
     * @return string
     * @throws \Exception
     */
    private function getSessionId(): string
    {
        return $this->sessionObj->getSessionId();
    }

    /**
     * 设置cookie信息
     * @throws \Exception
     */
    private function setCookieInfo(): bool
    {
        if (empty($this->getSessionId())) {
            return false;
        }
        return Response::setCookie($this->keyName, $this->getSessionId(), 0, '/', '', false, true);
    }


}