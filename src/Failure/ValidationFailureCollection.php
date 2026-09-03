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

namespace Awurth\Validator\Failure;

/**
 * Holds a list of validation failures.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 *
 * @implements \IteratorAggregate<int, ValidationFailureInterface>
 */
final class ValidationFailureCollection implements ValidationFailureCollectionInterface, \IteratorAggregate
{
    /**
     * @var array<int, ValidationFailureInterface>
     */
    private array $failures = [];

    /**
     * @param iterable<int, ValidationFailureInterface> $failures
     */
    public function __construct(iterable $failures = [])
    {
        $this->addAll($failures);
    }

    public function filter(callable $callback): self
    {
        $failures = new self();
        foreach ($this->failures as $index => $failure) {
            if ($callback($failure, $index)) {
                $failures->add($failure);
            }
        }

        return $failures;
    }

    public function find(callable $callback): ?ValidationFailureInterface
    {
        foreach ($this->failures as $index => $failure) {
            if ($callback($failure, $index)) {
                return $failure;
            }
        }

        return null;
    }

    public function add(ValidationFailureInterface $failure): void
    {
        $this->failures[] = $failure;
    }

    public function addAll(iterable $failures): void
    {
        foreach ($failures as $failure) {
            $this->add($failure);
        }
    }

    public function get(int $offset): ValidationFailureInterface
    {
        if (!isset($this->failures[$offset])) {
            throw new \OutOfBoundsException(\sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->failures[$offset];
    }

    public function has(int $offset): bool
    {
        return isset($this->failures[$offset]);
    }

    public function set(int $offset, ValidationFailureInterface $failure): void
    {
        $this->failures[$offset] = $failure;
    }

    public function remove(int $offset): void
    {
        unset($this->failures[$offset]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): ValidationFailureInterface
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
        return \count($this->failures);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->failures);
    }
}
