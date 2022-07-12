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

use Dyorg\TokenAuthentication\TokenSearch;
use Dyorg\TokenAuthentication\Exceptions\UnauthorizedExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authenticator.
 * Required param of authentication middleware. Will make the token validation of your application.
 */
interface AuthenticatorInterface
{
    /**
     * Process token validation of the application.
     * @see "Getting authentication" section of the main README.md
     *
     * @param ServerRequestInterface
     * @param TokenSearch
     *
     * @throws UnauthorizedExceptionInterface
     *
     * @return bool Returns false when user not found or access restricted.
     */
    public function authenticate(
        ServerRequestInterface &$request,
        TokenSearch $tokenSearch
    ): bool;
}
