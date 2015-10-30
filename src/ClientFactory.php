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

namespace Akamai\Open\EdgeGrid;


class ClientFactory
{
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function __invoke()
    {
        $args = func_get_args();
        call_user_func_array($this->callable, $args);
    }
}