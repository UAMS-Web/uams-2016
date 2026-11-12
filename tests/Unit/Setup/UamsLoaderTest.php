<?php

/**
 * UAMS, the object that loads the theme's back-end classes and creates the ones that exist, with WordPress
 * mocked. The theme directory it loads from is a scratch copy of the layout holding marker files, so the
 * order and the optional files are observable without loading the theme twice.
 *
 * @see setup/class.uams.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

/**
 * Write a marker file that records its own relative path in the order it is loaded.
 */
function writeLoaderMarker(string $theme, string $relative): void
{
    $path = $theme.'/'.$relative;

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, "<?php\n\$GLOBALS['uams_test_loaded'][] = '".$relative."';\n");
}

/**
 * Remove a scratch directory and everything in it.
 */
function removeLoaderTheme(string $dir): void
{
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        is_dir($dir.'/'.$entry) ? removeLoaderTheme($dir.'/'.$entry) : unlink($dir.'/'.$entry);
    }

    rmdir($dir);
}

beforeEach(function (): void {
    $GLOBALS['uams_test_loaded'] = [];
    $this->theme = sys_get_temp_dir().'/uams-loader-test-'.getmypid().'-'.bin2hex(random_bytes(4));
    mkdir($this->theme);

    foreach (UAMS_LOADER_FILES as $file) {
        writeLoaderMarker($this->theme, 'setup/'.$file);
    }

    writeLoaderMarker($this->theme, 'setup/class.uams-acf.php');

    foreach (['tile-box', 'button', 'subpage-list', 'accordion', 'maps', 'tabs', 'grid', 'menu', 'cards'] as $shortcode) {
        writeLoaderMarker($this->theme, 'setup/class.'.$shortcode.'-shortcode.php');
    }

    writeLoaderMarker($this->theme, 'inc/template-functions.php');
    when('is_multisite')->justReturn(false);
    when('get_role')->justReturn();
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('get_stylesheet_uri')->justReturn('https://site.test/theme/style.css');
    when('site_url')->justReturn('https://site.test');
    when('is_user_logged_in')->justReturn(false);
    when('get_template_directory')->justReturn($this->theme);
    stubClassExists(loaderClassesAbsent());

    foreach (UAMS_LOADER_CLASSES as [, $file]) {
        when('add_shortcode')->justReturn(true);
        require_once dirname(__DIR__, 3).'/setup/'.$file;
    }

    require_once dirname(__DIR__, 3).'/setup/class.uams.php';
});

afterEach(function (): void {
    removeLoaderTheme($this->theme);
    unset($GLOBALS['uams_test_loaded']);
});

it('requires the setup files and the template functions in order', function (): void {
    new UAMS;

    expect($GLOBALS['uams_test_loaded'])->toBe([
        ...array_map(static fn (string $file): string => 'setup/'.$file, UAMS_LOADER_FILES),
        'setup/class.uams-acf.php',
        'inc/template-functions.php',
    ]);
});

it('requires the optional files that exist after the setup files', function (): void {
    writeLoaderMarker($this->theme, 'setup/custom-post-types.php');
    writeLoaderMarker($this->theme, 'docs/class.uams-documentation.php');

    new UAMS;

    $loaded = $GLOBALS['uams_test_loaded'];
    expect(array_slice($loaded, 24, 5))->toBe([
        'setup/class.uams-page-attributes-meta-box.php',
        'setup/custom-post-types.php',
        'setup/class.uams-acf.php',
        'inc/template-functions.php',
        'docs/class.uams-documentation.php',
    ]);
});

it('includes every widget file', function (): void {
    writeLoaderMarker($this->theme, 'widgets/a.php');
    writeLoaderMarker($this->theme, 'widgets/b.php');
    writeLoaderMarker($this->theme, 'widgets/readme.txt');

    new UAMS;

    expect(array_slice($GLOBALS['uams_test_loaded'], -2))->toBe(['widgets/a.php', 'widgets/b.php']);
});

it('includes a widget file once however often the loader runs', function (): void {
    writeLoaderMarker($this->theme, 'widgets/a.php');

    new UAMS;
    new UAMS;

    /** @var list<string> $loaded */
    $loaded = $GLOBALS['uams_test_loaded'];
    $loads = array_count_values($loaded);

    expect($loads['widgets/a.php'])->toBe(1);
});

it('leaves a property empty for every class that does not exist', function (): void {
    $uams = new UAMS;

    foreach (array_keys(UAMS_LOADER_CLASSES) as $property) {
        expect($uams->{$property})->toBeNull();
    }
});

it('creates the class for a property when it exists', function (string $property, string $class): void {
    stubClassExists([$class => true] + loaderClassesAbsent());

    $uams = new UAMS;

    // The loader class names are plain strings keyed by property.
    // @phpstan-ignore argument.type
    expect($uams->{$property})->toBeInstanceOf($class);
    foreach (array_keys(UAMS_LOADER_CLASSES) as $other) {
        if ($other !== $property) {
            expect($uams->{$other})->toBeNull();
        }
    }
})->with(function () {
    foreach (UAMS_LOADER_CLASSES as $property => [$class]) {
        yield $property => [$property, $class];
    }
});
