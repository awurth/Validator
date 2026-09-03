# Installation

``` bash
$ composer require awurth/slim-validation
```

## Usage

The following example shows how to validate that a string is at least 10 characters long:

``` php
use Awurth\Validator\Validator;
use Respect\Validation\Validator as V;

$validator = Validator::create();
$failures = $validator->validate('Too short', V::notBlank()->length(min: 10));

if (0 !== $failures->count()) {
    // Validation failed: display errors
    foreach ($failures as $failure) {
        echo $failure->getMessage();
    }
}
```

The `validate()` method returns a list of validation failures as an object that implements [`ValidationFailureCollectionInterface`](../src/Failure/ValidationFailureCollectionInterface.php). If you have lots of validation failures, you can filter them with a callback:

``` php
use Awurth\Validator\Failure\ValidationFailureInterface;

$failures = $validator->validate(/* ... */);
$filteredFailures = $failures->filter(static function (ValidationFailureInterface $failure, int $index): bool {
    return $failure->getRuleName() === 'notBlank';
});
```
