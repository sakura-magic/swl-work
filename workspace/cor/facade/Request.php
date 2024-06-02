<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method bool  isGet() static 设定当前的语言
 * @method bool  isPost() static 设定当前的语言
 * @method bool  isPut()  static 设定当前的语言
 * @method bool  isDelete()  static 设定当前的语言
 * @method bool  isHead()  static 设定当前的语言
 * @method bool  isPatch()  static 设定当前的语言
 * @method bool  isOptions()  static 设定当前的语言
 * @method bool  isCli()  static 设定当前的语言
 * @method bool  isAjax(bool $ajax = false)  static 设定当前的语言
 * @method array|mixed|string  method($method = false)  static 设定当前的语言
 * @method mixed  server($name = '', string $default = null, $filter = '')  static 设定当前的语言
 * @method mixed  input(array $data = [], $name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method void  typeCast(&$data, $type)  static 设定当前的语言
 * @method mixed  filter($filter = null)  static 设定当前的语言
 * @method mixed  getFilter($filter, $default)  static 设定当前的语言
 * @method void  filterValue(&$value, $key, array $filters)  static 设定当前的语言
 * @method void  filterExp(&$value)  static 设定当前的语言
 * @method mixed  cookie($name = '', string $default = null, $filter = '')  static 设定当前的语言
 * @method bool  isSsl()  static 设定当前的语言
 * @method bool  isMobile()  static 设定当前的语言
 * @method string|object  url(string $url = null)  static 设定当前的语言
 * @method string|object  baseUrl(string $url = null)  static 设定当前的语言
 * @method string  domain(string $domain = null)  static 设定当前的语言
 * @method string  scheme()  static 设定当前的语言
 * @method string  host(bool $strict = false)  static 设定当前的语言
 * @method string  pathinfo()  static 设定当前的语言
 * @method mixed  param($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method mixed  get($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method mixed  post($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method string  contentType()  static 设定当前的语言
 * @method mixed  put($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method string|array  header(string $name = '', string $default = null)  static 设定当前的语言
 * @method mixed  route($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method null|array|\work\cor\File  file($name = '')  static 设定当前的语言
 * @method array|null  getFileInfo($name = '')  static 设定当前的语言
 * @method array|null|string  postJson($name = '', $default = null, $filter = '')  static 设定当前的语言
 * @method string getRouteUri() static 设定当前的语言
 * @method null|string getClientIp() static 设定当前的语言
 */
class Request extends Facade
{

    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Request
    {
        return static::createFacade(null, $arg);
    }

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): ?string
    {
        return \work\cor\Request::class;
    }
}