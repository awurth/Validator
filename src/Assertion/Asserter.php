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
use Respect\Validation\ValidatorBuilder;

use function array_replace;
use function is_array;
use function is_string;

final readonly class Asserter implements AsserterInterface
{
    private const string COMPOSITE_MESSAGE_KEY = '__root__';

    /**
     * @param array<string, string> $messages
     */
    public function __construct(
        private ValidationFailureCollectionFactoryInterface $validationFailureCollectionFactory,
        private ValidationFailureFactoryInterface $validationFailureFactory,
        private array $messages = [],
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

        $templates = array_replace($this->messages, $validation->getGlobalMessages(), $validation->getMessages());
        $result = ValidatorBuilder::init($validation->getRules())->validate($subject, $templates);

        if (!$result->hasFailed()) {
            return $failures;
        }

        $message = $validation->getMessage();
        if (null !== $message) {
            $failures->add(
                $this->validationFailureFactory->create($validation, $message, $subject),
            );

            return $failures;
        }

        foreach ($this->flattenMessages($result->getMessages()) as $ruleName => $ruleMessage) {
            $failures->add(
                $this->validationFailureFactory->create($validation, $ruleMessage, $subject, (string) $ruleName),
            );
        }

        return $failures;
    }

    /**
     * @param array<string|int, mixed> $messages
     *
     * @return array<string|int, string>
     */
    private function flattenMessages(array $messages): array
    {
        unset($messages[self::COMPOSITE_MESSAGE_KEY]);

        $flattened = [];
        foreach ($messages as $name => $message) {
            $nested = match (true) {
                is_array($message) => $this->flattenMessages($message),
                is_string($message) => [$name => $message],
                default => [],
            };

            foreach ($nested as $nestedName => $nestedMessage) {
                if (is_string($nestedName)) {
                    $flattened[$nestedName] = $nestedMessage;
                } else {
                    $flattened[] = $nestedMessage;
                }
            }
        }

        return $flattened;
    }
}
