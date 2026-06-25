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
     * Test that the middleware adds an X-Response-Time header in the expected millisecond format.
     */
    public function testProcessAddsResponseTimeHeaderInMillisecondFormat(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $string = $response->getHeaderLine('X-Response-Time');

        self::assertMatchesRegularExpression('/^\d{1,4}\.\d{3} ms$/', $string);
    }

    /**
     * Test that the middleware uses the REQUEST_TIME_FLOAT server param when it is provided.
     */
    public function testProcessUsesRequestTimeFloatServerParamWhenAvailable(): void
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
     * Test that the middleware implements MiddlewareInterface when instantiated through the factory.
     */
    public function testMiddlewareImplementsMiddlewareInterface(): void
    {
        $middleware = $this->getInstance();

        // @phpstan-ignore-next-line
        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * Test that the response carries a header named exactly X-Response-Time.
     */
    public function testProcessAddsHeaderNamedXResponseTime(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    /**
     * Test that the response time value is suffixed with the 'ms' millisecond unit.
     */
    public function testProcessReportsResponseTimeInMilliseconds(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        self::assertStringEndsWith(' ms', $header);
    }

    /**
     * Test that the response time value is formatted with exactly three decimal places.
     */
    public function testProcessFormatsResponseTimeWithThreeDecimalPlaces(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        // Extract the numeric part; the regex enforces exactly three decimals.
        $result = preg_match('/^(\d+\.\d{3}) ms$/', $header, $matches);

        self::assertSame(1, $result);
        self::assertMatchesRegularExpression('/^\d+\.\d{3}$/', $matches[1]);
    }

    /**
     * Test that the measured response time is never negative.
     */
    public function testProcessReportsNonNegativeResponseTime(): void
    {
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack);

        $header = $response->getHeaderLine('X-Response-Time');

        preg_match('/^(\d+\.\d+) ms$/', $header, $matches);
        $time = (float) ($matches[1] ?? 0);

        self::assertGreaterThanOrEqual(0.0, $time);
    }

    /**
     * Test that a REQUEST_TIME_FLOAT 100ms in the past yields a duration of at least 100ms.
     */
    public function testProcessReportsElapsedDurationFromPastRequestTimeFloat(): void
    {
        $pastTime     = microtime(true) - 0.1; // 100ms ago
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
     * Test that an integer REQUEST_TIME_FLOAT value still produces a valid millisecond header.
     */
    public function testProcessHandlesIntegerRequestTimeFloat(): void
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
     * Test that headers set by a downstream handler are preserved alongside the timing header.
     */
    public function testProcessPreservesDownstreamResponseHeaders(): void
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
     * Test that the status code set by a downstream handler is preserved by the middleware.
     */
    public function testProcessPreservesDownstreamResponseStatusCode(): void
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
     * Test that the factory produces a ResponseTimeMiddleware instance from the container.
     */
    public function testFactoryCreatesResponseTimeMiddlewareInstance(): void
    {
        $container  = new ServiceManager();
        $factory    = new ResponseTimeMiddlewareFactory();
        $middleware = $factory($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(ResponseTimeMiddleware::class, $middleware);
    }

    /**
     * Provides representative HTTP methods exercised by the middleware.
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
     * Test that the timing header is added regardless of the request HTTP method.
     */
    #[DataProvider('httpMethodProvider')]
    public function testProcessAddsHeaderForAnyHttpMethod(string $method): void
    {
        $request  = Factory::createServerRequest($method, '/');
        $stack    = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertTrue($response->hasHeader('X-Response-Time'));
    }

    /**
     * Provides representative URI paths exercised by the middleware.
     *
     * @return array<string, array{path: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'root path'   => [
                'path' => '/',
            ],
            'simple path' => [
                'path' => '/api',
            ],
            'nested path' => [
                'path' => '/api/v1/users',
            ],
            'with query'  => [
                'path' => '/search?q=test',
            ],
        ];
    }

    /**
     * Test that the timing header is added regardless of the request URI path.
     */
    #[DataProvider('pathProvider')]
    public function testProcessAddsHeaderForAnyRequestPath(string $path): void
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
