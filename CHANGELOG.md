# Changelog

## Unreleased

* Raised the minimum PHP version to 8.5
* Removed `Awurth\Validator\Twig\LegacyValidatorExtension`, deprecated since v5.0, use `Awurth\Validator\Twig\ValidatorExtension` instead

## v5.1.0

* Allow Symfony 8
* Fixed route arguments never being read from PSR-7 requests
* Fixed `$collection[] = $value` throwing instead of appending on `ValidationFailureCollection` and `ValidatedValueCollection`
* An invalid `rules` option now throws `InvalidPropertyOptionsException` instead of Symfony's `InvalidOptionsException`
* `Validator` no longer passes a discarded third argument to `AsserterInterface::assert()`
* Added array shapes and value types to the PHPDoc of the interfaces

## v5.0.1

* Allow Symfony 7

## v5.0.0

Complete rewrite

* Require PHP 8.1 or newer
* Changed namespace from `Awurth\SlimValidation` to `Awurth\Validator`
* Added support for Respect Validation v2, drop support for v1
* Removed error groups, use `context` instead for a similar feature
* Merged the `request`, `array`, `object` and `value` methods into a single `validate` method
* Made the validator stateless. The `validate` method now returns a `ValidationFailureCollection`
* Added a `StatefulValidator` to be able to use the Twig extension
* Renamed `Awurth\SlimValidation\ValidatorExtension` to `Awurth\Validator\Twig\LegacyValidatorExtension`
* Moved validation logic to an `Asserter` class
* Added a `DataCollectorAsserter` to collect all data passing through the validator, not just invalid values, as an instance of `ValidatedValueCollection`
* Added `ValueReaders` to get values from array keys, object properties or request parameters
* Made all classes final while adding extension points for everything
