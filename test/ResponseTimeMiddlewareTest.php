<?php
declare(strict_types=1);

namespace CtwTest\Middleware\ResponseTimeMiddleware;

use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ResponseTimeMiddlewareTest extends AbstractCase
{
    public function testResponseTimeMiddleware(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $string = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $string);
    }

    public function testResponseTimeMiddlewareAsFloat(): void
    {
        $serverParams = [
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];
        $request      = Factory::createServerRequest('GET', '/', $serverParams);
        $stack        = [$this->getInstance()];
        $response     = Dispatcher::run($stack, $request);

        $string = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $string);
    }

    private function getInstance(): ResponseTimeMiddleware
    {
        $container = new ServiceManager();
        $factory   = new ResponseTimeMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
