<?php

/**
 * The [map] shortcode, with WordPress mocked.
 *
 * The supported building codes are a static property, which coverage attributes to no line, so the
 * mutation run reports removing any one of them as uncovered; setup/class.maps-shortcode.php marks
 * that `RemoveArrayItem` with `@pest-mutate-ignore`. It is not equivalent: the first dataset below
 * renders a map for every code, so removing one fails its case.
 *
 * @see setup/class.maps-shortcode.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.maps-shortcode.php');
    stubShortcodeApi();
});

const MAP_BUILDINGS = [
    '127', '116', '117', '118', '119', '120', '121', '122', '123', '124',
    '125', '128', '129', '126', '131', '130', '132', '133', '134', '135',
    '136', '137', '138', '139', '141', '142', '143', '144', '145', '146',
    '147', '148', '149', '150', '151', '152', '153', '154', '155', '2',
    '3', '4', '7', '6',
];

/**
 * Collapse runs of whitespace, so the comparison is of markup and not of the template's indentation.
 */
function squish(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ', $html));
}

it('registers the map shortcode', function (): void {
    $calls = recordShortcodes();
    $map = new UAMS_Map;

    expect($calls->getArrayCopy())->toBe([['map', [$map, 'map_handler']]]);
});

it('renders the map for every supported building, at the default size', function (string $building): void {
    when('sanitize_text_field')->returnArg();

    expect(squish((new UAMS_Map)->map_handler(['building' => $building])))
        ->toBe('<div class="uams-campus-map"> <iframe width="A{100%}" height="A{480px}" src="U{//maps.uams.edu/full-screen/?markerid=}'.$building.'" frameborder="0"></iframe> <a href="https://maps.uams.edu/map-mashup/?markerid='.$building.'" target="_blank" rel="noopener noreferrer">View full map</a> </div>')
        ->and(shortcodeAttsNames())->toBe(['map']);
})->with(MAP_BUILDINGS);

it('takes its size from the attributes', function (): void {
    when('sanitize_text_field')->returnArg();

    expect(squish((new UAMS_Map)->map_handler(['building' => '135', 'width' => '50%', 'height' => '300px'])))
        ->toContain('<iframe width="A{50%}" height="A{300px}"');
});

it('sanitizes the building before it checks it', function (): void {
    expect((new UAMS_Map)->map_handler(['building' => '135']))->toBe('Building "H{T{135}}" is not supported');
});

it('asks for the building when it is missing', function (mixed $atts): void {
    when('sanitize_text_field')->returnArg();

    expect((new UAMS_Map)->map_handler($atts))->toBe('required attribute "building" missing');
})->with([
    'no attributes' => [''],
    'an empty building' => [['building' => '']],
]);

it('refuses a building it does not support', function (): void {
    when('sanitize_text_field')->returnArg();

    expect((new UAMS_Map)->map_handler(['building' => '999']))->toBe('Building "H{999}" is not supported');
});

it('matches the building exactly, so a padded number is refused', function (): void {
    when('sanitize_text_field')->returnArg();

    expect((new UAMS_Map)->map_handler(['building' => '0135']))->toBe('Building "H{0135}" is not supported');
});
