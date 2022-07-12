<?php declare(strict_types=1);

/*
 * This file is part of Slim Token Authentication Middleware
 *
 * Copyright (c) 2016-2018 Dyorg Washington G. Almeida
 *
 * Licensed under the MIT license
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace Dyorg;

use Dyorg\TokenAuthentication\Exceptions\UnauthorizedExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Error Handler.
 * You can customize unauthorized response by implementing this interface.
 */
interface ErrorHandlerInterface
{
    /**
     * Process unauthorized exception.
     * Returns modified response instance.
     *
     * @param ServerRequestInterface         $request
     * @param ResponseInterface              $response
     * @param UnauthorizedExceptionInterface $exception
     *
     * @return ResponseInterface
     */
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        UnauthorizedExceptionInterface $exception
    ): ResponseInterface;
}
