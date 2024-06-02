<?php
declare(strict_types=1);

namespace work\cor\session;
class Bash
{
    /**
     * @var string
     */
    protected string $sessionId = '';

    /**
     * 生成session
     * @param null $id
     */
    public function buildSession($id = null): string
    {
        return is_string($id) && strlen($id) === 32 && ctype_alnum($id) ? $id : md5(microtime(true) . session_create_id());
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}