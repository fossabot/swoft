<?php

namespace Swoft\Web;

use Swoft\Base\RequestContext;
use Swoft\Bean\Collector;
use Swoft\Contract\Arrayable;
use Swoft\Helper\JsonHelper;
use Swoft\Helper\StringHelper;

/**
 * 响应response
 *
 * @uses      Response
 * @version   2017年05月11日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 Swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class Response extends \Swoft\Base\Response
{

    use ViewRendererTrait;

    /**
     * @var \Throwable|null
     */
    protected $exception;

    /**
     * 重定向
     *
     * @param string   $url
     * @param null|int $status
     * @return static
     */
    public function redirect($url, $status = null)
    {
        $this->swooleResponse->header('Location', (string)$url);

        if (null === $status) {
            $status = 302;
        }

        if (null !== $status) {
            $this->swooleResponse->status((int)$status);
        }

        return $this;
    }

    /**
     * return a View format response
     *
     * @param array $data
     * @return \Swoft\Web\Response
     */
    public function view($data = []): Response
    {
        $controllerClass = RequestContext::getContextData('controllerClass');
        $template = Collector::$requestMapping[$controllerClass]['view']['template'] ?? null;
        $layout = Collector::$requestMapping[$controllerClass]['view']['layout'] ?? null;
        $response = $this->render($template, $data, $layout);
        return $response;
    }

    /**
     * return a Raw format response
     *
     * @param  mixed $data   The data
     * @param  int   $status The HTTP status code.
     * @return \Swoft\Web\Response when $data not jsonable
     */
    public function raw($data = [], int $status = 200): Response
    {
        $response = $this;

        // Headers
        $response = $response->withoutHeader('Content-Type')->withAddedHeader('Content-Type', 'text/plain');
        $this->getCharset() && $response = $response->withCharset($this->getCharset());

        // Content
        $data && $response = $response->withContent($data);

        // Status code
        $status && $response = $response->withStatus($status);

        return $response;
    }

    /**
     * return a Json format response
     *
     * @param  mixed $data            The data
     * @param  int   $status          The HTTP status code.
     * @param  int   $encodingOptions Json encoding options
     * @return static when $data not jsonable
     */
    public function json($data = [], int $status = 200, int $encodingOptions = 0): Response
    {
        $response = $this;

        // Headers
        $response = $response->withoutHeader('Content-Type')->withAddedHeader('Content-Type', 'application/json');
        $this->getCharset() && $response = $response->withCharset($this->getCharset());

        // Content
        if ($data && (is_array($data) || is_string($data) || $data instanceof Arrayable)) {
            is_string($data) && $data = ['data' => $data];
            $content = JsonHelper::encode($data, $encodingOptions);
            $response = $response->withContent($content);
        } else {
            $response = $response->withContent('{}');
        }

        // Status code
        $status && $response = $response->withStatus($status);

        return $response;
    }

    /**
     * return an automatic detection format response
     *
     * @param mixed $data
     * @param int   $status
     * @return static
     */
    public function auto($data = null, int $status = 200): Response
    {
        $accepts = RequestContext::getRequest()->getHeader('accept');
        $currentAccept = current($accepts);
        $controllerClass = RequestContext::getContextDataByKey('controllerClass');
        $template = Collector::$requestMapping[$controllerClass]['view']['template'] ?? null;
        $matchViewModel = StringHelper::contains($currentAccept, 'text/html') === true && $controllerClass && is_array($data) && $template && ! $this->getException();
        switch ($currentAccept) {
            // View
            case $matchViewModel === true:
                $response = $this->view($data, $status);
                break;
            // Json
            case StringHelper::contains($currentAccept, 'application/json') === true:
            case is_array($data) || $data instanceof Arrayable:
                is_string($data) && $data = compact('data');
                $response = $this->json($data, $status);
                break;
            // Raw
            default:
                $response = $this->raw((string)$data, $status);
                break;
        }
        return $response;
    }

    /**
     * 处理 Response 并发送数据
     */
    public function send(): void
    {
        $response = $this;

        /**
         * Headers
         */
        // Write Headers to swoole response
        foreach ($response->getHeaders() as $key => $value) {
            $this->swooleResponse->header($key, implode(';', $value));
        }

        /**
         * Cookies
         */
        // TODO: handle cookies

        /**
         * Status code
         */
        $this->swooleResponse->status($response->getStatusCode());

        /**
         * Body
         */
        $this->swooleResponse->end($response->getBody()->getContents());
    }

    /**
     * 设置Body内容，使用默认的Stream
     *
     * @param string $content
     * @return static
     */
    public function withContent($content): Response
    {
        if ($this->stream) {
            return $this;
        }

        $new = clone $this;
        $new->stream = new SwooleStream($content);
        return $new;
    }

    /**
     * 添加cookie
     *
     * @param string  $key
     * @param  string $value
     * @param int     $expire
     * @param string  $path
     * @param string  $domain
     */
    public function addCookie($key, $value, $expire = 0, $path = '/', $domain = '')
    {
        $this->swooleResponse->cookie($key, $value, $expire, $path, $domain);
    }

    /**
     * @return null|\Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Throwable $exception
     * @return $this
     */
    public function setException(\Throwable $exception)
    {
        $this->exception = $exception;
        return $this;
    }

}
