<?php

/**
 * The related posts widget file. Its whole body sits inside a block comment, so loading it must define
 * nothing and register nothing; this pins that, so reviving the widget is a deliberate change.
 *
 * @see widgets/class.uams-related-posts.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

it('defines no widget class and registers no hook when it loads', function (): void {
    when('add_action')->alias(static function (): void {
        throw new LogicException('The related posts file must not add a hook.');
    });

    require dirname(__DIR__, 3).'/widgets/class.uams-related-posts.php';

    expect(class_exists('UAMS_Widget_Related_Posts', false))->toBeFalse()
        ->and(class_exists('UAMS_Related_Posts', false))->toBeFalse();
});
