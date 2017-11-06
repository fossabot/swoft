<?php

namespace Swoft\Web;

use Swoft\App;
use Swoft\Base\RequestContext;
use Swoft\Event\Event;
use Swoft\Filter\FilterChain;
use Swoft\Helper\ResponseHelper;

/**
 * 应用主体
 *
 * @uses      Application
 * @version   2017年04月25日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 Swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class Application extends \Swoft\Base\Application
{
    /**
     * handle request
     *
     * @param \Swoole\Http\Request  $request  Swoole request object
     * @param \Swoole\Http\Response $response Swoole response object
     * @return bool
     */
    public function doRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        // Fix Chrome ico request bug
        // TODO: Add Middleware mechanisms and move "fix the Chrome ico request bug" to middleware
        if (isset($request->server['request_uri']) && $request->server['request_uri'] === '/favicon.ico') {
            $response->end('favicon.ico');
            return false;
        }

        // Initialize Request and Response and set to RequestContent
        RequestContext::setRequest($request);
        RequestContext::setResponse($response);

        // Trigger 'Before Request' event
        App::trigger(Event::BEFORE_REQUEST);

        $swfRequest = RequestContext::getRequest();
        // Get URI and Method from request
        $uri = $swfRequest->getUri()->getPath();
        $method = $swfRequest->getMethod();

        // Run action of Controller by URI and Method
        $actionResponse = $this->runController($uri, $method);

        // Invalid Response was provided
        if (! $actionResponse instanceof \Swoft\Base\Response) {
            return false;
        }

        // Handle Response
        $actionResponse->send();

        // Trigger 'After Request' event
        App::trigger(Event::AFTER_REQUEST);
    }

    /**
     * rpc内部服务
     *
     * @param \Swoole\Server $server
     * @param int            $fd
     * @param int            $from_id
     * @param string         $data
     */
    public function doReceive(\Swoole\Server $server, int $fd, int $from_id, string $data)
    {
        try {
            // 解包
            $packer = App::getPacker();
            $data = $packer->unpack($data);

            App::trigger(Event::BEFORE_RECEIVE, null, $data);

            // 执行函数调用
            $response = $this->runService($data);
            $data = $packer->pack($response);
        } catch (\Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            $data = ResponseHelper::formatData("", $code, $message);
        }

        App::trigger(Event::AFTER_REQUEST);
        $server->send($fd, $data);
    }

    /**
     * 运行控制器
     *
     * @param string $uri
     * @param string $method
     * @return \Swoft\Web\Response
     * @throws \Exception
     */
    public function runController(string $uri, string $method = "get")
    {
        /* @var Router $router */
        $router = App::getBean('router');

        // 路由解析
        App::profileStart("router.match");
        list($path, $info) = $router->match($uri, $method);
        App::profileEnd("router.match");

        // 路由未定义处理
        if ($info == null) {
            throw new \RuntimeException("路由不存在，uri=" . $uri . " method=" . $method);
        }

        /* @var Controller $controller */
        list($controller, $actionId, $params) = $this->createController($path, $info);

        /* run controller with Filters */
        return $this->runControllerWithFilters($controller, $actionId, $params);
    }

    /**
     * run controller with Filters
     *
     * @param Controller $controller 控制器
     * @param string     $actionId   actionID
     * @param array      $params     action参数
     * @return \Swoft\Web\Response
     */
    private function runControllerWithFilters(Controller $controller, string $actionId, array $params)
    {
        $request = App::getRequest();
        $response = App::getResponse();

        /* @var FilterChain $filter */
        $filter = App::getBean('filter');

        App::profileStart("Filter");
        $result = $filter->doFilter($request, $response, $filter);
        App::profileEnd("Filter");

        if ($result) {
            $response = $controller->run($actionId, $params);
            // $response->send();
            return $response;
        }
    }

}
