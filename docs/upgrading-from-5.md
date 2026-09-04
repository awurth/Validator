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
