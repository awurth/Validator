# Validator

[![CI](https://github.com/awurth/Validator/actions/workflows/ci.yml/badge.svg)](https://github.com/awurth/Validator/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/awurth/slim-validation/v/stable)](https://packagist.org/packages/awurth/slim-validation)
[![License](https://poser.pugx.org/awurth/slim-validation/license)](https://packagist.org/packages/awurth/slim-validation)

[![Total Downloads](https://poser.pugx.org/awurth/slim-validation/downloads)](https://packagist.org/packages/awurth/slim-validation)
[![Monthly Downloads](https://poser.pugx.org/awurth/slim-validation/d/monthly)](https://packagist.org/packages/awurth/slim-validation)

A wrapper around the [Respect Validation](https://github.com/Respect/Validation) PHP validation library for easier error handling and display

> This project was originally designed to be used with the Micro-Framework "Slim", hence the package name "awurth/slim-validation", but can now
  be used in any other PHP project.

## Installation

``` bash
$ composer require awurth/slim-validation
```

## Documentation

* [**6.x**](docs/index.md) (current, PHP >= 8.5)
  * [Validating subjects](docs/subjects.md)
  * [Rule options](docs/options.md)
  * [Working with failures](docs/failures.md)
  * [Twig integration](docs/twig.md)
  * [Extending](docs/extending.md)
* [**5.x**](https://github.com/awurth/Validator/tree/5.x/docs) (PHP >= 8.1)
* [**3.4**](https://github.com/awurth/Validator/tree/3.x/docs) (outdated, PHP >= 7.1)

## Usage

The following example shows how to validate that a string is at least 10 characters long:

``` php
use Awurth\Validator\Validator;
use Respect\Validation\ValidatorBuilder as V;

$validator = Validator::create();
$failures = $validator->validate('Too short', V::notBlank()->length(V::greaterThanOrEqual(10)));

if (0 !== $failures->count()) {
    // Validation failed: display errors
    foreach ($failures as $failure) {
        echo $failure->getMessage();
    }
}
```

The `validate()` method returns a list of validation failures as an object that implements [`ValidationFailureCollectionInterface`](src/Failure/ValidationFailureCollectionInterface.php). If you have lots of validation failures, you can filter them with a callback:

``` php
use Awurth\Validator\Failure\ValidationFailureInterface;

$failures = $validator->validate(/* ... */);
$filteredFailures = $failures->filter(static function (ValidationFailureInterface $failure, int $index): bool {
    return $failure->getRuleName() === 'notBlank';
});
```

## License

This package is available under the [MIT license](LICENSE).

<a href="https://www.buymeacoffee.com/awurth" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-green.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>
