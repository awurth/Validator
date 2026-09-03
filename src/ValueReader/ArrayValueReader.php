<?php

declare(strict_types=1);

namespace Awurth\Validator\ValueReader;

final class ArrayValueReader implements ValueReaderInterface
{
    /**
     * @param array<array-key, mixed> $subject
     */
    public function getValue(mixed $subject, string $path, mixed $default = null): mixed
    {
        return \array_key_exists($path, $subject) ? $subject[$path] : $default;
    }

    public function supports(mixed $subject): bool
    {
        return \is_array($subject);
    }
}
