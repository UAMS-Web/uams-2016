<?php

/**
 * rector.php and rector-sweep.php: the two Rector profiles, loaded into Rector's own parameter
 * store and read back.
 *
 * The store is process-wide and the sweep shim defines a constant that cannot be undefined, so
 * `rectorProfiles()` loads the gating profile first and the sweep profile second, once, and every
 * test reads that record.
 *
 * @see rector.php
 * @see rector-sweep.php
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamFromDimFetchKeyUseRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

// Rector marks its Option constants @internal, so the parameter names are written out here.

/**
 * Apply a config file to Rector's parameter store and read back what it set.
 *
 * @return array{paths: array<string>, skip: array<string>, php: int, import: bool, docImport: bool, shortImport: bool, removeUnused: bool, sets: array<string>}
 */
function readRectorProfile(string $file): array
{
    foreach (['paths', 'skip', 'registered_rector_sets'] as $name) {
        SimpleParameterProvider::setParameter($name, []);
    }

    // Patchwork reroutes the call_user_func_array() that Nette makes to preg_match() while Rector
    // builds its config, which loses the by-reference argument and raises a warning. The call
    // still returns what the config needs (every assertion below reads the result), so only that
    // warning is set aside, and only while the config loads.
    set_error_handler(static fn (int $level, string $message): bool => str_starts_with($message, 'preg_match(): Argument #3'), E_WARNING);

    try {
        $builder = require dirname(__DIR__, 2).'/'.$file;
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore callable.nonCallable
        $builder(new RectorConfig);
    } finally {
        restore_error_handler();
    }

    // Paths use forward slashes; rule class names keep their backslashes.
    $normalize = static fn (string $entry): string => str_starts_with($entry, 'Rector\\') ? $entry : str_replace('\\', '/', $entry);

    return [
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        'paths' => array_map($normalize, SimpleParameterProvider::provideArrayParameter('paths')),
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        'skip' => array_map($normalize, SimpleParameterProvider::provideArrayParameter('skip')),
        'php' => SimpleParameterProvider::provideIntParameter('php_version_features'),
        'import' => SimpleParameterProvider::provideBoolParameter('auto_import_names'),
        'docImport' => SimpleParameterProvider::provideBoolParameter('auto_import_doc_block_names'),
        'shortImport' => SimpleParameterProvider::provideBoolParameter('import_short_classes'),
        'removeUnused' => SimpleParameterProvider::hasParameter('remove-unused-imports')
            && SimpleParameterProvider::provideBoolParameter('remove-unused-imports'),
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        'sets' => array_map(static fn (string $set): string => basename($set, '.php'), SimpleParameterProvider::provideArrayParameter('registered_rector_sets')),
    ];
}

/**
 * @return array{gating: array<string, mixed>, sweep: array<string, mixed>}
 */
function rectorProfiles(): array
{
    static $profiles = null;

    return $profiles ??= ['gating' => readRectorProfile('rector.php'), 'sweep' => readRectorProfile('rector-sweep.php')];
}

const SWEEP_ONLY_RULES = [
    ReadOnlyClassRector::class,
    ReadOnlyPropertyRector::class,
    LocallyCalledStaticMethodToNonStaticRector::class,
    RemoveUselessVarTagRector::class,
    StrictArrayParamDimFetchRector::class,
    AddParamFromDimFetchKeyUseRector::class,
    ArrayToFirstClassCallableRector::class,
];

describe('rector.php', function (): void {
    it('checks the first-party directories and the theme files at the root', function (): void {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $paths = rectorProfiles()['gating']['paths'];

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect(array_slice($paths, 0, 6))->toBe(array_map(static fn (string $dir): string => $root.'/'.$dir, ['inc', 'scripts', 'setup', 'templates', 'tests', 'widgets']))
            ->and($paths)->toContain($root.'/functions.php', $root.'/header.php', $root.'/rector.php');
    });

    it('skips the vendored and unused code, in both profiles', function (string $profile): void {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $skip = rectorProfiles()[$profile]['skip'];

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect(array_slice($skip, 0, 5))->toBe(array_map(static fn (string $path): string => $root.'/'.$path, [
            'tests/Integration/vendor',
            'setup/class-tgm-plugin-activation.php',
            'setup/cuztom',
            'setup/_cuztom',
            'widgets-unused',
        ]));
    })->with(['gating', 'sweep']);

    it('holds back the judgment-call rules from the gating profile', function (): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect(array_slice(rectorProfiles()['gating']['skip'], 5))->toBe(SWEEP_ONLY_RULES);
    });

    it('enables every rule in the sweep profile', function (): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect(array_slice(rectorProfiles()['sweep']['skip'], 5))->toBe([]);
    });

    it('enables the prepared sets, then the PHP sets and polyfills', function (string $profile): void {
        expect(rectorProfiles()[$profile]['sets'])->toBe([
            'dead-code',
            'code-quality',
            'coding-style',
            'type-declaration',
            'privatization',
            'early-return',
            'php-version-based',
            'php-polyfills',
        ]);
    })->with(['gating', 'sweep']);

    it('pins the PHP floor to 8.2', function (string $profile): void {
        expect(rectorProfiles()[$profile]['php'])->toBe(80200);
    })->with(['gating', 'sweep']);

    it('imports class names in code and docblocks, but leaves short classes and unused imports to Pint', function (string $profile): void {
        $result = rectorProfiles()[$profile];

        expect($result['import'])->toBeTrue()
            ->and($result['docImport'])->toBeTrue()
            ->and($result['shortImport'])->toBeFalse()
            ->and($result['removeUnused'])->toBeFalse();
    })->with(['gating', 'sweep']);
});

describe('rector-sweep.php', function (): void {
    it('turns the sweep profile on through the shared config', function (): void {
        rectorProfiles();

        expect(defined('UAMS_RECTOR_SWEEP') && UAMS_RECTOR_SWEEP)->toBeTrue()
            ->and(rectorProfiles()['sweep']['paths'])->toBe(rectorProfiles()['gating']['paths']);
    });
});
