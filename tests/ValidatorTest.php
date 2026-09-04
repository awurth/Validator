<?php

declare(strict_types=1);

/*
 * This file is part of the Awurth Validator package.
 *
 * (c) Alexis Wurth <awurth.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Awurth\Validator\Tests;

use Awurth\Validator\Exception\InvalidPropertyOptionsException;
use Awurth\Validator\Validator;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Respect\Validation\ValidatorBuilder as V;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ValidatorTest extends TestCase
{
    private ServerRequestInterface $request;

    private Validator $validator;

    #[Override]
    protected function setUp(): void
    {
        $this->request = new ServerRequestFactory()->createServerRequest('POST', 'http://localhost?username=a_wurth&password=1234');

        $this->validator = Validator::create();
    }

    public function testValidateWithoutRules(): void
    {
        $this->expectException(InvalidPropertyOptionsException::class);

        $this->validator->validate($this->request, ['username' => null]);
    }

    public function testValidateWithRulesWrongType(): void
    {
        $this->expectException(InvalidPropertyOptionsException::class);

        $this->validator->validate($this->request, [
            'username' => [
                'rules' => null,
            ],
        ]);
    }

    public function testValidateWithRulesEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate($this->request, []);
    }

    public function testRequest(): void
    {
        $errors = $this->validator->validate($this->request, ['username' => V::length(V::greaterThanOrEqual(6))]);

        self::assertCount(0, $errors);

        $errors = $this->validator->validate($this->request, ['username' => V::length(V::greaterThanOrEqual(8))]);

        self::assertCount(1, $errors);
    }

    public function testRequestWithRouteArguments(): void
    {
        $app = AppFactory::create();
        $app->get('/users/{username}', static fn (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface => $response);

        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return new Response();
            }
        };

        $app->addRoutingMiddleware()->process(
            new ServerRequestFactory()->createServerRequest('GET', 'http://localhost/users/a_wurth'),
            $handler,
        );

        self::assertInstanceOf(ServerRequestInterface::class, $handler->request);

        self::assertCount(0, $this->validator->validate($handler->request, ['username' => V::length(V::greaterThanOrEqual(6))]));
        self::assertCount(1, $this->validator->validate($handler->request, ['username' => V::length(V::greaterThanOrEqual(8))]));
    }

    public function testArray(): void
    {
        $array = [
            'username' => 'a_wurth',
            'password' => '1234',
        ];

        $errors = $this->validator->validate($array, [
            'username' => V::notBlank(),
            'password' => V::notBlank(),
        ]);

        self::assertCount(0, $errors);

        $errors = $this->validator->validate($array, [
            'username' => V::notBlank()->length(V::greaterThanOrEqual(10)),
            'password' => V::notBlank()->length(V::greaterThanOrEqual(10)),
        ]);

        self::assertCount(2, $errors);
    }

    public function testObject(): void
    {
        $object = new TestObject('private', 'protected', 'public');
        $errors = $this->validator->validate($object, [
            'privateProperty' => V::notBlank(),
            'protectedProperty' => V::notBlank(),
            'publicProperty' => V::notBlank(),
        ]);

        self::assertCount(1, $errors);
    }

    public function testValue(): void
    {
        $errors = $this->validator->validate(2017, V::numericVal()->between(2010, 2020));

        self::assertCount(0, $errors);

        $errors = $this->validator->validate(2021, V::numericVal()->between(2010, 2020));

        self::assertCount(1, $errors);
    }

    public function testValidateWithErrors(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(1, $errors);

        $error = $errors->get(0);

        self::assertSame('username', $error->getValidation()->getProperty());
        self::assertSame('lengthGreaterThanOrEqual', $error->getRuleName());
        self::assertSame('a_wurth', $error->getInvalidValue());
        self::assertSame('The length of "a_wurth" must be greater than or equal to 8', $error->getMessage());
    }

    public function testValidateWithCustomDefaultMessage(): void
    {
        $validator = Validator::create(messages: ['lengthGreaterThanOrEqual' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(1, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
    }

    public function testValidateWithCustomGlobalMessages(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
            'password' => V::length(V::greaterThanOrEqual(8)),
        ], ['lengthGreaterThanOrEqual' => 'Too short!']);

        self::assertCount(2, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
    }

    public function testValidateWithCustomDefaultAndGlobalMessages(): void
    {
        $validator = Validator::create(messages: ['lengthGreaterThanOrEqual' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
            'password' => V::length(V::greaterThanOrEqual(8))->alpha(),
        ], ['alpha' => 'Only letters are allowed']);

        self::assertCount(3, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
        self::assertSame('Only letters are allowed', $errors->get(2)->getMessage());
        self::assertSame('alpha', $errors->get(2)->getRuleName());
    }

    public function testValidateWithCustomIndividualMessage(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(V::greaterThanOrEqual(8)),
                'messages' => [
                    'lengthGreaterThanOrEqual' => 'Too short!',
                ],
            ],
            'password' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(2, $errors);
        self::assertSame('username', $errors->get(0)->getValidation()->getProperty());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('password', $errors->get(1)->getValidation()->getProperty());
        self::assertSame('The length of "1234" must be greater than or equal to 8', $errors->get(1)->getMessage());
    }

    public function testValidateWithWrongCustomSingleMessageType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The option "message" with value 10 is expected to be of type "null" or "string", but is of type "int".');

        $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(V::greaterThanOrEqual(8))->alnum(),
                'message' => 10,
            ],
        ]);
    }

    public function testValidateWithCustomSingleMessage(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(V::greaterThanOrEqual(8))->alnum(),
                'message' => 'Bad username.',
                'messages' => [
                    'lengthGreaterThanOrEqual' => 'Too short!',
                ],
            ],
            'password' => [
                'rules' => V::length(V::greaterThanOrEqual(8)),
                'messages' => [
                    'lengthGreaterThanOrEqual' => 'Too short!',
                ],
            ],
        ]);

        self::assertCount(2, $errors);
        self::assertSame('username', $errors->get(0)->getValidation()->getProperty());
        self::assertSame('Bad username.', $errors->get(0)->getMessage());
        self::assertSame('password', $errors->get(1)->getValidation()->getProperty());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
    }

    public function testValidateDoesNotReportTheCompositeFailure(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8))->alnum(),
        ]);

        self::assertCount(2, $errors);
        self::assertSame('lengthGreaterThanOrEqual', $errors->get(0)->getRuleName());
        self::assertSame('alnum', $errors->get(1)->getRuleName());
    }
}
