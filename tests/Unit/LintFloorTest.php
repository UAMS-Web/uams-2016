<?php

/**
 * scripts/lint-floor.php, run in a child process against a fixture tree.
 *
 * The script reads the tree around its own location and exits non-zero on a parse error, so it is
 * copied into a throwaway `scripts/` folder beside the fixture files and run there. A child
 * process is outside the coverage run, so the mutation run counts the script's mutants as
 * uncovered; these tests pin its behavior instead.
 *
 * @see scripts/lint-floor.php
 */

declare(strict_types=1);

/**
 * Build a fixture theme tree holding the script, and return its root.
 *
 * @param  array<string, string>  $files  Path relative to the root => contents.
 */
function lintFixture(array $files): string
{
    // The script reports paths as the OS resolves them (/private/var on macOS, long names for
    // 8.3 short ones on Windows), so the fixture root is resolved the same way.
    $temp = (string) realpath(sys_get_temp_dir());
    $root = $temp.'/lint-floor-'.bin2hex(random_bytes(6));
    mkdir($root.'/scripts', 0777, true);
    copy(dirname(__DIR__, 2).'/scripts/lint-floor.php', $root.'/scripts/lint-floor.php');

    foreach ($files as $path => $contents) {
        $directory = dirname($root.'/'.$path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($root.'/'.$path, $contents);
    }

    return $root;
}

/**
 * Run the script in a fixture tree.
 *
 * @return array{int, string, string} Exit status, stdout and stderr.
 */
function runLintFloor(string $root): array
{
    $process = proc_open([PHP_BINARY, $root.'/scripts/lint-floor.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

    assert(is_resource($process));
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
}

/**
 * Remove a fixture tree.
 */
function removeLintFixture(string $root): void
{
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($root);
}

describe('lint-floor.php', function (): void {
    it('passes a tree that parses, counting every PHP file including its own', function (): void {
        $root = lintFixture(['index.php' => "<?php\necho 1;\n", 'inc/nested/deep.php' => "<?php\n\$x = fn () => 1;\n"]);
        [$status, $stdout, $stderr] = runLintFloor($root);
        removeLintFixture($root);

        expect($stdout)->toBe(sprintf('Parsed under PHP %s: 3 files checked, 0 failed.', PHP_VERSION).PHP_EOL)
            ->and($stderr)->toBe('')
            ->and($status)->toBe(0);
    });

    it('fails a tree with a parse error, naming the file and line, and reports every failure', function (): void {
        $root = lintFixture([
            'good.php' => "<?php\necho 1;\n",
            'bad-one.php' => "<?php\n\necho (;\n",
            'inc/bad-two.php' => "<?php\n\$a = [;\n",
        ]);
        [$status, $stdout, $stderr] = runLintFloor($root);
        $files = [str_replace('\\', '/', $root).'/bad-one.php:3: ', str_replace('\\', '/', $root).'/inc/bad-two.php:2: '];
        removeLintFixture($root);

        expect($stdout)->toBe(sprintf('Parsed under PHP %s: 4 files checked, 2 failed.', PHP_VERSION).PHP_EOL)
            ->and($status)->toBe(1);

        $lines = array_map(static fn (string $line): string => str_replace('\\', '/', $line), explode(PHP_EOL, trim($stderr)));
        sort($lines);

        expect($lines)->toHaveCount(2)
            ->and($lines[0])->toStartWith($files[0])
            ->and($lines[1])->toStartWith($files[1]);
    });

    it('skips vendor, node_modules, tests and .git, and files that are not PHP', function (): void {
        $broken = "<?php\necho (;\n";
        $root = lintFixture([
            'vendor/pkg/x.php' => $broken,
            'node_modules/pkg/x.php' => $broken,
            'tests/x.php' => $broken,
            '.git/hooks/x.php' => $broken,
            'notes.txt' => $broken,
            'index.php' => "<?php\necho 1;\n",
        ]);
        [$status, $stdout] = runLintFloor($root);
        removeLintFixture($root);

        expect($stdout)->toBe(sprintf('Parsed under PHP %s: 2 files checked, 0 failed.', PHP_VERSION).PHP_EOL)->and($status)->toBe(0);
    });

    it('skips a folder only by exact name', function (): void {
        $root = lintFixture(['vendors/x.php' => "<?php\necho (;\n", 'inc/tests-helpers/x.php' => "<?php\necho 1;\n"]);
        [$status, $stdout] = runLintFloor($root);
        removeLintFixture($root);

        expect($stdout)->toBe(sprintf('Parsed under PHP %s: 3 files checked, 1 failed.', PHP_VERSION).PHP_EOL)->and($status)->toBe(1);
    });

    it('does not skip a file whose name matches a skipped folder', function (): void {
        $root = lintFixture(['vendor.php' => "<?php\necho (;\n"]);
        [$status] = runLintFloor($root);
        removeLintFixture($root);

        expect($status)->toBe(1);
    });
});
