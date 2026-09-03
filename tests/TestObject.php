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

/** @php-cs-fixer-ignore protected_to_private */

namespace Awurth\Validator\Tests;

final class TestObject
{
    public function __construct(
        // @phpstan-ignore property.onlyWritten
        private mixed $privateProperty = null,
        protected mixed $protectedProperty = null,
        public mixed $publicProperty = null,
    ) {
    }

    public function getProtectedProperty(): mixed
    {
        return $this->protectedProperty;
    }

    public function getPublicProperty(): mixed
    {
        return $this->publicProperty;
    }

    public function setPrivateProperty(mixed $privateProperty): void
    {
        $this->privateProperty = $privateProperty;
    }

    public function setProtectedProperty(mixed $protectedProperty): void
    {
        $this->protectedProperty = $protectedProperty;
    }

    public function setPublicProperty(mixed $publicProperty): void
    {
        $this->publicProperty = $publicProperty;
    }
}
