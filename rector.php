<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use RectorLaravel\Rector\MethodCall\AvoidNegatedCollectionFilterOrRejectRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withImportNames(importShortClasses: true, removeUnusedImports: true)
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withRules([
        AvoidNegatedCollectionFilterOrRejectRector::class,
    ])
    ->withSkip([
        __DIR__ . '/config/*.php',
        __DIR__ . '/app/Providers/*.php',
        __DIR__ . '/database/migrations',
        __DIR__ . '/database/migrations/*.php',
        __DIR__ . '/Modules/**/database/migrations',
        __DIR__ . '/app/Http/Requests/Api/BaseApiFormRequest.php',
        __DIR__ . '/app/Http/Controllers/Api/BaseApiController.php',
        __DIR__ . '/bootstrap',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/database/migrations',
        __DIR__ . '/app/Providers/ApiServiceProvider.php',
        RemoveUnusedPrivateMethodRector::class => [__DIR__ . '/app/Jobs/*.php',            __DIR__ . '/app/Listeners/*.php',            __DIR__ . '/Modules/**/Jobs/*.php',            __DIR__ . '/Modules/**/Listeners/*.php'],
        DisallowedEmptyRuleFixerRector::class,
        RemoveUselessParamTagRector::class => [
            // Keep @param tags for complex types that help with IDE support
            __DIR__ . '/app/Services',
            __DIR__ . '/app/Console/Commands',
        ],
        RemoveUselessReturnTagRector::class => [
            // Keep @return tags for better documentation
            __DIR__ . '/app/Services',
            __DIR__ . '/app/Console/Commands',
        ],
        RemoveUselessVarTagRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class => [
            // Don't add void return types to commands as they might return exit codes
            __DIR__ . '/app/Console/Commands',
        ],
        // Kizárjuk ezt a fájlt a facade-ról DI-ra konvertálásból
        __DIR__ . '/app/Filament/Resources/ApiCredentials/Schemas/ApiCredentialForm.php',
    ])
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    ])
    ->withPhpSets()
    ->withAutoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
    );
