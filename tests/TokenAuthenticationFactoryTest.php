<?php
declare(strict_types=1);

namespace Dyorg;

use Dyorg\TokenAuthenticationFactory;
use Dyorg\PassAnyTokenAuthenticator;
use Dyorg\RejectAllTokensAuthenticator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;

/**
 * Purpose of this test is to modify the same factory instance and produce different middlewares.
 * @coversDefaultClass \Dyorg\TokenAuthenticationFactory
 */
final class TokenAuthenticationFactoryTest extends TestCase
{
    /** @var TokenAuthenticationFactory */
    public static $factory;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        // global factory to check mutations
        static::$factory = new TokenAuthenticationFactory();
    }

    /**
     * @covers ::__construct
     * @covers ::buildOptions
     * @covers ::__toString
     * @covers ::jsonSerialize
     */
    public function testConstructor(): void
    {
        $factory = new TokenAuthenticationFactory();
        $this->assertEquals($this->getDefaultOptions(), $factory->buildOptions());
        $this->assertEquals(\print_r($this->getDefaultOptions(), true), (string) $factory);
        $this->assertJsonStringEqualsJsonString(\json_encode($this->getDefaultOptions()), \json_encode($factory));
    }

    /**
     * @covers ::__construct
     * @covers ::setError
     * @covers ::buildOptions
     * @covers ::__toString
     * @covers ::jsonSerialize
     */
    public function testConstructorWithArguments(): void
    {
        $authenticator = new RejectAllTokensAuthenticator();
        $errorHandler = new ReturnErrorHandler();
        $factory = (new TokenAuthenticationFactory(false, null, $authenticator))
            ->setError($errorHandler);
        $expectedOptions = \array_merge(
            $this->getDefaultOptions(),
            [
                'secure' => false,
                'relaxed' => null,
                'authenticator' => [
                    $authenticator,
                    'authenticate',
                ],
                'error' => [
                    $errorHandler,
                    'handle',
                ],
            ]
        );
        $this->assertEquals($expectedOptions, $factory->buildOptions());
        $expectedOptions['authenticator'][0] = RejectAllTokensAuthenticator::class;
        $expectedOptions['error'][0] = ReturnErrorHandler::class;
        $this->assertEquals(\print_r($expectedOptions, true), (string) $factory);
        $this->assertJsonStringEqualsJsonString(\json_encode($expectedOptions), \json_encode($factory));
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateWithoutAuthenticator(): void
    {
        $factory = new TokenAuthenticationFactory();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Authenticator option has not been setted.');
        $factory->create();
    }

    /**
     * @covers ::__construct
     * @covers ::setAuthenticator
     * @covers ::setHeader
     * @covers ::setParameter
     * @covers ::setCookie
     * @covers ::create
     */
    public function testCreateWithoutRequiredOptions(): void
    {
        $factory = new TokenAuthenticationFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('At least one search options must be set: header, parameter, cookie. Otherwise there is nowhere to find authorization token.');
        $factory
            ->setAuthenticator(new RejectAllTokensAuthenticator())
            ->setHeader(null)
            ->setParameter(null)
            ->setCookie(null)
            ->create();
    }

    /**
     * @covers ::__construct
     * @covers ::setRegex
     */
    public function testEmptyRegex(): void
    {
        $factory = new TokenAuthenticationFactory();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"regex" option cannot be empty. To parse whole header use [:print:] pattern.');
        $factory->setRegex('');
    }

    /**
     * @dataProvider provideListOfOptions
     * @covers ::__construct
     * @covers ::setAuthenticator
     * @covers ::setError
     * @covers ::setSecure
     * @covers ::setRelaxed
     * @covers ::setHeader
     * @covers ::setRegex
     * @covers ::setParameter
     * @covers ::setCookie
     * @covers ::setAttribute
     * @covers ::setPath
     * @covers ::setExcept
     * @covers ::create
     */
    public function testFactoryMutationWithFixtures(
        array $expectedOptionsList,
        ?AuthenticatorInterface $authenticator,
        ?ErrorHandlerInterface $errorHandler,
        ?bool $secureOption,
        ?array $relaxedOption,
        ?string $headerOption,
        ?string $regexOption,
        ?string $parameterOption,
        ?string $cookieOption,
        ?string $attributeOption,
        ?array $pathOption,
        ?array $exceptOption
    ): void {
        $optionsList = static::$factory
            ->setAuthenticator($authenticator)
            ->setError($errorHandler)
            ->setSecure($secureOption)
            ->setRelaxed($relaxedOption)
            ->setHeader($headerOption)
            ->setRegex($regexOption)
            ->setParameter($parameterOption)
            ->setCookie($cookieOption)
            ->setAttribute($attributeOption)
            ->setPath($pathOption)
            ->setExcept($exceptOption)
            ->jsonSerialize();

        $this->assertEquals($expectedOptionsList, $optionsList);
    }

    public function provideListOfOptions(): array
    {
        return [
            'defaults' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => null,
                        'header' => null,
                        'regex' => '/Bearer\s+(.*)$/i',
                        'parameter' => null,
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                // new ResponseHelloWorldHandler(),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                null, // relaxed
                null, // header
                '/Bearer\s+(.*)$/i', // regex
                null, // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'secure false' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'secure' => false,
                        'relaxed' => [],
                        'header' => null,
                        'regex' => '/Bearer\s+(.*)$/i',
                        'parameter' => null,
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                false, // secure
                [], // relaxed
                null, // header
                '/Bearer\s+(.*)$/i', // regex
                null, // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'relaxed example.dev' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => null,
                        'regex' => '/Bearer\s+(.*)$/i',
                        'parameter' => null,
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                null, // header
                '/Bearer\s+(.*)$/i', // regex
                null, // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'non-common header' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Bearer\s+(.*)$/i',
                        'parameter' => null,
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Bearer\s+(.*)$/i', // regex
                null, // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'basic auth' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => null,
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                null, // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'query param auth' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => null,
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                null, // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'cookie param auth' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => 'auth-in-cookie',
                        'attribute' => null,
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                'auth-in-cookie', // cookie
                null, // attribute
                ['/'], // path
                null, // except
            ],
            'custom attribute name' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => 'auth-in-cookie',
                        'attribute' => 'auth-attr',
                        'path' => ['/'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                'auth-in-cookie', // cookie
                'auth-attr', // attribute
                ['/'], // path
                null, // except
            ],
            'multiple endpoints' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => 'auth-in-cookie',
                        'attribute' => 'auth-attr',
                        'path' => ['/foobar', '/foobaz'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                'auth-in-cookie', // cookie
                'auth-attr', // attribute
                ['/foobar', '/foobaz'], // path
                null, // except
            ],
            'ignore endpoints' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => 'auth-in-cookie',
                        'attribute' => 'auth-attr',
                        'path' => ['/foobar', '/foobaz'],
                        'except' => ['/ignore-first', '/ignore-second'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                null, // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                'auth-in-cookie', // cookie
                'auth-attr', // attribute
                ['/foobar', '/foobaz'], // path
                ['/ignore-first', '/ignore-second'], // except
            ],
            'error handler' => [
                \array_merge(
                    $this->getDefaultOptions(),
                    [
                        'relaxed' => ['example.dev'],
                        'header' => 'Token-Authorization-X',
                        'regex' => '/Basic\s+(.*)$/i',
                        'parameter' => 'auth',
                        'cookie' => 'auth-in-cookie',
                        'attribute' => 'auth-attr',
                        'path' => ['/foobar', '/foobaz'],
                        'except' => ['/ignore-first', '/ignore-second'],
                        'authenticator' => [
                            RejectAllTokensAuthenticator::class,
                            'authenticate',
                        ],
                        'error' => [
                            ReturnErrorHandler::class,
                            'handle',
                        ],
                    ]
                ),
                new RejectAllTokensAuthenticator(),
                new ReturnErrorHandler(), // error handler
                true, // secure
                ['example.dev'], // relaxed
                'Token-Authorization-X', // header
                '/Basic\s+(.*)$/i', // regex
                'auth', // parameter
                'auth-in-cookie', // cookie
                'auth-attr', // attribute
                ['/foobar', '/foobaz'], // path
                ['/ignore-first', '/ignore-second'], // except
            ],
        ];
    }

    /**
     * @covers ::__construct
     */
    public function testAsGlobalSlimMiddleware(): void
    {
        $requestFactory = new ServerRequestFactory();
        $uriFactory = new UriFactory();
        $app = AppFactory::create();
        $controller = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->getBody()->write('Authentication passed!');
            return $res->withStatus(200);
        };
        $factory = (new TokenAuthenticationFactory(true, null, new PassAnyTokenAuthenticator()))
            ->setPath(['/friends', '/pictures']);

        $app->get('/user', $controller);
        $app->get('/friends', $controller);
        $app->get('/pictures', $controller);
        $app->get('/public-pics', $controller);
        $app->add($factory->create());

        $userRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/user')->withScheme('https'));
        $response = $app->handle($userRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $friendsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/friends')->withScheme('https'));
        $response = $app->handle($friendsRequest);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Authorization token not found","token":null}', (string) $response->getBody());

        $response = $app->handle($friendsRequest->withHeader('Authorization', 'Bearer OpenSesame'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $picsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/pictures')->withScheme('https'));
        $response = $app->handle($picsRequest);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Authorization token not found","token":null}', (string) $response->getBody());

        $response = $app->handle($picsRequest->withHeader('Authorization', 'Bearer OpenSesame'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $picsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/public-pics')->withScheme('https'));
        $response = $app->handle($picsRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());
    }

    /**
     * @covers ::__construct
     */
    public function testAsRouteSlimMiddleware(): void
    {
        $requestFactory = new ServerRequestFactory();
        $uriFactory = new UriFactory();
        $app = AppFactory::create();
        $controller = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->getBody()->write('Authentication passed!');
            return $res->withStatus(200);
        };
        $factory = new TokenAuthenticationFactory(true, null, new PassAnyTokenAuthenticator());

        $app->get('/user', $controller);
        $app->get('/friends', $controller)->add($factory->create());
        $app->get('/pictures', $controller)->add($factory->create());
        $app->get('/public-pics', $controller);

        $userRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/user')->withScheme('https'));
        $response = $app->handle($userRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $friendsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/friends')->withScheme('https'));
        $response = $app->handle($friendsRequest);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Authorization token not found","token":null}', (string) $response->getBody());

        $response = $app->handle($friendsRequest->withHeader('Authorization', 'Bearer OpenSesame'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $picsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/pictures')->withScheme('https'));
        $response = $app->handle($picsRequest);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Authorization token not found","token":null}', (string) $response->getBody());

        $response = $app->handle($picsRequest->withHeader('Authorization', 'Bearer OpenSesame'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());

        $picsRequest = $requestFactory->createServerRequest('GET', $uriFactory->createUri('/public-pics')->withScheme('https'));
        $response = $app->handle($picsRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Authentication passed!', (string) $response->getBody());
    }

    /**
     * @private
     */
    private function getDefaultOptions(): array
    {
        return [
            'secure' => true,
            'relaxed' => ['localhost', '127.0.0.1'],
            'header' => 'Authorization',
            'regex' => '/Bearer\s+(.*)$/i',
            'parameter' => 'authorization',
            'cookie' => 'authorization',
            'attribute' => 'authorization',
            'path' => null,
            'except' => null,
            'authenticator' => null,
            'error' => null,
        ];
    }
}
