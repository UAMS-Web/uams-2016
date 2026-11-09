<?php

/**
 * Floor lint: parses every PHP file the theme ships.
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

// Mutation testing cannot score this file: it runs as a script in a child process, outside the
// coverage run, so every mutant is reported uncovered. LintFloorTest pins each behavior marked
// below by running the script against fixture trees, so the markers record that, not an untested line.
$root = dirname(__DIR__);
$skip = ['vendor', 'node_modules', 'tests', '.git']; // @pest-mutate-ignore: RemoveArrayItem

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static fn (SplFileInfo $file): bool => ! $file->isDir() || ! in_array($file->getFilename(), $skip, true) // @pest-mutate-ignore: RemoveNot,BooleanOrToBooleanAnd
    )
);

$checked = 0; // @pest-mutate-ignore: IncrementInteger,DecrementInteger
$failed = [];

foreach ($iterator as $file) { // @pest-mutate-ignore: ForeachEmptyIterable
    if ($file->getExtension() !== 'php') { // @pest-mutate-ignore: NotIdenticalToIdentical,IfNegated
        continue; // @pest-mutate-ignore: ContinueToBreak
    }

    $checked++; // @pest-mutate-ignore: PostIncrementToPostDecrement

    try {
        token_get_all((string) file_get_contents($file->getPathname()), TOKEN_PARSE); // @pest-mutate-ignore: RemoveStringCast,RemoveFunctionCall
    } catch (ParseError $error) {
        $failed[] = sprintf('%s:%d: %s', $file->getPathname(), $error->getLine(), $error->getMessage());
    }
}

fwrite(STDOUT, sprintf('Parsed under PHP %s: %d files checked, %d failed.', PHP_VERSION, $checked, count($failed)).PHP_EOL); // @pest-mutate-ignore: RemoveFunctionCall,ConcatSwitchSides,ConcatRemoveRight,ConcatRemoveLeft

if ($failed !== []) { // @pest-mutate-ignore: NotIdenticalToIdentical,IfNegated
    fwrite(STDERR, implode(PHP_EOL, $failed).PHP_EOL); // @pest-mutate-ignore: RemoveFunctionCall,ConcatSwitchSides,ConcatRemoveRight,ConcatRemoveLeft
    exit(1); // @pest-mutate-ignore: IncrementInteger,DecrementInteger
}
