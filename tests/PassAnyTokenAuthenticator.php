<?php
declare(strict_types=1);

namespace Dyorg;

use Dyorg\TokenAuthentication\TokenSearch;
use Psr\Http\Message\ServerRequestInterface;

final class PassAnyTokenAuthenticator implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface &$request, TokenSearch $tokenSearch): bool
    {
        $tokenSearch->getToken($request);

        $request = $request->withAttribute('user', 'anybody');

        return true;
    }
}
