# Twig integration

Templates need to query failures after validation has run, which the stateless `Validator` cannot do. `StatefulValidator` decorates it and accumulates failures across every call.

``` php
use Awurth\Validator\StatefulValidator;

$validator = StatefulValidator::create();

$validator->validate($request, ['username' => V::notBlank()]);
$validator->validate($request, ['password' => V::length(min: 8)]);

$validator->getFailures(); // failures from both calls
```

## Registering the extension

``` php
use Awurth\Validator\Twig\ValidatorExtension;

$twig->addExtension(new ValidatorExtension($validator));
```

This adds three functions:

| Function             | Returns                             |
|----------------------|-------------------------------------|
| `error(callback)`    | the first matching failure, or null |
| `errors(callback)`   | a collection of matching failures   |
| `has_errors()`       | whether any failure was recorded    |

Called without a callback, `error()` returns the first failure and `errors()` returns all of them.

``` twig
{% if has_errors() %}
    <ul>
        {% for failure in errors() %}
            <li>{{ failure.message }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

Callbacks are the same ones `filter()` and `find()` take, so filtering by property happens in the template:

``` twig
{% set usernameError = error(failure => failure.validation.property == 'username') %}

{% if usernameError %}
    <span class="error">{{ usernameError.message }}</span>
{% endif %}
```

Rename the functions if they clash with your own:

``` php
new ValidatorExtension($validator, null, [
    'error' => 'validation_error',
    'errors' => 'validation_errors',
    'has_errors' => 'has_validation_errors',
]);
```

## Redisplaying submitted values

To repopulate a form you need the values that were validated, including the valid ones. `DataCollectorAsserter` records all of them.

``` php
use Awurth\Validator\Assertion\DataCollectorAsserter;
use Awurth\Validator\StatefulValidator;
use Awurth\Validator\Twig\ValidatorExtension;

$asserter = DataCollectorAsserter::create();
$validator = StatefulValidator::create($asserter);

$twig->addExtension(new ValidatorExtension($validator, $asserter));
```

Passing the asserter adds a fourth function, `val()`, which takes a callback over the collected values:

``` twig
<input type="text" name="username" value="{{ val(value => value.validation.property == 'username') }}">
```

## Legacy extension

`Awurth\Validator\Twig\LegacyValidatorExtension` reproduces the key based API of v4. It is deprecated, will be removed in v6, and takes no new features. Use `ValidatorExtension` instead.
