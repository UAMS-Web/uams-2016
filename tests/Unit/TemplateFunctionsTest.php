<?php

/**
 * is_pdf(), uams_is_custom_post_type() and uams_get_sticky_posts(), with
 * WordPress mocked.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

namespace UAMS\Tests\Unit;

use Brain\Monkey\Functions;

final class TemplateFunctionsTest extends UnitTestCase
{
    public function test_is_pdf_is_true_for_a_pdf_attachment(): void
    {
        Functions\when('get_post_mime_type')->justReturn('application/pdf');

        $this->assertTrue(is_pdf());
    }

    public function test_is_pdf_is_false_for_any_other_mime_type(): void
    {
        Functions\when('get_post_mime_type')->justReturn('image/png');

        $this->assertFalse(is_pdf());
    }

    public function test_a_custom_post_type_is_recognised_by_its_registration(): void
    {
        Functions\when('get_post_type')->justReturn('provider');
        Functions\expect('get_post_types')
            ->once()
            ->with(['_builtin' => false])
            ->andReturn(['provider' => 'provider']);

        $this->assertTrue(uams_is_custom_post_type());
    }

    public function test_a_built_in_post_type_is_not_a_custom_post_type(): void
    {
        Functions\when('get_post_type')->justReturn('page');
        Functions\when('get_post_types')->justReturn(['provider' => 'provider']);

        $this->assertFalse(uams_is_custom_post_type());
    }

    public function test_there_is_no_custom_post_type_outside_a_post(): void
    {
        Functions\when('get_post_type')->justReturn(false);

        $this->assertFalse(uams_is_custom_post_type());
    }

    public function test_sticky_posts_are_fetched_by_their_ids(): void
    {
        Functions\when('get_option')->justReturn([7, 9]);
        Functions\when('wp_parse_args')->alias(
            static fn (array $args, array $defaults): array => array_merge($defaults, $args)
        );
        Functions\expect('get_posts')
            ->once()
            ->with(['post__in' => [7, 9]])
            ->andReturn(['first', 'second']);

        $this->assertSame(['first', 'second'], uams_get_sticky_posts());
    }

    public function test_with_no_sticky_posts_the_query_matches_nothing(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('wp_parse_args')->alias(
            static fn (array $args, array $defaults): array => array_merge($defaults, $args)
        );
        Functions\expect('get_posts')
            ->once()
            ->with(['post__in' => [0]])
            ->andReturn([]);

        $this->assertSame([], uams_get_sticky_posts());
    }
}
