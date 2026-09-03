<?php

declare(strict_types=1);

namespace Awurth\Validator\ValueReader;

use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

final class ValueReaderRegistry implements ValueReaderRegistryInterface
{
    /**
     * @param iterable<int, ValueReaderInterface> $valueReaders
     */
    public function __construct(private readonly iterable $valueReaders)
    {
    }

    public static function create(): self
    {
        return new self([
            new ArrayValueReader(),
            new PsrServerRequestValueReader(),
            new ObjectValueReader(),
        ]);
    }

    public function getValueReaderFor(mixed $subject): ValueReaderInterface
    {
        foreach ($this->valueReaders as $valueReader) {
            if ($valueReader->supports($subject)) {
                return $valueReader;
            }
        }

        throw new InvalidArgumentException(sprintf('No value reader configured for type "%s"', get_debug_type($subject)));
    }
}
