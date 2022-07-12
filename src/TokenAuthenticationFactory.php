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

use Dyorg\AuthenticatorInterface;
use Dyorg\ErrorHandlerInterface;
use Dyorg\TokenAuthentication;
use JsonSerializable;
use RuntimeException;

/**
 * Token authentication middleware factory.
 * Might be useful when you need to apply different security schemas to app routes.
 * Also very useful with dependency injection container like PHP-DI.
 */
class TokenAuthenticationFactory implements JsonSerializable
{
    /** @var bool */
    protected $secure;

    /** @var string[] */
    protected $relaxed;

    /** @var string|null */
    protected $header = 'Authorization';

    /** @var string */
    protected $regex = '/Bearer\s+(.*)$/i';

    /** @var string|null */
    protected $parameter = 'authorization';

    /** @var string|null */
    protected $cookie = 'authorization';

    /** @var string|null */
    protected $attribute = 'authorization';

    /** @var string[]|null */
    protected $path;

    /** @var string[]|null */
    protected $except;

    /** @var AuthenticatorInterface|null */
    protected $authenticator;

    /** @var ErrorHandlerInterface|null */
    protected $error;

    /**
     * Factory constructor.
     *
     * @param bool                        $secure
     * @param string[]|null               $relaxed
     * @param AuthenticatorInterface|null $authenticator
     * @param ErrorHandlerInterface|null  $error
     */
    public function __construct(
        bool $secure = true,
        ?array $relaxed = ['localhost', '127.0.0.1'],
        ?AuthenticatorInterface $authenticator = null,
        ?ErrorHandlerInterface $error = null
    ) {
        $this->setSecure($secure);
        $this->setRelaxed($relaxed);
        if ($authenticator instanceof AuthenticatorInterface) {
            $this->setAuthenticator($authenticator);
        }
        $this->setError($error);
    }

    /**
     * Set secure option.
     * Tokens are essentially passwords. You should treat them as such and you should always use HTTPS. If the middleware
     * detects insecure usage over HTTP it will return unauthorized with a message Required HTTPS for token authentication.
     * This rule is relaxed for requests on localhost.
     * To allow insecure usage you must enable it manually by setting secure to false.
     *
     * @param bool $secure
     *
     * @return TokenAuthenticationFactory
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Set relaxed option.
     * Alternatively you can list your development host to have relaxed security.
     *
     * @param string[]|null $relaxed
     *
     * @return TokenAuthenticationFactory
     */
    public function setRelaxed(?array $relaxed)
    {
        $this->relaxed = $relaxed;
        return $this;
    }

