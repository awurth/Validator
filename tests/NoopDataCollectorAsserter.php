<?php

declare(strict_types=1);

namespace Awurth\Validator\Tests;

use Awurth\Validator\Assertion\DataCollectorAsserterInterface;
use Awurth\Validator\Failure\ValidationFailureCollection;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\ValidatedValue;
use Awurth\Validator\ValidatedValueCollection;
use Awurth\Validator\ValidatedValueCollectionInterface;
use Awurth\Validator\ValidationInterface;

final class NoopDataCollectorAsserter implements DataCollectorAsserterInterface
{
    public function __construct(private readonly ValidatedValueCollectionInterface $data = new ValidatedValueCollection())
    {
    }

    public function assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface
    {
        $this->data->add(new ValidatedValue($validation, $subject));

        return new ValidationFailureCollection();
    }

    public function getData(): ValidatedValueCollectionInterface
    {
        return $this->data;
    }
}
