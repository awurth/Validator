# awurth/slim-validation 6.0 — respect/validation 3.x Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the library from `respect/validation` ^2.0 to ^3.1 and release it as 6.0, keeping the public `Awurth\Validator` API shape intact.

**Architecture:** Respect 3 replaces the exception-driven flow with a result-driven one. `Asserter` stops catching `NestedValidationException` and instead wraps the rules in a `ValidatorBuilder`, calls `validate($subject, $templates)` and reads `ResultQuery::getMessages()`. Everything that typed `Respect\Validation\Validatable` types `Respect\Validation\Validator` instead, aliased as `RespectValidator` to avoid the collision with `Awurth\Validator\Validator`. No class of this library is added, removed or renamed.

**Tech Stack:** PHP 8.5, respect/validation ^3.1, symfony/options-resolver, symfony/property-access, PHPUnit 13, PHPStan 10, PHP CS Fixer, Rector, just.

**Spec:** This document. Background facts were verified against `respect/validation` 3.1.2 (installed in a scratch project and probed) and the upstream migration guide: <https://respect-validation.readthedocs.io/en/3.0/migrating-from-v2-to-v3/>

## Background — what respect 3 changes for us

Verified against 3.1.2, not assumed:

- `Respect\Validation\Validatable` no longer exists. The rule interface is `Respect\Validation\Validator`, which only declares `evaluate(mixed $input): Result`. `ValidatorBuilder` (the `v::` facade, ex-`Validator`) implements it through `Nameable`, so both a rule chain and a bare validator instance satisfy the type.
- `assert()`/`check()` live on `ValidatorBuilder`, not on the `Validator` interface. Wrapping with `ValidatorBuilder::init($rules)` is therefore mandatory before validating; `init()` is a no-op wrapper for a chain (verified: wrapping a chain returns the same messages as calling the chain directly).
- `NestedValidationException` is gone; the unified exception is `Respect\Validation\Exceptions\ValidationException`. It is not needed here — `ValidatorBuilder::validate()` returns a `ResultQuery` without throwing, which removes the try/catch entirely.
- `ResultQuery::getMessages()` replaces `NestedValidationException::getMessages($templates)`. Custom templates now go in at validation time: `validate($input, $templates)`.
- Message keys are respect ids (`lcfirst` of the validator's short class name, with prefixes applied), not 2.x rule names. `v::length(v::greaterThanOrEqual(8))` yields the key `lengthGreaterThanOrEqual`, `v::notBlank()` yields `notBlank`, `v::between(2010, 2020)` yields `between`.
- When a node has more than one failing child, `getMessages()` prepends a synthetic `__root__` entry holding the composite message (`'"1234" must pass all the rules'`). 2.x had no equivalent, so it must be dropped to keep failure counts stable.
- `getMessages()` is typed `array<string|int, mixed>` upstream. Integer keys appear for `each()` (`0`, `1`, …), and values can be nested arrays for deeply nested trees.
- `Length` no longer takes `min`/`max`: `v::length(min: 8)` becomes `v::length(v::greaterThanOrEqual(8))`. This affects the test suite and every documentation example.
- Container: `ValidatorBuilder::init()` resolves through `ContainerRegistry`, which ships a working default (php-di). No bootstrapping is required.

## Global Constraints

- PHP floor stays `>=8.5`; do not raise or lower it.
- `respect/validation` constraint is `^3.1` — 3.1.1 fixed deep-path and `__root__` template cascading bugs that this library's nested-key handling depends on.
- Symfony constraints stay `^7.4 || ^8.0` for both `symfony/options-resolver` and `symfony/property-access`.
- Every class stays `final`; extension points are the paired `*Interface` plus constructor injection. Do not add an abstract class or a trait.
- Every PHP file starts with `declare(strict_types=1);` followed by the package license header docblock.
- Global functions are imported with `use function` and called unqualified (`array_replace`, `is_array`, `is_string`) — `.php-cs-fixer.dist.php` sets `global_namespace_import.import_functions` alongside `native_function_invocation`. Note that `AGENTS.md` claims the opposite ("called fully qualified"); the config and every file in `src/` say otherwise, so follow the code.
- PHPStan runs at level 10 over `src` and `tests`. `phpstan-baseline.neon` may only shrink. A new baseline entry is a defect being filed, not fixed.
- Zero comments in the production code unless they document a non-obvious runtime behaviour.
- User-visible behaviour changes go in `CHANGELOG.md` under `## Unreleased`.
- Work happens on the `6.x` branch, which is also the PR target.

## File Structure

| File | Change | Responsibility after the change |
| --- | --- | --- |
| `composer.json` | Modify | Requires `respect/validation: ^3.1` |
| `src/Assertion/Asserter.php` | Modify | Runs the rules through `ValidatorBuilder`, turns `ResultQuery` messages into failures, drops `__root__` |
| `src/Validation.php` | Modify | Type swap only |
| `src/ValidationInterface.php` | Modify | Type swap only |
| `src/ValidationFactory.php` | Modify | Type swap, including the `rules` allowed type |
| `src/ValidationFactoryInterface.php` | Modify | Type swap in the `@param` shape |
| `src/Validator.php` | Modify | Type swap in signature, `instanceof` checks and exception messages |
| `src/ValidatorInterface.php` | Modify | Type swap in signature and in the `ValidationOptions` PHPStan alias |
| `src/StatefulValidator.php` | Modify | Type swap only |
| `tests/ValidatorTest.php` | Modify | Rules rewritten to 3.x signatures, expected messages and rule names updated, `__root__` regression covered |
| `docs/index.md`, `docs/subjects.md`, `docs/options.md`, `docs/twig.md`, `docs/failures.md` | Modify | Examples on 3.x syntax, message keys documented as respect ids |
| `README.md` | Modify | Usage snippet on 3.x syntax |
| `docs/upgrading-from-5.md` | Create | Consumer-facing upgrade notes for 6.0 |
| `CHANGELOG.md` | Modify | `## Unreleased` entries |
| `AGENTS.md` | Modify | Architecture and CI paragraphs that describe the v2 behaviour |

---

### Task 1: Migrate the runtime to respect/validation 3

**Files:**
- Modify: `composer.json:24` (the `require` block)
- Modify: `src/Assertion/Asserter.php` (whole file)
- Modify: `src/Validation.php:16`, `src/Validation.php:30`, `src/Validation.php:40`
- Modify: `src/ValidationInterface.php:16`, `src/ValidationInterface.php:25`
- Modify: `src/ValidationFactory.php:16`, `src/ValidationFactory.php:55`
- Modify: `src/ValidationFactoryInterface.php:16`, `src/ValidationFactoryInterface.php:26`
- Modify: `src/Validator.php:26`, `src/Validator.php:61-117`
- Modify: `src/ValidatorInterface.php:17-33`
- Modify: `src/StatefulValidator.php:20`, `src/StatefulValidator.php:41`
- Test: `tests/ValidatorTest.php`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: `Asserter::assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface` (signature unchanged); the `rules` option and every `validate()` signature now accept `Respect\Validation\Validator` (imported as `RespectValidator`) instead of `Respect\Validation\Validatable`.

- [ ] **Step 1: Bump the dependency**

In `composer.json`, inside `require`:

```json
"respect/validation": "^3.1",
```

Then install it:

```bash
composer update respect/validation --with-all-dependencies
```

- [ ] **Step 2: Run the suite to see it break**

Run: `just test`
Expected: FAIL — errors mentioning `Respect\Validation\Validatable` not found (the interface no longer exists in 3.x).

- [ ] **Step 3: Rewrite the test expectations for the 3.x rule signatures**

`v::length(min: N)` no longer exists, and message keys are respect ids. Apply these edits to `tests/ValidatorTest.php`. The import stays aliased as `V`, but points at the new facade:

```php
use Respect\Validation\ValidatorBuilder as V;
```

`testRequest()`:

```php
    public function testRequest(): void
    {
        $errors = $this->validator->validate($this->request, ['username' => V::length(V::greaterThanOrEqual(6))]);

        self::assertCount(0, $errors);

        $errors = $this->validator->validate($this->request, ['username' => V::length(V::greaterThanOrEqual(8))]);

        self::assertCount(1, $errors);
    }
```

`testRequestWithRouteArguments()` — only the two assertions at the end:

```php
        self::assertCount(0, $this->validator->validate($handler->request, ['username' => V::length(V::greaterThanOrEqual(6))]));
        self::assertCount(1, $this->validator->validate($handler->request, ['username' => V::length(V::greaterThanOrEqual(8))]));
```

`testArray()` — only the second block:

```php
        $errors = $this->validator->validate($array, [
            'username' => V::notBlank()->length(V::greaterThanOrEqual(10)),
            'password' => V::notBlank()->length(V::greaterThanOrEqual(10)),
        ]);

        self::assertCount(2, $errors);
```

`testValidateWithErrors()`:

```php
    public function testValidateWithErrors(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(1, $errors);

        $error = $errors->get(0);

        self::assertSame('username', $error->getValidation()->getProperty());
        self::assertSame('lengthGreaterThanOrEqual', $error->getRuleName());
        self::assertSame('a_wurth', $error->getInvalidValue());
        self::assertSame('The length of "a_wurth" must be greater than or equal to 8', $error->getMessage());
    }
```

`testValidateWithCustomDefaultMessage()`:

```php
    public function testValidateWithCustomDefaultMessage(): void
    {
        $validator = Validator::create(messages: ['lengthGreaterThanOrEqual' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(1, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
    }
```

`testValidateWithCustomGlobalMessages()`:

```php
    public function testValidateWithCustomGlobalMessages(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
            'password' => V::length(V::greaterThanOrEqual(8)),
        ], ['lengthGreaterThanOrEqual' => 'Too short!']);

        self::assertCount(2, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
    }
```

`testValidateWithCustomDefaultAndGlobalMessages()` — this is the case that produces a `__root__` entry upstream (password fails both `length` and `alpha`), so the count of 3 is the regression guard for Step 5:

```php
    public function testValidateWithCustomDefaultAndGlobalMessages(): void
    {
        $validator = Validator::create(messages: ['lengthGreaterThanOrEqual' => 'Too short!']);
        $errors = $validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8)),
            'password' => V::length(V::greaterThanOrEqual(8))->alpha(),
        ], ['alpha' => 'Only letters are allowed']);

        self::assertCount(3, $errors);
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('Too short!', $errors->get(1)->getMessage());
        self::assertSame('Only letters are allowed', $errors->get(2)->getMessage());
        self::assertSame('alpha', $errors->get(2)->getRuleName());
    }
```

`testValidateWithCustomIndividualMessage()`:

```php
    public function testValidateWithCustomIndividualMessage(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => [
                'rules' => V::length(V::greaterThanOrEqual(8)),
                'messages' => [
                    'lengthGreaterThanOrEqual' => 'Too short!',
                ],
            ],
            'password' => V::length(V::greaterThanOrEqual(8)),
        ]);

        self::assertCount(2, $errors);
        self::assertSame('username', $errors->get(0)->getValidation()->getProperty());
        self::assertSame('Too short!', $errors->get(0)->getMessage());
        self::assertSame('password', $errors->get(1)->getValidation()->getProperty());
        self::assertSame('The length of "1234" must be greater than or equal to 8', $errors->get(1)->getMessage());
    }
```

`testValidateWithWrongCustomSingleMessageType()` and `testValidateWithCustomSingleMessage()` — replace every `V::length(8)` with `V::length(V::greaterThanOrEqual(8))` and every `'length' =>` message key with `'lengthGreaterThanOrEqual' =>`. The asserted exception message and the `'Bad username.'` / `'Too short!'` expectations do not change.

`testValue()`, `testObject()` and the three option-error tests need no change: `V::numericVal()->between(2010, 2020)` and `V::notBlank()` are valid 3.x chains.

- [ ] **Step 4: Add the `__root__` regression test**

Append to `tests/ValidatorTest.php`:

```php
    public function testValidateDoesNotReportTheCompositeFailure(): void
    {
        $errors = $this->validator->validate($this->request, [
            'username' => V::length(V::greaterThanOrEqual(8))->alnum(),
        ]);

        self::assertCount(2, $errors);
        self::assertSame('lengthGreaterThanOrEqual', $errors->get(0)->getRuleName());
        self::assertSame('alnum', $errors->get(1)->getRuleName());
    }
```

- [ ] **Step 5: Run the suite to confirm the new expectations fail**

Run: `just test`
Expected: FAIL — still on the missing `Validatable` interface, which Step 6 fixes.

- [ ] **Step 6: Swap the rule type in the seven files that reference it**

In each file replace the import

```php
use Respect\Validation\Validatable;
```

with

```php
use Respect\Validation\Validator as RespectValidator;
```

and every bare `Validatable` occurrence with `RespectValidator`. The exact sites:

- `src/Validation.php`: the `private Validatable $rules` promoted property and the `getRules(): Validatable` return type.
- `src/ValidationInterface.php`: the `getRules(): Validatable` return type.
- `src/ValidationFactory.php`: `->setAllowedTypes('rules', Validatable::class)` becomes `->setAllowedTypes('rules', RespectValidator::class)`.
- `src/ValidationFactoryInterface.php`: the `@param array{rules: Validatable}&array<array-key, mixed> $options` docblock.
- `src/StatefulValidator.php`: the `validate()` signature's `Validatable|array $rules`.
- `src/Validator.php`: the `validate()` signature, both `instanceof` checks, both `Validatable::class` interpolations in the `InvalidPropertyOptionsException` messages, and the `@return array{rules: Validatable}&array<array-key, mixed>` docblock.
- `src/ValidatorInterface.php`: the `@phpstan-type ValidationOptions` alias, the `@param` line and the `validate()` signature.

- [ ] **Step 7: Rewrite `Asserter` on top of `ResultQuery`**

Replace the whole body of `src/Assertion/Asserter.php` below the license header with:

```php
namespace Awurth\Validator\Assertion;

use Awurth\Validator\Failure\ValidationFailureCollectionFactory;
use Awurth\Validator\Failure\ValidationFailureCollectionFactoryInterface;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\Failure\ValidationFailureFactory;
use Awurth\Validator\Failure\ValidationFailureFactoryInterface;
use Awurth\Validator\ValidationInterface;
use Respect\Validation\ValidatorBuilder;

use function array_replace;
use function is_array;
use function is_string;

final readonly class Asserter implements AsserterInterface
{
    private const string COMPOSITE_MESSAGE_KEY = '__root__';

    /**
     * @param array<string, string> $messages
     */
    public function __construct(
        private ValidationFailureCollectionFactoryInterface $validationFailureCollectionFactory,
        private ValidationFailureFactoryInterface $validationFailureFactory,
        private array $messages = [],
    ) {
    }

    /**
     * @param array<string, string> $messages
     */
    public static function create(array $messages = []): self
    {
        return new self(new ValidationFailureCollectionFactory(), new ValidationFailureFactory(), $messages);
    }

    public function assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface
    {
        $failures = $this->validationFailureCollectionFactory->create();

        $templates = array_replace($this->messages, $validation->getGlobalMessages(), $validation->getMessages());
        $result = ValidatorBuilder::init($validation->getRules())->validate($subject, $templates);

        if (!$result->hasFailed()) {
            return $failures;
        }

        $message = $validation->getMessage();
        if (null !== $message) {
            $failures->add(
                $this->validationFailureFactory->create($validation, $message, $subject),
            );

            return $failures;
        }

        foreach ($this->flattenMessages($result->getMessages()) as $ruleName => $ruleMessage) {
            $failures->add(
                $this->validationFailureFactory->create($validation, $ruleMessage, $subject, $ruleName),
            );
        }

        return $failures;
    }

    /**
     * @param array<string|int, mixed> $messages
     *
     * @return array<string, string>
     */
    private function flattenMessages(array $messages): array
    {
        unset($messages[self::COMPOSITE_MESSAGE_KEY]);

        $flattened = [];
        foreach ($messages as $name => $message) {
            if (is_array($message)) {
                $flattened = [...$flattened, ...$this->flattenMessages($message)];

                continue;
            }

            if (!is_string($message)) {
                continue;
            }

            $flattened[(string) $name] = $message;
        }

        return $flattened;
    }
}
```

Two things this changes on purpose, both listed in the Background section: the `__root__` composite entry produced by respect for multi-failure nodes is dropped so failure counts stay as they were in 5.x, and integer keys coming from `each()` are cast to strings because `ValidationFailureInterface::getRuleName()` is `?string`.

- [ ] **Step 8: Run the suite**

Run: `just test`
Expected: PASS, all tests green.

- [ ] **Step 9: Run a single test to double-check message precedence**

Run: `php vendor/bin/phpunit --filter testValidateWithCustomDefaultAndGlobalMessages`
Expected: PASS — asserter defaults < global messages < per-property messages still holds through the new `$templates` argument.

- [ ] **Step 10: Commit**

```bash
git add composer.json src tests
git commit -m "feat!: migrate to respect/validation 3"
```

---

### Task 2: Get static analysis and coding standards green

**Files:**
- Modify: `phpstan-baseline.neon` (regenerate only if entries disappear)
- Modify: `src/Assertion/Asserter.php` (only if PHPStan or Rector demands it)

**Interfaces:**
- Consumes: the `Asserter` and type swap from Task 1.
- Produces: nothing new; a green `just lint`.

- [ ] **Step 1: Run static analysis**

Run: `just phpstan`
Expected: the two pre-existing `Assertion/Asserter.php` baseline entries no longer match the rewritten file, so PHPStan reports them as *ignored errors that were not matched*. That failure is the signal to regenerate, not to add anything.

- [ ] **Step 2: Regenerate the baseline**

```bash
just phpstan-baseline
```

- [ ] **Step 3: Inspect the diff before keeping it**

```bash
git diff phpstan-baseline.neon
```

Expected: entries only disappear (the two `Assertion/Asserter.php` ones). If the diff *adds* an entry, revert the baseline and fix the reported error in the code instead — a new entry is forbidden by the project's rules.

- [ ] **Step 4: Run Rector and the fixer**

```bash
just rector-fix
just cs-fix
```

- [ ] **Step 5: Run the full lint chain and the suite**

Run: `just lint && just test`
Expected: PASS on all four (cs, rector, phpstan, phpunit).

- [ ] **Step 6: Verify the dependency floor resolves**

```bash
composer update --prefer-lowest --prefer-stable && just test
```

Expected: PASS with `respect/validation` at 3.1.x. Restore the highest set afterwards with `composer update`.

- [ ] **Step 7: Commit**

```bash
git add phpstan-baseline.neon src tests
git commit -m "chore: update static analysis baseline for respect 3"
```

---

### Task 3: Update the documentation and the changelog

**Files:**
- Modify: `README.md:38-41`
- Modify: `docs/index.md:19-22`
- Modify: `docs/subjects.md:6-84`
- Modify: `docs/options.md:3-75`
- Modify: `docs/twig.md:10-11`
- Modify: `docs/failures.md` (the "Reading a failure" and "Filtering" snippets)
- Create: `docs/upgrading-from-5.md`
- Modify: `CHANGELOG.md`

**Interfaces:**
- Consumes: the final rule type name (`Respect\Validation\Validator`), the facade name (`Respect\Validation\ValidatorBuilder`) and the message-key format (respect ids such as `lengthGreaterThanOrEqual`) from Task 1.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Fix the imports and the `length()` calls in every example**

Everywhere the docs and the README write

```php
use Respect\Validation\Validator as V;
```

write

```php
use Respect\Validation\ValidatorBuilder as V;
```

and replace every `V::length(min: N)` with `V::length(V::greaterThanOrEqual(N))`. The affected lines are `README.md:38`, `README.md:41`, `docs/index.md:19`, `docs/index.md:22`, `docs/subjects.md:15`, `docs/subjects.md:19`, `docs/subjects.md:33-34`, `docs/subjects.md:84`, `docs/twig.md:11`, `docs/options.md:39`, `docs/options.md:52`.

- [ ] **Step 2: Replace `Validatable` in the prose**

`docs/subjects.md:6` becomes:

```
validate(mixed $subject, Respect\Validation\Validator|array $rules, array $messages = [], mixed $context = null): ValidationFailureCollectionInterface
```

`docs/subjects.md:11` becomes:

> Pass a `Respect\Validation\Validator` to validate the subject itself. Failures carry no property name.

`docs/options.md:12` table row becomes:

```
| `rules`   | `Respect\Validation\Validator` | Required. The rules to assert.                            |
```

`docs/options.md:17` becomes:

> A `rules` option that is not a `Respect\Validation\Validator` throws `Awurth\Validator\Exception\InvalidPropertyOptionsException`.

- [ ] **Step 3: Document the new message-key format**

In `docs/options.md`, replace the `## messages` section body with:

````markdown
Overrides individual rule messages, keyed by the respect validator id — the lowercased short class name of the rule that failed, including any prefix. `V::notBlank()` is keyed `notBlank`, `V::length(V::greaterThanOrEqual(6))` is keyed `lengthGreaterThanOrEqual`.

``` php
$failures = $validator->validate(['username' => ''], [
    'username' => [
        'rules' => V::notBlank()->length(V::greaterThanOrEqual(6)),
        'messages' => [
            'notBlank' => 'Pick a username.',
            'lengthGreaterThanOrEqual' => 'At least 6 characters.',
        ],
    ],
]);
```

Read a key you are unsure about off the failure itself: `$failure->getRuleName()`.
````

The `## Message precedence` example below it keeps the `notBlank` key, which is still the id in 3.x — no edit there.

In `docs/failures.md`, the `getRuleName()` line in "Reading a failure" keeps its `notBlank` example but gains the id definition. Replace the sentence after that snippet with:

```markdown
`getRuleName()` is the respect validator id of the failing rule — the lowercased short class name, prefixes included, so `V::length(V::greaterThanOrEqual(6))` reports `lengthGreaterThanOrEqual`. It is `null` for a property level message, which is what the `message` option produces.
```

- [ ] **Step 4: Write the consumer upgrade guide**

Create `docs/upgrading-from-5.md`:

````markdown
# Upgrading from 5.x to 6.0

6.0 is the respect/validation 3 release. The `Awurth\Validator` API is unchanged: `Validator::validate()` still returns a `ValidationFailureCollectionInterface`, the options array still has the same six keys, and no class was renamed. Everything below comes from respect itself.

## Rules

Swap the facade import:

``` diff
-use Respect\Validation\Validator as V;
+use Respect\Validation\ValidatorBuilder as V;
```

Rules that were renamed, removed or resignatured upstream must be rewritten. The full list is in the [respect migration guide](https://respect-validation.readthedocs.io/en/3.0/migrating-from-v2-to-v3/); the ones most likely to appear in code written against this library:

``` diff
-V::length(min: 6)
+V::length(V::greaterThanOrEqual(6))

-V::notEmpty()
+V::not(V::falsy())

-V::noWhitespace()
+V::not(V::spaced())

-V::optional(V::email())
+V::undefOr(V::email())

-V::nullable(V::email())
+V::nullOr(V::email())
```

## Message keys

The `messages` option, the `$messages` argument of `validate()` and the `messages` argument of `Validator::create()` are keyed by the respect validator id, which is the lowercased short class name of the failing rule. Simple rules keep their old key (`notBlank`, `alpha`, `between`, `email`). Composed rules gain a compound key:

``` diff
 'messages' => [
-    'length' => 'At least 6 characters.',
+    'lengthGreaterThanOrEqual' => 'At least 6 characters.',
 ],
```

`ValidationFailureInterface::getRuleName()` returns that same id, so an unknown key can be read off a failure at runtime.

## Custom message templates

The main placeholder is now `{{subject}}`:

``` diff
-'{{name}} is not valid'
+'{{subject}} is not valid'
```

## Requirements

respect/validation 3 requires PHP 8.5, which this library already required in 6.0.
````

- [ ] **Step 5: Add the changelog entries**

Under `## Unreleased` in `CHANGELOG.md`, keeping the two entries already there:

```markdown
* Migrated to `respect/validation` 3.0, dropped support for 2.x
* Rules are now typed `Respect\Validation\Validator` instead of the removed `Respect\Validation\Validatable`
* Custom messages and `ValidationFailureInterface::getRuleName()` are keyed by respect validator ids, so composed rules such as `length()` changed key (see `docs/upgrading-from-5.md`)
* The composite `__root__` message respect emits for a rule chain with several failures is not reported as a failure
```

- [ ] **Step 6: Check every documented snippet still parses**

```bash
composer validate --strict && just test
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add README.md docs CHANGELOG.md
git commit -m "docs: document the respect/validation 3 migration"
```

---

### Task 4: Update the agent-facing repository notes

**Files:**
- Modify: `AGENTS.md` (the Project paragraph, the CI bullet on `--prefer-lowest`, the `Assertion\Asserter` bullet, and the PHPStan baseline paragraph)

**Interfaces:**
- Consumes: the baseline count from Task 2 and the `Asserter` behaviour from Task 1.
- Produces: nothing.

- [ ] **Step 1: Fix the project description**

The first paragraph says the library wraps respect/validation "v2". Change it to "v3".

- [ ] **Step 2: Fix the CI bullet**

The Tests bullet says the `--prefer-lowest` run is the only thing exercising the `respect/validation ^2.0` floor. Change `^2.0` to `^3.1`.

- [ ] **Step 3: Fix the Asserter bullet**

Replace:

> **`Assertion\Asserter`** runs `$rules->assert()`, catches `NestedValidationException`, and converts it into failures.

with:

> **`Assertion\Asserter`** wraps the rules in a `Respect\Validation\ValidatorBuilder`, calls `validate($subject, $templates)` and converts the resulting `ResultQuery` messages into failures, dropping the `__root__` composite entry respect emits when a node has several failing children.

Keep the rest of that bullet (the `message` short-circuit and the precedence sentence) as it is — both still hold.

- [ ] **Step 4: Fix the baseline paragraph**

The paragraph naming "the 11 pre-existing errors" and their split across three files is stale once Task 2 regenerates the baseline. Read the actual numbers first:

```bash
grep -c "message:" phpstan-baseline.neon
grep -A1 "path:" phpstan-baseline.neon | sort | uniq -c
```

Then rewrite the count and the per-file split with what those commands print.

- [ ] **Step 5: Verify nothing else in the file is stale**

```bash
grep -n "Validatable\|Nested\|v2\|\^2\.0" AGENTS.md
```

Expected: no output. Fix anything that comes back.

- [ ] **Step 6: Commit**

```bash
git add AGENTS.md
git commit -m "docs: refresh the repository notes for respect 3"
```

---

## Verification

Full green gate before opening the PR against `6.x`:

```bash
composer validate --strict && just lint && just test
```

Plus the floor run:

```bash
composer update --prefer-lowest --prefer-stable && just test && composer update
```

## Out of scope

Deliberately not part of this migration, each one worth its own decision:

- Exposing respect 3's attribute validation (`v::attributes()`) through this library.
- Mapping the `message` option onto respect's own string template instead of short-circuiting to one failure.
- Surfacing the `__root__` composite message as an optional property-level failure.
- Wiring `ContainerRegistry` so consumers can inject a translator into respect's renderer.
