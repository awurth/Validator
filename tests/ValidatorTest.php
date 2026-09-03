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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Validator as V;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ValidatorTest extends TestCase
{
    private ServerRequestInterface $request;
    private Validator $validator;

    protected function setUp(): void
    {
        $this->request = (new ServerRequestFactory())->createServerRequest('POST', 'http://localhost?username=a_wurth&password=1234');

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
        $this->expectException(\InvalidArgumentException::class);

        $this->validator->validate($this->request, []);
    }

    public function testRequest(): void
    {
        $errors = $this->validator->validate($this->request, ['username' => V::length(6)]);

        self::assertSame(0, $errors->count());

        $errors = $this->validator->validate($this->request, ['username' => V::length(8)]);

        self::assertSame(1, $errors->count());
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

        self::assertSame(0, $errors->count());

        $errors = $this->validator->validate($array, [
            'username' => V::notBlank()->length(10),
            'password' => V::notBlank()->length(10),
        ]);

        self::assertSame(2, $errors->count());
    }

    public function testObject(): void
    {
        $object = new TestObject('private', 'protected', 'public');
        $errors = $this->validator->validate($object, [
            'privateProperty' => V::notBlank(),
            'protectedProperty' => V::notBlank(),
            'publicProperty' => V::notBlank(),
        ]);

        self::assertSame(1, $errors->count());
    }

    public function testValue(): void
    {
        $errors = $this->validator->validate(2017, V::numericVal()->between(2010, 2020));

        self::assertSame(0, $errors->count());

        $errors = $this->validator->validate(2021, V::numericVal()->between(2010, 2020));

        self::assertSame(1, $errors->count());
    }

    public function testValidateWithErrors(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(8),
        ]);

        self::assertSame(1, $errors->count());

        $error = $errors->get(0);

        self::assertSame('username', $error->getValidation()->getProperty());
        self::assertSame('length', $error->getRuleName());
        self::assertSame('a_wurth', $error->getInvalidValue());
        self::assertSame('"a_wurth" must have a length greater than or equal to 8', $error->getMessage());
    }

    public function testValidateWithCustomDefaultMessage(): void
    {
        $validator = Validator::create(messages: ['length' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(8),
        ]);

        self::assertSame(1, $errors->count());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
    }

    public function testValidateWithCustomGlobalMessages(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(8),
            'password' => V::length(8),
        ], ['length' => 'Too short!']);

        self::assertSame(2, $errors->count());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
    }

    public function testValidateWithCustomDefaultAndGlobalMessages(): void
    {
        $validator = Validator::create(messages: ['length' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(8),
            'password' => V::length(8)->alpha(),
        ], ['alpha' => 'Only letters are allowed']);

        self::assertSame(3, $errors->count());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
        self::assertSame('Only letters are allowed', $errors->get(2)->getMessage());
        self::assertSame('alpha', $errors->get(2)->getRuleName());
    }

    public function testValidateWithCustomIndividualMessage(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(8),
                'messages' => [
                    'length' => 'Too short!',
                ],
            ],
            'password' => V::length(8),
        ]);

        self::assertSame(2, $errors->count());
        self::assertSame('username', $errors->get(0)->getValidation()->getProperty());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('password', $errors->get(1)->getValidation()->getProperty());
        self::assertSame('"1234" must have a length greater than or equal to 8', $errors->get(1)->getMessage());
    }

    public function testValidateWithWrongCustomSingleMessageType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "message" with value 10 is expected to be of type "null" or "string", but is of type "int".');

        $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(8)->alnum(),
                'message' => 10,
            ],
        ]);
    }

    public function testValidateWithCustomSingleMessage(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(8)->alnum(),
                'message' => 'Bad username.',
                'messages' => [
                    'length' => 'Too short!',
                ],
            ],
            'password' => [
                'rules' => V::length(8),
                'messages' => [
                    'length' => 'Too short!',
                ],
            ],
        ]);

        self::assertSame(2, $errors->count());
        self::assertSame('username', $errors->get(0)->getValidation()->getProperty());
        self::assertSame('Bad username.', $errors->get(0)->getMessage());
        self::assertSame('password', $errors->get(1)->getValidation()->getProperty());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
    }
}
