
# UAMS 2016 Theme - Back to the 5

Back to the 5 is the default theme for UAMS.edu websites. The name "Back to the 5" is an homage to the University of Washington and the UW web team whose UW 2014 "Boundless" theme is this theme's base.

This is an overview of the theme: how to install it, how to run its tests, and where its reference documentation lives.

## Table of contents
 - - -

- [Quick start](#quickstart)
- [Bugs and feature requests](#bugsandfeaturerequests)
- [Reference](#reference)

- [Creators](#creators)
- [Contributors](#contributors)
- [Credits](#credits)
- [License](#license)

## Quick Start ##
 - - -

Download the [UAMS 2016 theme](https://github.com/UAMS-Web/uams-2016/archive/master.zip) into the wp-content/themes folder of your local WordPress install. Next, log into the WordPress dashboard and go the Appearance -> Themes page. From here you can activate the UAMS 2016 theme. 

## Running the tests ##
 - - - -

The theme supports WordPress 6.8 and later (`Requires at least` in the style sheet header, and the `php-stubs/wordpress-stubs` line locked at `6.8.x`). The theme supports PHP 8.2 and later (`Requires PHP` in the style sheet header, `require.php` in `composer.json`). Development runs on PHP 8.4, pinned in `config.platform.php`. The tools in `composer.json` are for development only and are not part of the deployed theme.

There are two suites, each in its own process:

 * **Unit** runs on [Pest](https://pestphp.com) with [Brain Monkey](https://giuseppe-mazzapica.gitbook.io/brain-monkey) mocking WordPress, so it needs no database and no WordPress install.
 * **Integration** boots the theme inside a real WordPress through `wp-phpunit`, on PHPUnit 9.6 (WordPress' test suite supports PHPUnit 9 only). It has its own `tests/Integration/composer.json`.

```bash
composer install
composer test:unit
```

The integration suite needs the theme checked out inside a WordPress install and a disposable MySQL database, which is dropped and rebuilt on every run. The default name is `uams_2016_tests`; override it with `WP_TESTS_DB_NAME`, and never point it at a database anything else uses.

```bash
mysql -u root -e 'CREATE DATABASE uams_2016_tests'
composer setup:integration
composer test:integration
```

`composer lint:floor` parses every shipped PHP file with the PHP it runs under. Run it under PHP 8.2 to prove none uses newer syntax.

The code-quality tools run from Composer: `composer test:pint` (formatting, Pint with the `laravel` preset), `composer test:refactor` (Rector, the gating profile) and `composer stan` (PHPStan with the WordPress extension; the level in `phpstan.neon.dist` is the gate). `composer pint` and `composer refactor` apply their changes. Once per clone, tell `git blame` to skip the formatting commit: `git config blame.ignoreRevsFile .git-blame-ignore-revs`.

The remote gate is [`.github/workflows/ci.yml`](.github/workflows/ci.yml), which runs on every pull request and on pushes to `master`. It runs the checks above (Pint, Rector, PHPStan, the Pest unit suite on Linux, Windows and macOS), the PHP 8.2 floor lint, the Grunt build, and the repository checks for synced files, emoji and the aggregate job list. Its final `CI` job is the single status to require on a branch. The integration suite needs WordPress and a database, so it runs locally only.

The Grunt build has its own tests: after `npm ci`, `npm test` runs `tests/Build/` with Node's built-in test runner. They build the Less and JavaScript in a scratch copy and compare the result with the committed `style.css`, `style.dev.css`, `uams.css`, `js/site.dev.js`, `js/site.js` and `uams.js`; they also check the `jshint` task, the registered tasks and the `watch` targets. Run them before `grunt`, which rewrites the committed compiled files. The committed stylesheets differ from a fresh build in the ways `tests/Build/build.test.js` lists.

Build notes: `package.json` carries an `overrides` entry that pins `minimatch` to `^3.1.3` under `jshint` and `globule` (the latter reached through `grunt-contrib-watch` and `gaze`). Both ask for `~3.0.2`, which stops below the 3.1.3 release that fixes GHSA-7r86-cg39-jmmj, and the newest `jshint` keeps the same range. Remove the entry once `jshint` and `globule` (or the packages that pull it in) accept `minimatch` 3.1.3 or later, then run `npm install --package-lock-only` and confirm `npm ls minimatch` shows no copy below 3.1.3.

The same entry pins `lodash` to `^4.18.0` under `jshint`, which asks for `~4.17.21` in its newest release (2.13.6). That range stops below the 4.18.0 release that fixes GHSA-r5fr-rjxr-66jc. Remove it once `jshint` accepts `lodash` 4.18.0 or later, and confirm `npm ls lodash` shows no copy below 4.18.0.

A coverage run needs a coverage driver such as PCOV: `php -d pcov.enabled=1 vendor/bin/pest --coverage`. It measures the theme's own PHP and leaves out the vendored and unused trees (see `phpunit.xml`).

## Bugs and feature requests ##
 - - -

All bugs and feature requests can be issued at the [UAMS Web Team's GitHub account](https://github.com/UAMS-Web/uams-2016/issues) or emailed directly to the UAMS web team at [uamsonline@uams.edu](mailto:uamsonline@uams.edu).

## Reference ##
 - - -

The reference for the theme's widgets, shortcodes and features is on the [wiki](https://github.com/UAMS-Web/uams-2016/wiki): [Widgets](https://github.com/UAMS-Web/uams-2016/wiki/Widgets), [Shortcodes](https://github.com/UAMS-Web/uams-2016/wiki/Shortcodes) and [Features](https://github.com/UAMS-Web/uams-2016/wiki/Features). The pages are written in [`docs/wiki/`](docs/wiki/), and the dashboard's **Documentation** page shows the same three pages.


## UW 2014 Creators 
- - - 
[Dane Odekirk](https://github.com/daneodekirk)  
[Jon Swanson](https://github.com/swansong)  
[Kilian Frey](https://github.com/kilianf)  

## UW 2014 Contributors
- - -
[Ben Erickson](https://github.com/nambuben)

## UAMS 2016 Contributors
[Todd McKee](https://github.com/todd-uams)  
[Brent Passmore](https://github.com/bpmore) 

## Credits

Developed and maintained by UAMS Web Services.

Developers:

- Bradley Bell
- Greg Bueno
- Josh Daugherty
- Ben Erickson
- Kilian Frey
- Ryan Grant
- Todd McKee
- Dane Odekirk
- Brent Passmore
- Jon Swanson
- Nick Winkelbauer

## License
- - - 
GPL-2.0+
