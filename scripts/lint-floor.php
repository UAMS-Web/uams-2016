<?php

/**
 * Floor lint: parse every PHP file the theme ships.
 *
 * Run it under the supported floor (PHP 8.2), not the development PHP, so a file
 * that uses newer syntax fails here before it fails on a site:
 *
 *     php82 scripts/lint-floor.php
 *     composer lint:floor              (runs under whatever php is on PATH)
 *
 * Each file goes through the running PHP's own parser (token_get_all with
 * TOKEN_PARSE, which throws the same ParseError `php -l` reports), in this one
 * process: spawning `php -l` per file takes minutes on Windows.
 *
 * Shipped means every .php file outside vendor/, node_modules/ and tests/.
 * Exits 1 if any file fails to parse, after reporting all of them.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$skip = ['vendor', 'node_modules', 'tests', '.git'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static fn (SplFileInfo $file): bool => ! ($file->isDir() && in_array($file->getFilename(), $skip, true))
    )
);

$checked = 0;
$failed = [];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $checked++;

    try {
        token_get_all((string) file_get_contents($file->getPathname()), TOKEN_PARSE);
    } catch (ParseError $error) {
        $failed[] = sprintf('%s:%d: %s', $file->getPathname(), $error->getLine(), $error->getMessage());
    }
}

fwrite(STDOUT, sprintf('Parsed under PHP %s: %d files checked, %d failed.', PHP_VERSION, $checked, count($failed)).PHP_EOL);

if ($failed !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failed).PHP_EOL);
    exit(1);
}
