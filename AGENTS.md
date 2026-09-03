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
php vendor/bin/php-cs-fixer fix --dry-run --diff   # what CI runs
php vendor/bin/phpstan analyse
php vendor/bin/phpstan analyse --generate-baseline=phpstan-baseline.neon
composer validate --strict
```

`composer.lock` is gitignored, so every install resolves fresh.

CI (`.github/workflows/ci.yml`) runs on push/PR against `5.x`, split into four jobs:

- **Validate composer.json** — `composer validate --strict`, no install.
- **Coding standards** — the PHP CS Fixer dry-run, pinned to PHP 8.1 because the fixer can emit syntax the project's minimum version cannot parse when run on a newer runtime.
- **Static analysis** — PHPStan at level 10 over `src` and `tests`, configured in `phpstan.dist.neon`. `phpVersion` is set to the 8.1–8.5 range so a single run checks every supported version rather than the one the runner happens to use.
- **Tests** — a 6-entry matrix: PHP 8.1 through 8.5 with the highest resolvable dependencies, plus PHP 8.1 with `--prefer-lowest`. The lowest run is the only thing exercising the `symfony/* ^6.0` and `respect/validation ^2.0` floors, so a change that silently needs a newer minor must raise the constraint rather than relax the job.

`fail-fast` is off, so one failing PHP version does not hide the others.

`phpstan-baseline.neon` holds the 11 pre-existing errors left from the moment PHPStan was introduced, and `phpstan.dist.neon` includes it, so the analysis is only green because of it. Regenerate it deliberately, never to make a new error disappear — a new entry in the baseline is a defect being filed, not fixed.

Those 11 sit in three files: `Twig/ValidatorExtension.php` (5), `ValidationFactory.php` (4) and `Assertion/Asserter.php` (2). Everything else is clean at level 10. Two of the Twig entries are `filter()`/`find()` being absent from `ValidationFailureCollectionInterface` while the extension calls them on that type — a real defect, not noise. The `ValidationFactory` four are `OptionsResolver::resolve()` returning `array<string, mixed>`, which level 9 will not let through to a typed constructor.

## Architecture

`Validator::validate($subject, $rules, $messages, $context)` is the single entry point and is stateless — it returns a `ValidationFailureCollectionInterface` and keeps nothing. It branches on the shape of `$rules` and `$subject`:

1. `$rules` is a `Validatable` → validate the subject as a single value.
2. `$subject` is a scalar and `$rules` is an options array → same, with the array treated as the options for one `Validation`.
3. `$subject` is an array/object/`ServerRequestInterface` → `$rules` is a `property => Validatable|options-array` map; each property is read then asserted, and the resulting collections are merged.

The pieces behind that:

- **`ValidationFactory`** turns an options array into an immutable `Validation` via a shared static Symfony `OptionsResolver`. The option schema is `rules` (required) plus `default`, `message`, `messages`, `globalMessages` and `context`. The resolver is the only thing that enforces it at runtime, but three places now describe it and must move together: that resolver, the `@param` on `ValidationFactoryInterface::create()`, and the `@phpstan-type ValidationOptions` alias on `ValidatorInterface` (which deliberately omits `globalMessages` and `context`, since `validate()` overwrites both before calling the factory).
- **`ValueReader\ValueReaderRegistry`** picks the first `ValueReaderInterface` whose `supports()` matches: array, PSR-7 request (parsed body → query → route args → `$_FILES`), then object (Symfony PropertyAccess). Order matters — the object reader is the catch-all and must stay last.
- **`Assertion\Asserter`** runs `$rules->assert()`, catches `NestedValidationException`, and converts it into failures. A `Validation` with a non-null `message` short-circuits to exactly one failure; otherwise one failure per rule message, with message precedence: asserter-level defaults < `globalMessages` (the `validate()` `$messages` argument) < per-property `messages`.
- **`StatefulValidator`** decorates a `ValidatorInterface` and accumulates failures across calls. It exists solely because the Twig extensions need to query failures after the fact.
- **`Assertion\DataCollectorAsserter`** decorates an `AsserterInterface` and records *every* validated value (valid included) as a `ValidatedValueCollection`; that is what powers the Twig `val()` function.
- **`Twig\ValidatorExtension`** exposes `error()`/`errors()`/`has_errors()` taking callbacks.

## Conventions

- Every class is `final`; extensibility comes from the paired `*Interface` and constructor injection, so a new collaborator means a new interface next to it.
- Concrete classes expose a static `create()` that wires the default object graph (`Validator::create()`, `Asserter::create()`, `ValueReaderRegistry::create()`); keep those in sync when constructor signatures change.
- Every PHP file starts with `declare(strict_types=1);` followed by the package license header docblock. The six files in `src/ValueReader/` and `tests/NoopDataCollectorAsserter.php` are missing that header and should gain it rather than being treated as precedent.
- PHP CS Fixer uses `@Symfony` + `@Symfony:risky` with `native_function_invocation` for internal functions — global functions are called fully qualified (`\sprintf`, `\is_array`). Run the fixer rather than hand-formatting. `tests/TestObject.php` carries a `// @php-cs-fixer-ignore protected_to_private` annotation: the class is `final`, so the fixer would turn its `protected` property private and destroy the fixture's reason to exist, which is covering all three visibilities for `ObjectValueReader`. Leave that annotation in place.
- Tests are plain PHPUnit `TestCase` classes under `Awurth\Validator\Tests\`, no mocking framework: fakes such as `tests/NoopDataCollectorAsserter.php` are written by hand.
- User-visible behaviour changes go in `CHANGELOG.md`, and the docs live in `docs/index.md` plus the README usage section (both are kept in sync by hand).
