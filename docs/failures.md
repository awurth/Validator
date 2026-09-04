# Working with failures

`validate()` returns a collection of `ValidationFailureInterface` objects. It is empty when the subject is valid, so there is no exception to catch.

``` php
$failures = $validator->validate($subject, $rules);

if (0 !== $failures->count()) {
    // invalid
}
```

## Reading a failure

``` php
foreach ($failures as $failure) {
    $failure->getMessage();      // 'must not be empty'
    $failure->getRuleName();     // 'notBlank', or null for a property level message
    $failure->getInvalidValue(); // the value that was rejected
    $failure->getValidation();   // the Validation, carrying the property and context
}
```

`getRuleName()` is the respect validator id of the failing rule for a flat rule chain — the short class name with a lowercase first letter, prefixes included, so `V::length(V::greaterThanOrEqual(6))` reports `lengthGreaterThanOrEqual`. It is `null` for a property level message, which is what the `message` option produces.

`each()` and `key()` report something else instead of a rule id: `each()` reports the positional index of the failing item among the *failing* children, as a string (`'0'`, `'1'`, …), not its index in the original array; `key()` reports the array key that failed (`'a'`, `'b'`, …).

The property name lives on the `Validation`, since a single value validation has none:

``` php
$failure->getValidation()->getProperty(); // 'username', or null
```

## Filtering

`filter()` returns a new collection, `find()` returns the first match or `null`. Both receive the failure and its index.

``` php
use Awurth\Validator\Failure\ValidationFailureInterface;

$blank = $failures->filter(
    static fn (ValidationFailureInterface $failure, int $index): bool => 'notBlank' === $failure->getRuleName(),
);

$first = $failures->find(
    static fn (ValidationFailureInterface $failure, int $index): bool => 'username' === $failure->getValidation()->getProperty(),
);
```

The collection has no grouping helper; loop and key by property:

``` php
$byProperty = [];
foreach ($failures as $failure) {
    $byProperty[$failure->getValidation()->getProperty()][] = $failure->getMessage();
}
```

## Collection access

The collection is `Countable`, `Traversable` and `ArrayAccess`.

``` php
$failures->count();
$failures->has(0);
$failures->get(0);       // throws OutOfBoundsException when absent
$failures[0];            // same as get()
$failures[] = $failure;  // appends
```

It is also mutable, which is what lets you build one yourself:

``` php
use Awurth\Validator\Failure\ValidationFailureCollection;

$collection = new ValidationFailureCollection();
$collection->add($failure);
$collection->addAll($failures);
$collection->remove(0);
```
