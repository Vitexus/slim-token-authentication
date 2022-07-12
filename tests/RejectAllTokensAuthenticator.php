<?php
declare(strict_types=1);

namespace Dyorg;

use Dyorg\TokenAuthentication\Exceptions\UnauthorizedException;
use Dyorg\TokenAuthentication\TokenSearch;
use Psr\Http\Message\ServerRequestInterface;

final class RejectAllTokensAuthenticator implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface &$request, TokenSearch $tokenSearch): bool
    {
        $tokenSearch->getToken($request);
        throw new UnauthorizedException('User not found');
    }
}
