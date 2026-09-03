# Validator

A wrapper around [Respect Validation](https://github.com/Respect/Validation) that returns filterable validation failure objects instead of throwing exceptions.

Despite the package name it has no dependency on Slim and works in any PHP project.

## Installation

``` bash
$ composer require awurth/slim-validation "^5.0"
```

Requires PHP 8.1 or newer.

## Quick start

``` php
use Awurth\Validator\Validator;
use Respect\Validation\Validator as V;

$validator = Validator::create();
$failures = $validator->validate('Too short', V::notBlank()->length(min: 10));

if (0 !== $failures->count()) {
    foreach ($failures as $failure) {
        echo $failure->getMessage();
    }
}
```

`validate()` never throws on invalid input. It returns a collection of failures, empty when the subject is valid.

The validator is stateless: it holds nothing between calls, so a single instance can be reused.

## Guide

* [Validating subjects](subjects.md) — values, arrays, objects and PSR-7 requests
* [Rule options](options.md) — defaults, custom messages and context
* [Working with failures](failures.md) — reading, filtering and counting failures
* [Twig integration](twig.md) — displaying errors in templates
* [Extending](extending.md) — custom value readers, asserters and factories
