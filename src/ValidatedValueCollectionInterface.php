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
 * @extends \ArrayAccess<int, ValidatedValueInterface>
 * @extends \Traversable<int, ValidatedValueInterface>
 */
interface ValidatedValueCollectionInterface extends \ArrayAccess, \Countable, \Traversable
{
    /**
     * Adds a validated value to the list.
     */
    public function add(ValidatedValueInterface $value): void;

    /**
     * Adds a list of validated value to this list.
     *
     * @param iterable<int, ValidatedValueInterface> $values
     */
    public function addAll(iterable $values): void;

    /**
     * Gets the validated value at the given offset.
     */
    public function get(int $offset): ValidatedValueInterface;

    /**
     * Returns whether the given offset exists.
     */
    public function has(int $offset): bool;

    /**
     * Sets a validated value at the given offset.
     */
    public function set(int $offset, ValidatedValueInterface $value): void;

    /**
     * Removes the validated value at the given offset.
     */
    public function remove(int $offset): void;
}
