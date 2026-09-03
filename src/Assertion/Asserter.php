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

namespace Awurth\Validator\Assertion;

use Awurth\Validator\Failure\ValidationFailureCollectionFactory;
use Awurth\Validator\Failure\ValidationFailureCollectionFactoryInterface;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\Failure\ValidationFailureFactory;
use Awurth\Validator\Failure\ValidationFailureFactoryInterface;
use Awurth\Validator\ValidationInterface;
use Respect\Validation\Exceptions\NestedValidationException;

use function array_replace;
use function is_array;

final class Asserter implements AsserterInterface
{
    /**
     * @param array<string, string> $messages
     */
    public function __construct(
        private readonly ValidationFailureCollectionFactoryInterface $validationFailureCollectionFactory,
        private readonly ValidationFailureFactoryInterface $validationFailureFactory,
        private readonly array $messages = [],
    ) {
    }

    /**
     * @param array<string, string> $messages
     */
    public static function create(array $messages = []): self
    {
        return new self(new ValidationFailureCollectionFactory(), new ValidationFailureFactory(), $messages);
    }

    public function assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface
    {
        $failures = $this->validationFailureCollectionFactory->create();

        try {
            $validation->getRules()->assert($subject);
        } catch (NestedValidationException $exception) {
            $message = $validation->getMessage();
            if (null !== $message) {
                $failures->add(
                    $this->validationFailureFactory->create($validation, $message, $subject),
                );

                return $failures;
            }

            $exceptionMessages = $this->extractMessagesFromException($exception, $validation);
            foreach ($exceptionMessages as $ruleName => $message) {
                $failures->add(
                    $this->validationFailureFactory->create($validation, $message, $subject, $ruleName),
                );
            }
        }

        return $failures;
    }

    /**
     * @return array<string, string>
     */
    private function extractMessagesFromException(NestedValidationException $exception, ValidationInterface $validation): array
    {
        $definedMessages = array_replace($this->messages, $validation->getGlobalMessages(), $validation->getMessages());

        $errors = [];
        foreach ($exception->getMessages($definedMessages) as $name => $error) {
            if (is_array($error)) {
                $errors = [...$errors, ...$error];
            } else {
                $errors[$name] = $error;
            }
        }

        return $errors;
    }
}
