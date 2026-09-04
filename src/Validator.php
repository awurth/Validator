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

namespace Awurth\Validator;

use Awurth\Validator\Assertion\Asserter;
use Awurth\Validator\Assertion\AsserterInterface;
use Awurth\Validator\Exception\InvalidPropertyOptionsException;
use Awurth\Validator\Failure\ValidationFailureCollectionFactory;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\Failure\ValidationFailureFactory;
use Awurth\Validator\ValueReader\ValueReaderRegistry;
use Awurth\Validator\ValueReader\ValueReaderRegistryInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as RespectValidator;

use function get_debug_type;
use function is_array;
use function is_object;
use function sprintf;

/**
 * The Validator.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
final readonly class Validator implements ValidatorInterface
{
    public function __construct(
        private ValidationFactoryInterface $validationFactory,
        private AsserterInterface $asserter,
        private ValueReaderRegistryInterface $valueReaderRegistry,
    ) {
    }

    /**
     * @param array<string, string> $messages
     */
    public static function create(
        ?AsserterInterface $asserter = null,
        array $messages = [],
    ): self {
        return new self(
            new ValidationFactory(),
            $asserter ?? new Asserter(new ValidationFailureCollectionFactory(), new ValidationFailureFactory(), $messages),
            ValueReaderRegistry::create(),
        );
    }

    public function validate(mixed $subject, RespectValidator|array $rules, array $messages = [], mixed $context = null): ValidationFailureCollectionInterface
    {
        if ($rules instanceof RespectValidator) {
            return $this->asserter->assert($subject, $this->validationFactory->create([
                'rules' => $rules,
                'globalMessages' => $messages,
                'context' => $context,
            ]));
        }

        if (!$subject instanceof Request && !is_object($subject) && !is_array($subject)) {
            $rules['globalMessages'] = $messages;
            $rules['context'] = $context;

            return $this->asserter->assert($subject, $this->validationFactory->create($this->assertHasRules($rules)));
        }

        if ([] === $rules) {
            throw new InvalidArgumentException('Rules cannot be empty');
        }

        $valueReader = $this->valueReaderRegistry->getValueReaderFor($subject);

        $failures = null;
        foreach ($rules as $property => $options) {
            if ($options instanceof RespectValidator) {
                $options = ['rules' => $options];
            } elseif (!is_array($options)) {
                throw new InvalidPropertyOptionsException(sprintf('Expected an array or an instance of "%s", "%s" given', RespectValidator::class, get_debug_type($options)));
            }

            $options['globalMessages'] = $messages;
            $options['context'] = $context;

            $validation = $this->validationFactory->create($this->assertHasRules($options, $property), $property);
            $value = $valueReader->getValue($subject, $property, $validation->getDefault());

            if (!$failures instanceof ValidationFailureCollectionInterface) {
                $failures = $this->asserter->assert($value, $validation);
                continue;
            }

            $failures->addAll($this->asserter->assert($value, $validation));
        }

        return $failures;
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array{rules: RespectValidator}&array<array-key, mixed>
     */
    private function assertHasRules(array $options, ?string $property = null): array
    {
        if (!($options['rules'] ?? null) instanceof RespectValidator) {
            throw new InvalidPropertyOptionsException(sprintf('The "rules" option must be an instance of "%s"%s, "%s" given', RespectValidator::class, null === $property ? '' : sprintf(' for property "%s"', $property), get_debug_type($options['rules'] ?? null)));
        }

        return $options;
    }
}
