# Upgrading from 5.x to 6.0

6.0 is the respect/validation 3 release. The `Awurth\Validator` API *shape* is unchanged: `Validator::validate()` still returns a `ValidationFailureCollectionInterface`, the options array still has the same six keys, and no class was renamed. What `each()` and `key()` actually *return* did change: 5.x reported one failure per item or key; 6.0 reports one failure per failing rule inside that item or key, keyed by position or array key instead. For `V::key('x', V::notBlank()->alnum())->key('y', V::notBlank()->alnum())` on `['x' => '', 'y' => '']`, 5.x reported 2 failures keyed `'x'`/`'y'`; 6.0 reports 4 keyed `'0'`–`'3'` (see [Working with failures](failures.md)). Everything else below comes from respect itself.

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

The `messages` option, the `$messages` argument of `validate()` and the `messages` argument of `Validator::create()` are keyed by the respect validator id, which is the short class name of the failing rule with a lowercase first letter. Simple rules keep their old key (`notBlank`, `alpha`, `between`, `email`). Composed rules gain a compound key:

``` diff
 'messages' => [
-    'length' => 'At least 6 characters.',
+    'lengthGreaterThanOrEqual' => 'At least 6 characters.',
 ],
```

The rules renamed above change key too, not just call signature:

``` diff
 'messages' => [
-    'notEmpty' => 'This value is required.',
+    'notFalsy' => 'This value is required.',
-    'noWhitespace' => 'No spaces allowed.',
+    'notSpaced' => 'No spaces allowed.',
-    'validator' => 'Not a valid email.',
+    'undefOrEmail' => 'Not a valid email.',
 ],
```

`V::optional()` and `V::nullable()` never keyed the wrapped rule's failure by its own id in 2.x — both reported it under the generic `validator` key. `V::undefOr()` and `V::nullOr()` key it as the wrapped rule's id with `undefOr`/`nullOr` glued on, so `V::nullOr(V::email())` is `nullOrEmail`, not `email` or `validator`.

`ValidationFailureInterface::getRuleName()` returns that same id for a flat rule chain, so an unknown key can be read off a failure at runtime. Inside `each()` or `key()` it only matches the item's position or array key when that item failed a single rule; once it fails more than one, the flattened list is renumbered by running position instead, same as the `key('x', ...)`/`key('y', ...)` example above.

## Custom message templates

The main placeholder is now `{{subject}}`:

``` diff
-'{{name}} is not valid'
+'{{subject}} is not valid'
```

## Requirements

respect/validation 3 requires PHP 8.5, which this library already required in 6.0.

respect/validation 3 also pulls in production dependencies 2.x had none of: `php-di/php-di` and `psr/container` for its dependency injection support, `respect/string-formatter`, `respect/stringifier`, and the `symfony/polyfill-intl-idn` and `symfony/polyfill-mbstring` polyfills. Expect a DI container to show up in your `vendor/` even if you never touch it directly.
