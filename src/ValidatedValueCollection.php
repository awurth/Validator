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

/**
 * Holds a list of validated values.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 *
 * @implements \IteratorAggregate<int, ValidatedValueInterface>
 */
final class ValidatedValueCollection implements ValidatedValueCollectionInterface, \IteratorAggregate
{
    /**
     * @var array<int, ValidatedValueInterface>
     */
    private array $values = [];

    /**
     * @param iterable<int, ValidatedValueInterface> $values
     */
    public function __construct(iterable $values = [])
    {
        $this->addAll($values);
    }

    public function add(ValidatedValueInterface $value): void
    {
        $this->values[] = $value;
    }

    public function addAll(iterable $values): void
    {
        foreach ($values as $value) {
            $this->add($value);
        }
    }

    public function get(int $offset): ValidatedValueInterface
    {
        if (!isset($this->values[$offset])) {
            throw new \OutOfBoundsException(\sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->values[$offset];
    }

    public function has(int $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function set(int $offset, ValidatedValueInterface $value): void
    {
        $this->values[$offset] = $value;
    }

    public function remove(int $offset): void
    {
        unset($this->values[$offset]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): ValidatedValueInterface
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            $this->add($value);

            return;
        }

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function count(): int
    {
        return \count($this->values);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->values);
    }
}
