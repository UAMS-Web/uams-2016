<?php

/**
 * The theme's ACF field groups, pinned as they are registered today.
 *
 * setup/class.uams-acf.php is one function of array literals handed to acf_add_local_field_group(), so
 * its behavior is the data it registers. tests/Fixtures/acf-field-groups.json holds that data as it
 * stood when the file was brought to full coverage; the registration is compared with it exactly
 * (same keys, same order, same types), so changing a field, a rule or a default fails here and the
 * fixture is updated in the same change. The remaining tests state what the data must satisfy for
 * ACF to accept it, which the fixture alone would not explain.
 *
 * @see setup/class.uams-acf.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

/**
 * Load the file once per process, recording the hooks it adds at file scope.
 *
 * A function can be declared once, so the file is required once; the recorded hooks and the
 * registered groups are kept for the tests to read.
 *
 * @return array<int, array{string, mixed}>
 */
function loadAcfFile(): array
{
    if (! isset($GLOBALS['uams_test_acf_actions'])) {
        $GLOBALS['uams_test_acf_actions'] = [];

        when('add_action')->alias(static function (string $hook, mixed $callback): true {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_acf_actions'][] = [$hook, $callback];

            return true;
        });

        require_once dirname(__DIR__, 2).'/setup/class.uams-acf.php';
    }

    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore return.type
    return $GLOBALS['uams_test_acf_actions'];
}

/**
 * Run the registration function and return the groups it passed to ACF, in order.
 *
 * @return list<array<string, mixed>>
 */
function registeredAcfGroups(): array
{
    loadAcfFile();

    $groups = [];
    when('acf_add_local_field_group')->alias(static function (array $group) use (&$groups): bool {
        $groups[] = $group;

        return true;
    });

    my_acf_add_local_field_groups();

    return $groups;
}

/**
 * Every field of a group or of a nested field, depth first.
 *
 * @param  array<int, array<string, mixed>>  $fields
 * @return list<array<string, mixed>>
 */
function flattenAcfFields(array $fields): array
{
    $flat = [];

    foreach ($fields as $field) {
        $flat[] = $field;

        foreach (['sub_fields', 'layouts'] as $nested) {
            if (isset($field[$nested]) && is_array($field[$nested])) {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore argument.type
                array_push($flat, ...flattenAcfFields($field[$nested]));
            }
        }
    }

    return $flat;
}

it('registers the field groups on acf/init', function (): void {
    expect(loadAcfFile())->toBe([['acf/init', 'my_acf_add_local_field_groups']]);
});

it('registers exactly the field groups pinned in the fixture', function (): void {
    $expected = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/acf-field-groups.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(registeredAcfGroups())->toBe($expected);
});

it('registers every group once', function (): void {
    $groups = registeredAcfGroups();

    expect($groups)->toHaveCount(7)
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        ->and(array_column($groups, 'key'))->toBe(array_unique(array_column($groups, 'key')));
});

it('gives every group and field a unique key with the prefix ACF expects', function (): void {
    $groups = registeredAcfGroups();
    $keys = [];

    foreach ($groups as $group) {
        expect($group['key'])->toStartWith('group_');

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        foreach (flattenAcfFields($group['fields']) as $field) {
            expect($field['key'])->toStartWith('field_');
            $keys[] = $field['key'];
        }
    }

    expect($keys)->not->toBeEmpty()
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        ->and($keys)->toBe(array_values(array_unique($keys)));
});
