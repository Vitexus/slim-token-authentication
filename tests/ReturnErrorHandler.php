<?php
declare(strict_types=1);

namespace Dyorg;

use Dyorg\TokenAuthentication\Exceptions\UnauthorizedExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ReturnErrorHandler implements ErrorHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response, UnauthorizedExceptionInterface $exception): ResponseInterface
    {
        $response->getBody()->write(\json_encode([
            'error_description' => (string) $exception,
        ]));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
