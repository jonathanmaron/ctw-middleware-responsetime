<?php
declare(strict_types=1);

namespace CtwTest\Middleware\ResponseTimeMiddleware;

use Ctw\Middleware\ResponseTimeMiddleware\ConfigProvider;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddlewareFactory;

class ConfigProviderTest extends AbstractCase
{
    public function testConfigProvider(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'dependencies' => [
                'factories' => [
                    ResponseTimeMiddleware::class => ResponseTimeMiddlewareFactory::class,
                ],
            ],
        ];

        self::assertSame($expected, $configProvider->__invoke());
    }
}
