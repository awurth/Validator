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

namespace Awurth\Validator\Twig;

use Awurth\Validator\Assertion\DataCollectorAsserterInterface;
use Awurth\Validator\Failure\ValidationFailureInterface;
use Awurth\Validator\StatefulValidatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Validator Twig Extension.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 *
 * @deprecated since v5.0, will be removed in v6.0, use {@see ValidatorExtension} instead
 */
final class LegacyValidatorExtension extends AbstractExtension
{
    /**
     * An array of names for Twig functions.
     *
     * @var array<string, string>
     */
    private array $functionNames;

    /**
     * @param array<string, string> $functionNames An array of names for Twig functions
     */
    public function __construct(
        private readonly StatefulValidatorInterface $validator,
        private readonly ?DataCollectorAsserterInterface $asserter = null,
        array $functionNames = [],
    ) {
        $this->functionNames = [
            'error' => $functionNames['error'] ?? 'error',
            'errors' => $functionNames['errors'] ?? 'errors',
            'has_error' => $functionNames['has_error'] ?? 'has_error',
            'has_errors' => $functionNames['has_errors'] ?? 'has_errors',
        ];

        if ($asserter instanceof DataCollectorAsserterInterface) {
            $this->functionNames['val'] = $functionNames['val'] ?? 'val';
        }
    }

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction($this->functionNames['error'], $this->getError(...)),
            new TwigFunction($this->functionNames['errors'], $this->getErrors(...)),
            new TwigFunction($this->functionNames['has_error'], $this->hasError(...)),
            new TwigFunction($this->functionNames['has_errors'], $this->hasErrors(...)),
        ];

        if ($this->asserter instanceof DataCollectorAsserterInterface) {
            $functions[] = new TwigFunction($this->functionNames['val'], $this->getValue(...));
        }

        return $functions;
    }

    public function getError(string $key, $index = 0, mixed $context = null): ?string
    {
        $failures = $this->validator->getFailures()->filter(static function (ValidationFailureInterface $failure) use ($key, $context): bool {
            return $failure->getValidation()->getProperty() === $key
                && (null === $context || $failure->getValidation()->getContext() === $context)
            ;
        });

        $failure = $failures->has((int)$index) ? $failures->get((int)$index) : null;

        return $failure?->getMessage();
    }

    /**
     * @return array<array-key, string>
     */
    public function getErrors(?string $key = null, mixed $context = null): array
    {
        if (null === $key) {
            $failures = $this->validator->getFailures();
        } else {
            $failures = $this->validator->getFailures()->filter(static function (ValidationFailureInterface $failure) use ($key, $context): bool {
                return $failure->getValidation()->getProperty() === $key
                    && (null === $context || $failure->getValidation()->getContext() === $context)
                ;
            });
        }

        return \array_map(
            static fn (ValidationFailureInterface $failure) => $failure->getMessage(),
            \iterator_to_array($failures),
        );
    }

    public function getValue(string $key, mixed $context = null): mixed
    {
        foreach ($this->asserter->getData() as $validatedValue) {
            if (
                $validatedValue->getValidation()->getProperty() === $key
                && (null === $context || $validatedValue->getValidation()->getContext() === $context)
            ) {
                return $validatedValue->getValue();
            }
        }

        return null;
    }

    public function hasError(string $key, mixed $context = null): bool
    {
        return null !== $this->validator->getFailures()->find(static function (ValidationFailureInterface $failure) use ($key, $context): bool {
            return $failure->getValidation()->getProperty() === $key
                && (null === $context || $failure->getValidation()->getContext() === $context)
            ;
        });
    }

    public function hasErrors(): bool
    {
        return 0 !== $this->validator->getFailures()->count();
    }
}