    /**
     * Set error option.
     * By default on ocurred a fail on authentication, is sent a response on json format with a message (Invalid Token
     * or Not found Token) and with the token (if found), with status 401 Unauthorized. You can customize it by setting
     * a callable function on error option.
     *
     * @param ErrorHandlerInterface|null $error
     *
     * @return TokenAuthenticationFactory
     */
    public function setError(?ErrorHandlerInterface $error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Set authenticator function, this function will make the token validation of your application.
     *
     * @param AuthenticatorInterface $authenticator
     *
     * @return TokenAuthenticationFactory
     */
    public function setAuthenticator(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
        return $this;
    }

    /**
     * Set authorization header name. This option is case-insensitive.
     * Authorization header the value format as Bearer <token>, it is matched using a regular expression.
     * If you want to work without token type or with other token type, like Basic <token>, you can change
     * the regular expression pattern setting it on regex option. You can disabled authentication via header
     * by setting header option as null.
     *
     * @param string|null $header Default is 'Authorization'
     *
     * @return TokenAuthenticationFactory
     */
    public function setHeader(?string $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Set regex option.
     * Makes possible to parse token from complex strings like `Bearer token`.
     *
     * @param string $regex Default is '/Bearer\s+(.*)$/i'
     *
     * @throws \InvalidArgumentException when pattern is empty string.
     *
     * @return TokenAuthenticationFactory
     */
    public function setRegex(string $regex = '/Bearer\s+(.*)$/i')
    {
        if (empty($regex)) {
            throw new \InvalidArgumentException('"regex" option cannot be empty. To parse whole header use [:print:] pattern.');
        }
        $this->regex = $regex;
        return $this;
    }

    /**
     * Set authorization query name. This option is case-sensitive.
     * As a last resort, middleware tries to find authorization query parameter.
     * You can change parameter name using parameter option. You can disable authentication
     * via parameter by setting parameter option as null.
     * Be Careful! User tokens shouldn't be send by parameters in production environment,
     * it's represent a potential security risk. Prefer use header or cookie options.
     *
     * @param string|null $parameter Default is 'authorization'(case sensitive)
     *
     * @return TokenAuthenticationFactory
     */
    public function setParameter(?string $parameter)
    {
        $this->parameter = $parameter;
        return $this;
    }

    /**
     * Set authorization cookie name. This option is case-sensitive.
     * If token is not found into headers, middleware tries to find authorization cookie.
     * You can change cookie name using cookie option. You can disabled authentication
     * via cookie by setting cookie option as null.
     *
     * @param string|null $cookie Default is 'authorization'(case sensitive)
     *
     * @return TokenAuthenticationFactory
     */
    public function setCookie(?string $cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * Set request attribute name option.
     * When token is found, it's storage into authorization_token attribute of ServerRequestInterface : $request object.
     * This behavior enables you to recovery the token posterioly on your application.
     * You can change attribute name using attribute option. You can disabled the storing of token on the attributes
     * by setting attribute option to null.
     *
     * @param string|null $attribute
     *
     * @return TokenAuthenticationFactory
     */
    public function setAttribute(?string $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * Set path option.
     * By default no route requires authentication. You must set one or more routes to be restricted
     * by authentication, setting it on path option.
     *
     * @param string[]|null $path
     *
     * @return TokenAuthenticationFactory
     */
    public function setPath(?array $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set except option.
     * You can configure which routes do not require authentication, setting it on except option.
     *
     * @param string[]|null
     *
     * @return TokenAuthenticationFactory
     */
    public function setExcept(?array $except)
    {
        $this->except = $except;
        return $this;
    }

    /**
     * Create new middleware instance from stored options.
     * Main factory method.
     *
     * @throws \RuntimeException when required options not set and we cannot build middleware instance.
     *
     * @return TokenAuthentication
     */
    public function create(): TokenAuthentication
    {
        $nonEmpty = function ($prop) { return empty($prop) === false; };
        $searchProps = [$this->header, $this->parameter, $this->cookie];
        if (count(\array_filter($searchProps, $nonEmpty)) < 1) {
            throw new \RuntimeException('At least one search options must be set: header, parameter, cookie. Otherwise there is nowhere to find authorization token.');
        }

        $options = $this->buildOptions();
        // remove nullable values
        foreach (['authenticator', 'error'] as $prop) {
            if (\is_null($options[$prop])) unset($options[$prop]);
        }

        return new TokenAuthentication($options);
    }

    /**
     * Builds middleware options. Might be useful during debugging process and testing.
     *
     * @return array Options list as assoc array
     */
    public function buildOptions(): array
    {
        $options = [
            'secure' => $this->secure,
            'relaxed' => $this->relaxed,
            'header' => $this->header,
            'regex' => $this->regex,
            'parameter' => $this->parameter,
            'cookie' => $this->cookie,
            'attribute' => $this->attribute,
            'path' => $this->path,
            'except' => $this->except,
            'authenticator' => $this->authenticator,
            'error' => $this->error,
        ];
        if ($this->authenticator instanceof AuthenticatorInterface) {
            $options['authenticator'] = [$this->authenticator, 'authenticate'];
        }
        if ($this->error instanceof ErrorHandlerInterface) {
            $options['error'] = [$this->error, 'handle'];
        }

        return $options;
    }

    /**
     * Returns string of options list.
     * Might be useful during debugging process.
     *
     * @return string options list
     */
    public function __toString(): string
    {
        $options = $this->buildOptions();
        // replace instances to class names for readability
        foreach (['authenticator', 'error'] as $prop) {
            // check that it's callable
            if (\is_array($options[$prop]) && \is_object($options[$prop][0])) {
                $options[$prop][0] = \get_class($options[$prop][0]);
            }
        }
        return \print_r($options, true);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $options = $this->buildOptions();
        // replace instances to class names for readability
        foreach (['authenticator', 'error'] as $prop) {
            // check that it's callable
            if (\is_array($options[$prop]) && \is_object($options[$prop][0])) {
                $options[$prop][0] = \get_class($options[$prop][0]);
            }
        }
        return $options;
    }
}
