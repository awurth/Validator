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

use ArrayAccess;
use Countable;
use Traversable;

/**
 * Holds a list of validation failures.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 *
 * @extends ArrayAccess<int, ValidationFailureInterface>
 * @extends Traversable<int, ValidationFailureInterface>
 */
interface ValidationFailureCollectionInterface extends ArrayAccess, Countable, Traversable
{
    /**
     * Adds a validation failure to the list.
     */
    public function add(ValidationFailureInterface $failure): void;

    /**
     * Adds a list of validation failures to this list.
     *
     * @param iterable<int, ValidationFailureInterface> $failures
     */
    public function addAll(iterable $failures): void;

    /**
     * Gets the validation failure at the given offset.
     */
    public function get(int $offset): ValidationFailureInterface;

    /**
     * Returns whether the given offset exists.
     */
    public function has(int $offset): bool;

    /**
     * Sets a validation failure at the given offset.
     */
    public function set(int $offset, ValidationFailureInterface $failure): void;

    /**
     * Removes the validation failure at the given offset.
     */
    public function remove(int $offset): void;
}
