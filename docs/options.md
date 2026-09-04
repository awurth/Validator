# Rule options

Anywhere rules are accepted you may pass an options array instead of a bare `Respect\Validation\Validator`. These two are equivalent:

``` php
$validator->validate($subject, ['username' => V::notBlank()]);
$validator->validate($subject, ['username' => ['rules' => V::notBlank()]]);
```

| Option     | Type                           | Description                                                 |
|------------|--------------------------------|-------------------------------------------------------------|
| `rules`    | `Respect\Validation\Validator` | Required. The rules to assert.                              |
| `default`  | `mixed`                        | Value used when the property is missing or unreadable.      |
| `message`  | `?string`                      | Replaces every failure for this property with a single one. |
| `messages` | `array`                        | Per rule id overrides, keyed by respect validator id.       |

A `rules` option that is not a `Respect\Validation\Validator` throws `Awurth\Validator\Exception\InvalidPropertyOptionsException`.

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
        'rules' => V::notBlank()->length(V::greaterThanOrEqual(6)),
        'message' => 'Please choose a valid username.',
    ],
]);
```

## messages

Overrides individual rule messages, keyed by the respect validator id — the short class name of the rule that failed, with a lowercase first letter, including any prefix. `V::notBlank()` is keyed `notBlank`, `V::length(V::greaterThanOrEqual(6))` is keyed `lengthGreaterThanOrEqual`.

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

Read a key you are unsure about off the failure itself: `$failure->getRuleName()`. That only works for a flat rule chain — inside `each()` or `key()` it returns the item's position or array key only when that item failed a single rule, and a running-position number otherwise, never a message key. There, key `messages` by the wrapped rule's own id instead, e.g. `stringType` for `V::each(V::stringType())`.

## Message precedence

Three levels exist, each overriding the one before it:

1. **Asserter defaults**, passed once when building the validator
2. **Global messages**, the third argument to `validate()`
3. **Per property `messages`**

``` php
$validator = Validator::create(messages: [
    'notBlank' => 'This value is required.',
]);

$failures = $validator->validate(['username' => ''], [
    'username' => ['rules' => V::notBlank()],
], messages: [
    'notBlank' => 'Overrides the asserter default.',
]);
```

`StatefulValidator::create()` takes no messages, so build the asserter yourself there: `StatefulValidator::create(Asserter::create(['notBlank' => 'This value is required.']))`.

A property level `message` sits outside this ladder: it wins over all three and produces one failure.

## Context

`$context` is attached to every `Validation` produced by the call and is not used by the library itself. It exists so you can tag failures and filter them later.

``` php
$failures = $validator->validate($subject, $rules, context: 'registration');

foreach ($failures as $failure) {
    $failure->getValidation()->getContext(); // 'registration'
}
```
