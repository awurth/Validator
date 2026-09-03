# Validating subjects

`Validator::validate()` takes any subject and branches on its type. What you pass as `$rules` depends on that type.

``` text
validate(mixed $subject, Validatable|array $rules, array $messages = [], mixed $context = null): ValidationFailureCollectionInterface
```

## A single value

Pass a `Validatable` to validate the subject itself. Failures carry no property name.

``` php
use Awurth\Validator\Validator;
use Respect\Validation\Validator as V;

$validator = Validator::create();

$failures = $validator->validate('a_wurth', V::length(min: 10));
```

## Arrays

When the subject is an array, `$rules` maps each key to its rules.

``` php
$data = [
    'username' => 'a_wurth',
    'password' => '1234',
];

$failures = $validator->validate($data, [
    'username' => V::length(min: 3),
    'password' => V::length(min: 8),
]);
```

Keys absent from the subject validate as `null` unless the property declares a [`default`](options.md#default).

## Objects

Object properties are read with [Symfony PropertyAccess](https://symfony.com/doc/current/components/property_access.html), so the path may be a public property, a getter, or a nested path.

``` php
$failures = $validator->validate($user, [
    'email' => V::email(),
    'address.city' => V::notBlank(),
]);
```

An `ArrayAccess` object is read with the bracket syntax PropertyAccess uses for keys:

``` php
$failures = $validator->validate(new ArrayObject(['email' => 'not-an-email']), [
    '[email]' => V::email(),
]);
```

A path PropertyAccess cannot read yields the default rather than an error, so `email` on an `ArrayObject` silently validates `null`. Use the bracket form for key-addressable objects.

## PSR-7 requests

A `ServerRequestInterface` subject reads each property from the first source that has it:

1. parsed body
2. query parameters
3. route arguments
4. `$_FILES`

``` php
$failures = $validator->validate($request, [
    'username' => V::notBlank(),
]);
```

Route arguments are only available once the request has been through Slim's routing middleware. Outside that, the first two sources and `$_FILES` still work.

``` php
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->get('/users/{username}', function ($request, $response) use ($validator) {
    $failures = $validator->validate($request, [
        'username' => V::length(min: 6),
    ]);

    return $response;
});
```
