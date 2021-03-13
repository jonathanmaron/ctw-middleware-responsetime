<?php
declare(strict_types=1);

namespace Ctw\Middleware\ResponseTimeMiddleware;

use Psr\Container\ContainerInterface;

class ResponseTimeMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ResponseTimeMiddleware
    {
        return new ResponseTimeMiddleware();
    }
}
