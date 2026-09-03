# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

## Project

`awurth/slim-validation`: a standalone library (no framework, no app) wrapping [respect/validation](https://github.com/Respect/Validation) v2 to produce filterable validation failure objects instead of raw exceptions. Despite the name it has no Slim dependency; Slim/PSR-7 and Twig are dev-only.

The default branch is `5.x` (also the PR target). `3.x` holds the previous, unmaintained major.

## Commands

```bash
composer install
php vendor/bin/phpunit
php vendor/bin/phpunit --filter testValidateWithCustomGlobalMessages
php vendor/bin/php-cs-fixer fix
php vendor/bin/php-cs-fixer fix -v --dry-run --stop-on-violation   # what CI runs
composer validate --strict
```

CI runs those three checks on push/PR against `5.x`.

## Architecture

`Validator::validate($subject, $rules, $messages, $context)` is the single entry point and is stateless — it returns a `ValidationFailureCollectionInterface` and keeps nothing. It branches on the shape of `$rules` and `$subject`:

1. `$rules` is a `Validatable` → validate the subject as a single value.
2. `$subject` is a scalar and `$rules` is an options array → same, with the array treated as the options for one `Validation`.
3. `$subject` is an array/object/`ServerRequestInterface` → `$rules` is a `property => Validatable|options-array` map; each property is read then asserted, and the resulting collections are merged.

The pieces behind that:

- **`ValidationFactory`** turns an options array into an immutable `Validation` via a shared static Symfony `OptionsResolver`. The option schema (`rules` required, plus `default`, `message`, `messages`, `globalMessages`, `context`) lives only there — adding an option means touching that resolver and `Validation`/`ValidationInterface` together.
- **`ValueReader\ValueReaderRegistry`** picks the first `ValueReaderInterface` whose `supports()` matches: array, PSR-7 request (parsed body → query → route args → `$_FILES`), then object (Symfony PropertyAccess). Order matters — the object reader is the catch-all and must stay last.
- **`Assertion\Asserter`** runs `$rules->assert()`, catches `NestedValidationException`, and converts it into failures. A `Validation` with a non-null `message` short-circuits to exactly one failure; otherwise one failure per rule message, with message precedence: asserter-level defaults < `globalMessages` (the `validate()` `$messages` argument) < per-property `messages`.
- **`StatefulValidator`** decorates a `ValidatorInterface` and accumulates failures across calls. It exists solely because the Twig extensions need to query failures after the fact.
- **`Assertion\DataCollectorAsserter`** decorates an `AsserterInterface` and records *every* validated value (valid included) as a `ValidatedValueCollection`; that is what powers the Twig `val()` function.
- **`Twig\ValidatorExtension`** exposes `error()`/`errors()`/`has_errors()` taking callbacks. `Twig\LegacyValidatorExtension` is the deprecated v4-style key-based API, kept until v6 — do not add features to it.

## Conventions

- Every class is `final`; extensibility comes from the paired `*Interface` and constructor injection, so a new collaborator means a new interface next to it.
- Concrete classes expose a static `create()` that wires the default object graph (`Validator::create()`, `Asserter::create()`, `ValueReaderRegistry::create()`); keep those in sync when constructor signatures change.
- Every PHP file starts with `declare(strict_types=1);` followed by the package license header docblock.
- PHP CS Fixer uses `@Symfony` + `@Symfony:risky` with `native_function_invocation` for internal functions — global functions are called fully qualified (`\sprintf`, `\is_array`). Run the fixer rather than hand-formatting.
- Tests are plain PHPUnit `TestCase` classes under `Awurth\Validator\Tests\`, no mocking framework: fakes such as `tests/NoopDataCollectorAsserter.php` are written by hand.
- User-visible behaviour changes go in `CHANGELOG.md`, and the docs live in `docs/index.md` plus the README usage section (both are kept in sync by hand).
