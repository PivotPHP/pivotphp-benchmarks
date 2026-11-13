<?php

declare(strict_types=1);

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class PassThroughMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        return $handler($request);
    }
}
