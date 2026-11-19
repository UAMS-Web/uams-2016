<?php

/**
 * docs/documentation.php, the admin Documentation page, which prints the reference pages from
 * docs/wiki/ for assets/admin/js/admin.js to render.
 *
 * @see docs/documentation.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    stubEscaping();

    $this->root = sys_get_temp_dir().'/uams-2016-documentation-'.bin2hex(random_bytes(6));
    mkdir($this->root.'/docs/wiki', 0777, true);
    file_put_contents($this->root.'/README.md', 'README body');
    file_put_contents($this->root.'/docs/wiki/Home.md', 'Home body');
    when('get_template_directory')->justReturn($this->root);
});

afterEach(function (): void {
    removeDocumentationRoot($this->root);
});

/**
 * Remove the temporary theme: README.md and the pages in docs/wiki/.
 */
function removeDocumentationRoot(string $root): void
{
    foreach (glob($root.'/docs/wiki/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($root.'/docs/wiki');
    rmdir($root.'/docs');
    unlink($root.'/README.md');
    rmdir($root);
}

/**
 * Write the named pages into the temporary theme's docs/wiki/.
 *
 * @param  array<string, string>  $pages  File name => contents.
 */
function writeWikiPages(string $root, array $pages): void
{
    foreach ($pages as $name => $contents) {
        file_put_contents($root.'/docs/wiki/'.$name, $contents);
    }
}

it('prints the three reference pages from docs/wiki/, escaped and in order', function (): void {
    writeWikiPages($this->root, ['Features.md' => 'F body', 'Shortcodes.md' => 'S body', 'Widgets.md' => 'W body']);

    $html = renderTemplate('docs/documentation.php');

    expect($html)->toBe('<div class="wrap"> <div id="uams-documentation"></div> </div> <div id="markdown" style="display:none;"> H{W body} H{S body} H{F body} </div>');
});

it('does not print the README or the other wiki pages', function (): void {
    writeWikiPages($this->root, ['Widgets.md' => 'W body']);

    $html = renderTemplate('docs/documentation.php');

    expect($html)->not->toContain('README body')
        ->and($html)->not->toContain('Home body')
        ->and($html)->toContain('H{W body}');
});

it('skips a page that is missing', function (): void {
    writeWikiPages($this->root, ['Widgets.md' => 'W body', 'Features.md' => 'F body']);

    $html = renderTemplate('docs/documentation.php');

    expect($html)->toContain('<div id="markdown" style="display:none;"> H{W body} H{F body} </div>');
});

it('separates the pages with a blank line', function (): void {
    writeWikiPages($this->root, ['Widgets.md' => 'W', 'Shortcodes.md' => 'S', 'Features.md' => 'F']);

    $html = render(static fn () => include dirname(__DIR__, 2).'/docs/documentation.php');

    expect($html)->toContain("H{W}\n\nH{S}\n\nH{F}\n\n");
});

it('prints the reference the theme ships', function (): void {
    when('get_template_directory')->justReturn(dirname(__DIR__, 2));

    $html = renderTemplate('docs/documentation.php');

    expect($html)->toContain('## Widgets ##')
        ->and($html)->toContain('### UAMS Blogroll ###')
        ->and($html)->toContain('## Shortcodes')
        ->and($html)->toContain('### Tabs ###')
        ->and($html)->toContain('## Features')
        ->and($html)->toContain('### UAMS Helper Classes ###')
        ->and($html)->not->toContain('## Running the tests');
});
