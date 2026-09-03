# Rule options

Anywhere rules are accepted you may pass an options array instead of a bare `Validatable`. These two are equivalent:

``` php
$validator->validate($subject, ['username' => V::notBlank()]);
$validator->validate($subject, ['username' => ['rules' => V::notBlank()]]);
```

| Option    | Type          | Description                                              |
|-----------|---------------|----------------------------------------------------------|
| `rules`   | `Validatable` | Required. The rules to assert.                            |
| `default` | `mixed`       | Value used when the property is missing or unreadable.    |
| `message` | `?string`     | Replaces every failure for this property with a single one. |
| `messages`| `array`       | Per rule name overrides, keyed by rule.                   |

A `rules` option that is not a `Validatable` throws `Awurth\Validator\Exception\InvalidPropertyOptionsException`.

## default

``` php
$failures = $validator->validate([], [
    'role' => [
        'rules' => V::in(['admin', 'user']),
        'default' => 'user',
    ],
]);
```

Without `default` a missing property validates as `null`.

## message

Collapses the property to exactly one failure regardless of how many rules failed.

``` php
$failures = $validator->validate(['username' => ''], [
    'username' => [
        'rules' => V::notBlank()->length(min: 6),
        'message' => 'Please choose a valid username.',
    ],
]);
```

## messages

Overrides individual rule messages, keyed by rule name.

``` php
$failures = $validator->validate(['username' => ''], [
    'username' => [
        'rules' => V::notBlank()->length(min: 6),
        'messages' => [
            'notBlank' => 'Pick a username.',
            'length' => 'At least 6 characters.',
        ],
    ],
]);
```

## Message precedence

Three levels exist, each overriding the one before it:

1. **Asserter defaults**, passed once when building the validator
2. **Global messages**, the third argument to `validate()`
3. **Per property `messages`**

``` php
use Awurth\Validator\Assertion\Asserter;

$validator = Validator::create(Asserter::create([
    'notBlank' => 'This value is required.',
]));

$failures = $validator->validate(['username' => ''], [
    'username' => ['rules' => V::notBlank()],
], messages: [
    'notBlank' => 'Overrides the asserter default.',
]);
```

A property level `message` sits outside this ladder: it wins over all three and produces one failure.

## Context

`$context` is attached to every `Validation` produced by the call and is not used by the library itself. It exists so you can tag failures and filter them later.

``` php
$failures = $validator->validate($subject, $rules, context: 'registration');

foreach ($failures as $failure) {
    $failure->getValidation()->getContext(); // 'registration'
}
```
