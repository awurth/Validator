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

final readonly class ValidatedValue implements ValidatedValueInterface
{
    public function __construct(private ValidationInterface $validation, private mixed $value)
    {
    }

    public function getValidation(): ValidationInterface
    {
        return $this->validation;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
