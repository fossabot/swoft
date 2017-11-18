<?php

namespace Swoft\Test\Web;


/**
 * @uses      MiddlewareTest
 * @version   2017年11月14日
 * @author    huangzhhui <huangzhwork@gmail.com>
 * @copyright Copyright 2010-2017 Swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class MiddlewareTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function dispatchController()
    {
        $response = $this->request('GET', '/middleware/testMiddlewares', [], parent::ACCEPT_JSON);
        $response->assertSuccessful()->assertHeader('X-Powered-By', 'Swoft');
        $response->assertSuccessful()->assertHeader('Middleware-Group-Test');
        $response->assertSuccessful()->assertHeader('Middleware-Action-Test');
        $response->assertSuccessful()->assertHeader('Sub-Middleware-Test');
    }
}