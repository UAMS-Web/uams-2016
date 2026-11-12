<?php

/**
 * The Twitter feed widget and its shortcode, with WordPress and the Twitter API wrapper mocked. The widget
 * file only defines its class when the account constants exist, and requires the wrapper from the theme
 * root, so the constants are defined here and the theme root is a fixture holding a stand-in wrapper.
 *
 * @see widgets/class.uams-twitter.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

/**
 * Define the account constants and load the widget file once, returning what loading registered.
 *
 * @return list<string>
 */
function loadTwitterWidget(): array
{
    static $registered = null;

    if ($registered === null) {
        foreach (['TWITTER_OAUTH_TOKEN' => 'token', 'TWITTER_OAUTH_TOKEN_SECRET' => 'token-secret', 'TWITTER_CONSUMER_KEY' => 'key', 'TWITTER_CONSUMER_SECRET' => 'secret'] as $name => $value) {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        when('get_template_directory')->justReturn(dirname(__DIR__, 2).'/Support/twitter-root');
        $registered = loadWidgetFile('class.uams-twitter.php');
    }

    return $registered;
}

/**
 * A new widget. The file loads on the first call, inside the test body, so the coverage run sees the
 * file-scope code it runs.
 */
function twitterWidget(): UAMS_Widget_Twitter
{
    loadTwitterWidget();

    return new UAMS_Widget_Twitter;
}

/**
 * Make the stand-in wrapper answer the next requests with these responses.
 *
 * @param  list<mixed>  $responses  Each is encoded as JSON, unless it is already a string.
 */
function scriptTwitterResponses(array $responses): void
{
    loadTwitterWidget();
    UamsTestTwitterApi::$calls = [];
    UamsTestTwitterApi::$responses = array_map(static fn (mixed $response): string => is_string($response) ? $response : (string) json_encode($response), $responses);
}

/**
 * Stub the transient functions: get_transient() answers with the given value, and set_transient() is
 * recorded.
 *
 * @return ArrayObject<int, array<int, mixed>>
 */
function stubTransients(mixed $cached = false): ArrayObject
{
    $set = new ArrayObject;
    when('get_transient')->alias(static function (string $name) use ($cached, $set): mixed {
        $set[] = ['get', $name];

        return $cached;
    });
    when('set_transient')->alias(static function (string $name, string $value, int $expires) use ($set): bool {
        $set[] = ['set', $name, $value, $expires];

        return true;
    });

    return $set;
}

/**
 * A tweet as the API returns it.
 *
 * @param  array<string, mixed>  $overrides
 */
function apiTweet(array $overrides = []): object
{
    return (object) ($overrides + [
        'text' => 'Hello world',
        'user' => (object) ['screen_name' => 'uams', 'profile_image_url_https' => 'https://img.test/uams.png'],
        'entities' => (object) ['user_mentions' => []],
    ]);
}

beforeEach(function (): void {
    stubShortcodeApi();
    when('add_shortcode')->justReturn(true);
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('absint')->alias(static fn (mixed $value): int => abs((int) $value));
    when('sanitize_key')->alias(static fn (string $key): string => strtolower($key));
    when('wp_json_encode')->alias(static fn (mixed $value): string => (string) json_encode($value));
    stubClassExists(['TwitterAPIExchange' => true]);
});

afterEach(function (): void {
    if (class_exists('UamsTestTwitterApi', false)) {
        UamsTestTwitterApi::$responses = [];
        UamsTestTwitterApi::$calls = [];
    }
});

it('registers the widget when the file loads', function (): void {
    expect(loadTwitterWidget())->toBe(['UAMS_Widget_Twitter']);
});

it('hands the account settings to the wrapper', function (): void {
    stubTransients();
    scriptTwitterResponses([[]]);

    (twitterWidget())->shortcode([]);

    expect(UamsTestTwitterApi::$settings)->toBe([
        'oauth_access_token' => 'token',
        'oauth_access_token_secret' => 'token-secret',
        'consumer_key' => 'key',
        'consumer_secret' => 'secret',
    ]);
});

it('describes the widget and registers the shortcode', function (): void {
    $shortcodes = recordShortcodes();

    $widget = twitterWidget();

    expect($widget->id_base)->toBe('uams-twitter-feed')
        ->and($widget->name)->toBe('UAMS Twitter Feed')
        ->and($widget->widget_options)->toBe(['description' => 'Display your latest tweets', 'classname' => 'twitter-feed-widget'])
        ->and($shortcodes->getArrayCopy())->toBe([['twitter', [$widget, 'shortcode']]]);
});

describe('update', function (): void {
    it('sanitizes the settings', function (): void {
        expect((twitterWidget())->update(['title' => 'Hi', 'name' => 'uams', 'count' => '-3'], []))
            ->toBe(['title' => 'T{Hi}', 'name' => 'T{uams}', 'count' => 3]);
    });

    it('falls back to five tweets', function (array $new): void {
        expect((twitterWidget())->update($new, []))->toBe(['title' => 'T{}', 'name' => 'T{}', 'count' => 5]);
    })->with(['missing' => [[]], 'zero' => [['count' => '0']], 'empty' => [['count' => '']]]);
});

describe('form', function (): void {
    it('prints the stored settings', function (): void {
        $html = squishWidgetHtml(render(fn () => (twitterWidget())->form(['title' => 'Hi', 'name' => 'uams', 'count' => '3'])));

        expect($html)->toContain('name="A{name-title}" type="text" value="A{A{Hi}}"')
            ->and($html)->toContain('name="A{name-name}" type="text" value="A{A{uams}}"')
            ->and($html)->toContain('name="A{name-count}" type="text" value="A{A{3}}"')
            ->and($html)->toContain('<label for="A{id-title}">Title:</label>')
            ->and($html)->toContain('<label for="A{id-name}">Twitter screen name:</label>')
            ->and($html)->toContain('<label for="A{id-count}">Number of tweets to show:</label>');
    });

    it('prints the defaults without settings', function (): void {
        $html = squishWidgetHtml(render(fn () => (twitterWidget())->form([])));

        expect($html)->toContain('name="A{name-title}" type="text" value="A{UAMS Twitter Feed}"')
            ->and($html)->toContain('name="A{name-name}" type="text" value="A{twitter}"')
            ->and($html)->toContain('name="A{name-count}" type="text" value="A{5}"');
    });
});

describe('widget', function (): void {
    it('runs the shortcode with the stored settings between the wrappers', function (): void {
        $html = render(fn () => (twitterWidget())->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], ['title' => 'Hi', 'count' => '-3', 'name' => 'uams']));

        expect($html)->toBe('<aside>D{[twitter title="A{Hi}" count="3" name="A{uams}"]}</aside>');
    });

    it('falls back to no title, five tweets and the default account', function (): void {
        expect(render(fn () => (twitterWidget())->widget([], [])))->toBe('D{[twitter title="" count="5" name="twitter"]}');
    });
});

