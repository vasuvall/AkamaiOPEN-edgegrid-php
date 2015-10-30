<?php
/**
 * edgegrid-auth-php
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 */

namespace Akamai\Open\EdgeGrid\Tests;


class CliTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->factory = new \Akamai\Open\EdgeGrid\ClientFactory(function($section) {
            $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile($section);

            $mock = new MockHandler(new Response(200));
            $container = [];
            $history = Middleware::history($container);

            $handler = HandlerStack::create($mock);
            $handler->push($history, 'history');

            $client = new \Akamai\Open\EdgeGrid\Client(['handler' => $handler], $auth);

            return $client;
        });
    }


    public function testEmptyArgs()
    {
        ob_start();
        $cli = new \Akamai\Open\EdgeGrid\Cli($this->factory);
        $cli->run([__FILE__]);
        $out = ob_get_clean();

        $this->assertNotFalse(strpos($out, 'Usage:'));
    }
}
