<?php
declare(strict_types=1);

namespace server\other;

class ServerException extends \Exception
{

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取字符处理
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . "\n:[$this->code]:[$this->message]\n";
    }

    /**
     * @return void
     */
    public function customFunction()
    {
        echo "A Custom function for this type of exception\n";
    }


}