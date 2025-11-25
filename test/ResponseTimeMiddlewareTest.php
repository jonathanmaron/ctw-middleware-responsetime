<?php
declare(strict_types=1);

namespace CtwTest\Middleware\ResponseTimeMiddleware;

use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Server\MiddlewareInterface;

final class ResponseTimeMiddlewareTest extends AbstractCase
{
    /**
     * Test that middleware adds response time header
     */
    public function testResponseTimeMiddleware(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $string = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $string);
    }

    /**
     * Test that middleware uses REQUEST_TIME_FLOAT when available
     */
    public function testResponseTimeMiddlewareAsFloat(): void
    {
        $serverParams = [
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];
        $request  = Factory::createServerRequest('GET', '/', $serverParams);
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        $string = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $string);
    }

    /**
     * Test that middleware implements MiddlewareInterface
     */
    public function testMiddlewareImplementsMiddlewareInterface(): void
    {
        $middleware = $this->getInstance();

        // @phpstan-ignore-next-line
        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * Test that header name is X-Response-Time
     */
    public function testHeaderNameIsXResponseTime(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    /**
     * Test that response time is measured in milliseconds
     */
    public function testResponseTimeIsMeasuredInMilliseconds(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        self::assertStringEndsWith(' ms', $header);
    }

    /**
     * Test that response time has correct format with 3 decimal places
     */
    public function testResponseTimeHasThreeDecimalPlaces(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        // Extract the numeric part
        preg_match('/^(\d+\.\d{3}) ms$/', $header, $matches);

        self::assertNotEmpty($matches);
        self::assertCount(2, $matches);
    }

    /**
     * Test that response time is non-negative
     */
    public function testResponseTimeIsNonNegative(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        preg_match('/^(\d+\.\d+) ms$/', $header, $matches);
        $time = (float) ($matches[1] ?? 0);

        self::assertGreaterThanOrEqual(0.0, $time);
    }

    /**
     * Test that response time with past REQUEST_TIME_FLOAT shows longer duration
     */
    public function testResponseTimeWithPastRequestTimeFloat(): void
    {
        $pastTime = microtime(true) - 0.1; // 100ms ago
        $serverParams = [
            'REQUEST_TIME_FLOAT' => $pastTime,
        ];
        $request  = Factory::createServerRequest('GET', '/', $serverParams);
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        $header = $response->getHeaderLine('X-Response-Time');

        preg_match('/^(\d+\.\d+) ms$/', $header, $matches);
        $time = (float) ($matches[1] ?? 0);

        // Should be at least 100ms
        self::assertGreaterThanOrEqual(100.0, $time);
    }

    /**
     * Test that response time works with integer REQUEST_TIME
     */
    public function testResponseTimeWithIntegerRequestTime(): void
    {
        $serverParams = [
            'REQUEST_TIME_FLOAT' => time(),
        ];
        $request  = Factory::createServerRequest('GET', '/', $serverParams);
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        $header = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $header);
    }

    /**
     * Test that handler response is preserved
     */
    public function testHandlerResponseIsPreserved(): void
    {
        $stack = [
            $this->getInstance(),
            /**
             * @param mixed $request
             * @param mixed $next
             * @return \Psr\Http\Message\ResponseInterface
             */
            static function ($request, $next) {
                /** @var \Psr\Http\Server\RequestHandlerInterface $next */
                /** @var \Psr\Http\Message\ServerRequestInterface $request */
                $response = $next->handle($request);

                return $response->withHeader('X-Custom', 'value');
            },
        ];
        $response = Dispatcher::run($stack);

        self::assertTrue($response->hasHeader('X-Response-Time'));
        self::assertTrue($response->hasHeader('X-Custom'));
        self::assertSame('value', $response->getHeaderLine('X-Custom'));
    }

    /**
     * Test that handler response status code is preserved
     */
    public function testHandlerResponseStatusCodeIsPreserved(): void
    {
        $stack = [
            $this->getInstance(),
            /**
             * @param mixed $request
             * @param mixed $next
             * @return \Psr\Http\Message\ResponseInterface
             */
            static function ($request, $next) {
                /** @var \Psr\Http\Server\RequestHandlerInterface $next */
                /** @var \Psr\Http\Message\ServerRequestInterface $request */
                $response = $next->handle($request);

                return $response->withStatus(201);
            },
        ];
        $response = Dispatcher::run($stack);

        self::assertSame(201, $response->getStatusCode());
        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    /**
     * Test that factory creates middleware instance
     */
    public function testFactoryCreatesMiddlewareInstance(): void
    {
        $container  = new ServiceManager();
        $factory    = new ResponseTimeMiddlewareFactory();
        $middleware = $factory($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(ResponseTimeMiddleware::class, $middleware);
    }

    /**
     * Test various HTTP methods
     *
     * @return array<string, array{method: string}>
     */
    public static function httpMethodProvider(): array
    {
        return [
            'GET request'    => [
                'method' => 'GET',
            ],
            'POST request'   => [
                'method' => 'POST',
            ],
            'PUT request'    => [
                'method' => 'PUT',
            ],
            'DELETE request' => [
                'method' => 'DELETE',
            ],
            'PATCH request'  => [
                'method' => 'PATCH',
            ],
        ];
    }

    /**
     * Test that middleware works with various HTTP methods
     */
    #[DataProvider('httpMethodProvider')]
    public function testMiddlewareWorksWithVariousHttpMethods(string $method): void
    {
        $request  = Factory::createServerRequest($method, '/');
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    /**
     * Test various URI paths
     *
     * @return array<string, array{path: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'root path'      => [
                'path' => '/',
            ],
            'simple path'    => [
                'path' => '/api',
            ],
            'nested path'    => [
                'path' => '/api/v1/users',
            ],
            'with query'     => [
                'path' => '/search?q=test',
            ],
        ];
    }

    /**
     * Test that middleware works with various paths
     */
    #[DataProvider('pathProvider')]
    public function testMiddlewareWorksWithVariousPaths(string $path): void
    {
        $request  = Factory::createServerRequest('GET', $path);
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    private function getInstance(): ResponseTimeMiddleware
    {
        $container = new ServiceManager();
        $factory   = new ResponseTimeMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
