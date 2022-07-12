<?php
declare(strict_types=1);

namespace Dyorg;

use Dyorg\TokenAuthentication\Exceptions\UnauthorizedException;
use Dyorg\TokenAuthentication\TokenSearch;
use Psr\Http\Message\ServerRequestInterface;

final class FoobarFoobazAuthenticator implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface &$request, TokenSearch $tokenSearch): bool
    {
        $token = $tokenSearch->getToken($request);

        if ($token !== 'foobarfoobaz') {
            throw new UnauthorizedException('Token not found');
        }

        $request = $request->withAttribute('user', 'foobarfoobazuser');

        return true;
    }
}
