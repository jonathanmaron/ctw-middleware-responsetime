<?php
declare(strict_types=1);

namespace Ctw\Middleware\ResponseTimeMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseTimeMiddleware extends AbstractResponseTimeMiddleware
{
    private const string HEADER = 'X-Response-Time';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $server    = $request->getServerParams();
        $startTime = $server['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $response  = $handler->handle($request);
        $endTime   = microtime(true);

        $value = sprintf('%2.3f ms', 1000 * ($endTime - $startTime));

        return $response->withHeader(self::HEADER, $value);
    }
}