describe('shortcode with a cached feed', function (): void {
    it('prints the cached tweets', function (): void {
        $cached = (string) json_encode([
            ['author' => 'uams', 'img' => 'https://img.test/a.png', 'text' => 'Hi', 'retweet' => '<small>RT</small>'],
            ['author' => 'bo', 'img' => 'https://img.test/b.png', 'text' => 'Yo', 'retweet' => ''],
        ]);
        $log = stubTransients($cached);

        $html = (twitterWidget())->shortcode(['title' => 'News', 'name' => 'Uams', 'count' => '2']);

        expect($html)->toBe(
            '<div class="widget uams-twitter"><h2 class="widgettitle">H{News}</h2>'
            .'<div class="twitter-feed" data-name="A{Uams}" data-count="A{2}">'
            .'<div class="tweet"><a href="//twitter.com/A{uams}"><img src="U{https://img.test/a.png}" alt="A{uams}"/></a>'
            .'<p><a href="//twitter.com/A{uams}"><span>@H{A{uams}}</span></a> Hi <small>RT</small></p></div>'
            .'<div class="tweet"><a href="//twitter.com/A{bo}"><img src="U{https://img.test/b.png}" alt="A{bo}"/></a>'
            .'<p><a href="//twitter.com/A{bo}"><span>@H{A{bo}}</span></a> Yo </p></div>'
            .'<a class="more" href="//twitter.com/A{Uams}">More</a></div></div>'
        )
            ->and($log->getArrayCopy())->toBe([['get', 'twitter-feed-uams-2']])
            ->and(UamsTestTwitterApi::$calls)->toBe([])
            ->and(shortcodeAttsNames())->toBe(['twitter']);
    });

    it('leaves out the title when there is none', function (): void {
        stubTransients((string) json_encode([['author' => 'a']]));

        expect((twitterWidget())->shortcode([]))->not->toContain('widgettitle')
            ->toContain('data-name="A{twitter}" data-count="A{5}"');
    });

    it('prints a tweet with missing fields as empty', function (): void {
        stubTransients((string) json_encode([[]]));

        expect((twitterWidget())->shortcode([]))
            ->toContain('<a href="//twitter.com/A{}"><img src="U{}" alt="A{}"/></a>')
            ->toContain('<span>@H{A{}}</span></a>  </p>');
    });

    it('prints nothing for an empty or unreadable cache', function (string $cached): void {
        stubTransients($cached);

        expect((twitterWidget())->shortcode([]))->toBe('');
    })->with(['an empty list' => ['[]'], 'not json' => ['{broken'], 'a string' => ['"text"'], 'a number' => ['5']]);
});

