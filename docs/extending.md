# Extending

Every class is `final`. Extension happens through the interface each one implements, injected via the constructor. `Validator::create()` wires the defaults; build the graph yourself to replace a part.

``` php
use Awurth\Validator\Assertion\Asserter;
use Awurth\Validator\ValidationFactory;
use Awurth\Validator\Validator;
use Awurth\Validator\ValueReader\ValueReaderRegistry;

$validator = new Validator(
    new ValidationFactory(),
    Asserter::create(),
    ValueReaderRegistry::create(),
);
```

## Value readers

A value reader pulls one property out of a subject. The registry asks each reader in order and uses the first whose `supports()` returns true.

``` php
use Awurth\Validator\ValueReader\ValueReaderInterface;

final class CollectionValueReader implements ValueReaderInterface
{
    public function getValue(mixed $subject, string $path, mixed $default = null): mixed
    {
        return $subject->containsKey($path) ? $subject->get($path) : $default;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof \Doctrine\Common\Collections\Collection;
    }
}
```

Order matters. The built in readers are array, PSR-7 request, then object, and the object reader is the catch all because `is_object()` matches almost anything. Register a reader for an object type *before* it.

``` php
use Awurth\Validator\ValueReader\ArrayValueReader;
use Awurth\Validator\ValueReader\ObjectValueReader;
use Awurth\Validator\ValueReader\PsrServerRequestValueReader;
use Awurth\Validator\ValueReader\ValueReaderRegistry;

$registry = new ValueReaderRegistry([
    new ArrayValueReader(),
    new PsrServerRequestValueReader(),
    new CollectionValueReader(),
    new ObjectValueReader(),
]);
```

A subject no reader supports throws `InvalidArgumentException`.

## Asserters

An asserter turns one value plus its `Validation` into failures. Decorating the default one is the usual approach, and is how `DataCollectorAsserter` works.

``` php
use Awurth\Validator\Assertion\AsserterInterface;
use Awurth\Validator\Failure\ValidationFailureCollectionInterface;
use Awurth\Validator\ValidationInterface;

final class LoggingAsserter implements AsserterInterface
{
    public function __construct(
        private readonly AsserterInterface $asserter,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function assert(mixed $subject, ValidationInterface $validation): ValidationFailureCollectionInterface
    {
        $failures = $this->asserter->assert($subject, $validation);

        foreach ($failures as $failure) {
            $this->logger->warning($failure->getMessage());
        }

        return $failures;
    }
}
```

``` php
$validator = Validator::create(new LoggingAsserter(Asserter::create(), $logger));
```

## Factories

`ValidationFactory` resolves an options array into an immutable `Validation`, and the two failure factories build the objects the asserter returns. Replace them through `ValidationFactoryInterface`, `ValidationFailureFactoryInterface` and `ValidationFailureCollectionFactoryInterface` when you need your own `Validation` or failure implementations.

The option schema lives in `ValidationFactory`'s resolver. Adding an option means changing that resolver together with `Validation` and `ValidationInterface`.
