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

namespace Awurth\Validator\Assertion;

use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\ValidatedValue;
use Awurth\Validator\ValidatedValueCollection;
use Awurth\Validator\ValidatedValueCollectionInterface;
use Awurth\Validator\ValidationInterface;

final readonly class DataCollectorAsserter implements DataCollectorAsserterInterface
{
    private ValidatedValueCollectionInterface $data;

    public function __construct(private AsserterInterface $asserter)
    {
        $this->data = new ValidatedValueCollection();
    }

    public static function create(): self
    {
        return new self(Asserter::create());
    }

    public function assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface
    {
        $this->data->add(new ValidatedValue($validation, $subject));

        return $this->asserter->assert($subject, $validation);
    }

    public function getData(): ValidatedValueCollectionInterface
    {
        return $this->data;
    }
}