describe('shortcode with a live feed', function (): void {
    it('prints nothing without the wrapper', function (): void {
        loadTwitterWidget();
        stubClassExists(['TwitterAPIExchange' => false]);
        $log = stubTransients();

        expect((twitterWidget())->shortcode([]))->toBe('')
            ->and($log->getArrayCopy())->toBe([['get', 'twitter-feed-twitter-5']])
            ->and(UamsTestTwitterApi::$calls)->toBe([]);
    });

    it('requests the timeline, formats the tweets and caches them for a minute', function (): void {
        $log = stubTransients();
        scriptTwitterResponses([[apiTweet(['text' => 'Visit https://uams.edu/a?b=1 #Health @uams now'])]]);

        $html = (twitterWidget())->shortcode(['name' => 'Uams', 'count' => '3']);

        $expectedText = 'Visit <a href="https://uams.edu/a?b=1">https://uams.edu/a?b=1</a> '
            .'<a href="//twitter.com/search?q=%23Health"> H{#Health} </a> '
            .'<a href="//twitter.com/A{uams}">H{@uams}</a> now';

        expect($html)->toContain('<img src="U{https://img.test/uams.png}" alt="A{uams}"/>')
            ->and($html)->toContain($expectedText)
            ->and(UamsTestTwitterApi::$calls)->toBe([
                ['get', '?include_entities=true&include_rts=true&screen_name=Uams&count=3'],
                ['oauth', 'https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET'],
                ['perform'],
            ])
            ->and($log->getArrayCopy()[1][0])->toBe('set')
            ->and($log->getArrayCopy()[1][1])->toBe('twitter-feed-uams-3')
            ->and($log->getArrayCopy()[1][3])->toBe(60)
            ->and(json_decode($log->getArrayCopy()[1][2], true))->toBe([[
                'author' => 'uams',
                'img' => 'https://img.test/uams.png',
                'text' => $expectedText,
                'retweet' => '',
            ]]);
    });

    it('encodes the account name in the request', function (): void {
        stubTransients();
        scriptTwitterResponses([[]]);

        (twitterWidget())->shortcode(['name' => 'a b']);

        expect(UamsTestTwitterApi::$calls[0])->toBe(['get', '?include_entities=true&include_rts=true&screen_name=a%20b&count=5']);
    });

    it('takes the picture of a mentioned author from a second request', function (): void {
        $tweet = apiTweet(['entities' => (object) ['user_mentions' => [(object) ['screen_name' => 'bo']]]]);
        stubTransients();
        scriptTwitterResponses([[$tweet], (object) ['profile_image_url_https' => 'https://img.test/bo.png']]);

        $html = (twitterWidget())->shortcode([]);

        expect($html)->toContain('<img src="U{https://img.test/bo.png}" alt="A{uams}"/>')
            ->and(UamsTestTwitterApi::$calls)->toBe([
                ['get', '?include_entities=true&include_rts=true&screen_name=twitter&count=5'],
                ['oauth', 'https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET'],
                ['perform'],
                ['get', '?screen_name=uams'],
                ['oauth', 'https://api.twitter.com/1.1/users/show.json', 'GET'],
                ['perform'],
            ]);
    });

    it('keeps the picture of the tweet when the author lookup has none', function (): void {
        $tweet = apiTweet(['entities' => (object) ['user_mentions' => [(object) ['screen_name' => 'bo']]]]);
        stubTransients();
        scriptTwitterResponses([[$tweet], (object) []]);

        expect((twitterWidget())->shortcode([]))->toContain('<img src="U{https://img.test/uams.png}"');
    });

    it('credits the mentioned user of a retweet and says who retweeted', function (): void {
        $tweet = apiTweet([
            'text' => 'RT @bo: big news',
            'entities' => (object) ['user_mentions' => [(object) ['screen_name' => 'bo']]],
        ]);
        stubTransients();
        scriptTwitterResponses([[$tweet], (object) ['profile_image_url_https' => 'https://img.test/bo.png']]);

        $html = (twitterWidget())->shortcode([]);

        expect($html)->toContain('alt="A{bo}"')
            ->and($html)->toContain('<small>Retweeted by <a href="//twitter.com/A{uams}"> @H{uams}</a></small></p>')
            ->and(UamsTestTwitterApi::$calls[3])->toBe(['get', '?screen_name=bo']);
    });

    it('leaves the retweet note empty without the retweeting user', function (): void {
        $tweet = apiTweet(['text' => 'RT @bo: x', 'user' => (object) [], 'entities' => (object) ['user_mentions' => [(object) ['screen_name' => 'bo']]]]);
        stubTransients();
        scriptTwitterResponses([[$tweet], []]);

        expect((twitterWidget())->shortcode([]))->not->toContain('Retweeted by');
    });

    it('treats a tweet that merely contains RT later as an original', function (): void {
        stubTransients();
        scriptTwitterResponses([[apiTweet(['text' => 'about RT today'])]]);

        $html = (twitterWidget())->shortcode([]);

        expect($html)->toContain('alt="A{uams}"')
            ->and($html)->not->toContain('Retweeted by');
    });

    it('does not look the author up when there is no mention or no author name', function (): void {
        stubTransients();
        scriptTwitterResponses([[apiTweet(['user' => (object) ['profile_image_url_https' => 'https://img.test/x.png'], 'entities' => (object) ['user_mentions' => [(object) ['screen_name' => 'bo']]]])]]);

        $html = (twitterWidget())->shortcode([]);

        expect(UamsTestTwitterApi::$calls)->toHaveCount(3)
            ->and($html)->toContain('<img src="U{https://img.test/x.png}" alt="A{}"/>');
    });

    it('prints a tweet without text, user or entities as empty', function (): void {
        $log = stubTransients();
        scriptTwitterResponses([[(object) []]]);

        $html = (twitterWidget())->shortcode([]);

        expect($html)->toContain('<img src="U{}" alt="A{}"/>')
            ->and($log->getArrayCopy()[1][2])->toBe('[{"author":"","img":"","text":"","retweet":""}]');
    });

    it('caches an empty list when the response is not a list of tweets', function (mixed $response): void {
        $log = stubTransients();
        scriptTwitterResponses([$response]);

        expect((twitterWidget())->shortcode([]))->toBe('')
            ->and($log->getArrayCopy()[1][2])->toBe('[]');
    })->with(['an object' => [(object) ['errors' => 'x']], 'not json' => ['{broken'], 'a string' => ['"x"']]);

    it('keeps the tweets in the order the feed gave them', function (): void {
        stubTransients();
        scriptTwitterResponses([[apiTweet(['text' => 'first']), apiTweet(['text' => 'second'])]]);

        $html = (twitterWidget())->shortcode([]);

        // Both markers are in the markup, so strpos() returns positions.
        // @phpstan-ignore argument.type
        expect(strpos($html, 'first'))->toBeLessThan(strpos($html, 'second'))
            ->and(substr_count($html, 'class="tweet"'))->toBe(2);
    });

    it('formats links, hashtags and screen names in turn', function (string $text, string $expected): void {
        stubTransients();
        scriptTwitterResponses([[apiTweet(['text' => $text])]]);

        expect((twitterWidget())->shortcode([]))->toContain($expected);
    })->with([
        'a link' => ['go http://a.test/x', 'go <a href="http://a.test/x">http://a.test/x</a>'],
        'a hashtag' => ['#one', '<a href="//twitter.com/search?q=%23one"> H{#one} </a>'],
        'a screen name' => ['@one', '<a href="//twitter.com/A{one}">H{@one}</a>'],
        'plain text' => ['plain', ' plain '],
    ]);

    it('prints a tweet whose text is not a string as empty text', function (mixed $text): void {
        stubTransients();
        scriptTwitterResponses([[apiTweet(['text' => $text])]]);

        expect((twitterWidget())->shortcode([]))->toContain('</span></a>  </p>');
    })->with(['an empty string' => [''], 'zero' => ['0'], 'a number' => [5]]);
});

describe('the text callbacks', function (): void {
    it('links a hashtag to its search', function (array $match, string $expected): void {
        expect((new ReflectionMethod(twitterWidget(), 'encodeHashTag'))->invoke(twitterWidget(), $match))->toBe($expected);
    })->with([
        'a tag' => [['#one two'], '<a href="//twitter.com/search?q=%23one+two"> H{#one two} </a>'],
        'no match' => [[], '<a href="//twitter.com/search?q="> H{} </a>'],
    ]);

    it('links a screen name to its profile', function (array $match, string $expected): void {
        expect((new ReflectionMethod(twitterWidget(), 'normalizeScreenName'))->invoke(twitterWidget(), $match))->toBe($expected);
    })->with([
        'a name' => [['@one'], '<a href="//twitter.com/A{one}">H{@one}</a>'],
        'no match' => [[], '<a href="//twitter.com/A{}">H{}</a>'],
    ]);
});
