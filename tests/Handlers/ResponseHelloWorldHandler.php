<?php
declare(strict_types=1);

namespace Dyorg\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;

final class ResponseHelloWorldHandler implements RequestHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $streamFactory = new StreamFactory();
        return (AppFactory::determineResponseFactory())
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream(\json_encode([
                'message' => 'Hello World!',
            ])));
    }
}
