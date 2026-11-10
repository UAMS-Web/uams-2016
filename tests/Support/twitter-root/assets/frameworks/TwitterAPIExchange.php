<?php

/**
 * Stands in for the Twitter API wrapper the theme ships under assets/frameworks: it records the
 * settings, the query strings and the signed requests it is given, and answers each request with the next
 * scripted response. The Twitter widget requires this file from the theme root it is pointed at, so the
 * widget's own require runs. Aliased to the wrapper's name so the real wrapper is never loaded.
 */

declare(strict_types=1);

if (! class_exists('UamsTestTwitterApi', false)) {
    class UamsTestTwitterApi
    {
        /** @var list<string> */
        public static array $responses = [];

        /** @var list<list<string>> */
        public static array $calls = [];

        /** @var array<string, string> */
        public static array $settings = [];

        /** @param array<string, string> $settings */
        public function __construct(array $settings)
        {
            self::$settings = $settings;
        }

        public function setGetfield(string $query): self
        {
            self::$calls[] = ['get', $query];

            return $this;
        }

        public function buildOauth(string $url, string $method): self
        {
            self::$calls[] = ['oauth', $url, $method];

            return $this;
        }

        public function performRequest(): string
        {
            self::$calls[] = ['perform'];

            return array_shift(self::$responses) ?? '[]';
        }
    }

    class_alias(UamsTestTwitterApi::class, 'TwitterAPIExchange');
}
