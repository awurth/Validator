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

use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Respect\Validation\Validatable;

/**
 * @phpstan-type ValidationOptions array{
 *     rules: Validatable,
 *     default?: mixed,
 *     message?: string|null,
 *     messages?: array<string, string>,
 * }
 */
interface ValidatorInterface
{
    /**
     * @param Validatable|ValidationOptions|array<string, Validatable|ValidationOptions> $rules    the options for a single value when $subject is a scalar, a property => rules map otherwise
     * @param array<string, string>                                                      $messages
     */
    public function validate(mixed $subject, Validatable|array $rules, array $messages = [], mixed $context = null): ValidationFailureCollectionInterface;
}
