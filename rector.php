<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withImportNames()
    ->withAttributesSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        instanceOf: true,
        phpunitNarrowAsserts: true,
        phpunitMockToStub: true,
    )
    ->withComposerBased(
        twig: true,
        phpunit: true,
    )
    ->withPhpSets()
    ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
        'allow_override_empty_method' => true,
    ])
;
