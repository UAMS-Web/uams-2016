
# UAMS 2016 Theme - Back to the 5

Back to the 5 is the default theme for UAMS.edu websites. The name "Back to the 5" is an homage to the University of Washington and the UW web team whose UW 2014 "Boundless" theme is this theme's base.

This is an overview of the custom widgets, plugins and various features it includes.

## Table of contents
 - - -

- [Quick start](#quickstart)
- [Bugs and feature requests](#bugsandfeaturerequests)
- [Widgets](#widgets)
>  - [UAMS Blogroll](#uamsblogroll)
>  - [UAMS Recent Posts](#uamsrecentposts)
>  - [UAMS RSS](#uamsrss)
>  - [UAMS Single Image ](#uamssingleimage)
>  - [UAMS Top Posts](#uamstopposts)
>  - [UAMS Contact Card](#uamscontactcard)
>  - [UAMS Image Card](#uamsimagecard)
>  - [UAMS Campus Map](#uamscampusmap)
>  - [UAMS Social Icons](#uamssocialicons)

- [Shortcodes](#shortcodes)
>  - [Button](#button)
>  - [Blogroll](#blogroll)
>  - [iFrame](#iframe)
>  - [Intro](#intro)
>  - [Map](#map)
>  - [RSS](#rss)
>  - [Tile box](#tilebox)
>  - [YouTube](#youtube)
>  - [Subpage List](#subpagelist)
>  - [Accordion](#accordion)
>  - [Tabs](#tabs)
<!--   - [Custom Menu](#custommenu) -->
- [Features](#features)
>  - [UAMS Widget Visibility](#uamswidgetvisiblity)
>  - [UAMS Media Credit](#uamsmediacredit)
>  - [UAMS Helper Classes](#uamshelperclasses)

- [Creators](#creators)
- [Contributors](#contributors)
- [License](#license)

## Quick Start ##
 - - -

Download the [UAMS 2016 theme](https://github.com/UAMS-Web/uams-2016/archive/master.zip) into the wp-content/themes folder of your local WordPress install. Next, log into the WordPress dashboard and go the Appearance -> Themes page. From here you can activate the UAMS 2016 theme. 

## Running the tests ##
 - - - -

The theme supports PHP 8.2 and later (`Requires PHP` in the style sheet header, `require.php` in `composer.json`). Development runs on PHP 8.4, pinned in `config.platform.php`. The tools in `composer.json` are for development only and are not part of the deployed theme.

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

The remote gate is [`.github/workflows/ci.yml`](.github/workflows/ci.yml), which runs on every pull request and on pushes to `dev`, `staging` and `master`. It runs the checks above (Pint, Rector, PHPStan, the Pest unit suite on Linux, Windows and macOS), the PHP 8.2 floor lint, the Grunt build, and the repository checks for synced files, emoji and the aggregate job list. Its final `CI` job is the single status to require on a branch. The integration suite needs WordPress and a database, so it runs locally only.

The Grunt build has its own tests: after `npm ci`, `npm test` runs `tests/Build/` with Node's built-in test runner. They build the Less and JavaScript in a scratch copy and compare the result with the committed `style.css`, `style.dev.css`, `uams.css`, `js/site.dev.js`, `js/site.js` and `uams.js`; they also check the `jshint` task, the registered tasks and the `watch` targets. Run them before `grunt`, which rewrites the committed compiled files. The committed stylesheets differ from a fresh build in the ways `tests/Build/build.test.js` lists.

Build notes: `package.json` carries an `overrides` entry that pins `minimatch` to `^3.1.3` under `jshint` and `globule` (the latter reached through `grunt-contrib-watch` and `gaze`). Both ask for `~3.0.2`, which stops below the 3.1.3 release that fixes GHSA-7r86-cg39-jmmj, and the newest `jshint` keeps the same range. Remove the entry once `jshint` and `globule` (or the packages that pull it in) accept `minimatch` 3.1.3 or later, then run `npm install --package-lock-only` and confirm `npm ls minimatch` shows no copy below 3.1.3.

The same entry pins `lodash` to `^4.18.0` under `jshint`, which asks for `~4.17.21` in its newest release (2.13.6). That range stops below the 4.18.0 release that fixes GHSA-r5fr-rjxr-66jc. Remove it once `jshint` accepts `lodash` 4.18.0 or later, and confirm `npm ls lodash` shows no copy below 4.18.0.

A coverage run needs a coverage driver such as PCOV: `php -d pcov.enabled=1 vendor/bin/pest --coverage`. It measures the theme's own PHP and leaves out the vendored and unused trees (see `phpunit.xml`).

## Bugs and feature requests ##
 - - -

All bugs and feature requests can be issued at the [UAMS Web Team's GitHub account](https://github.com/UAMS-Web/uams-2016/issues) or emailed directly to the UAMS web team at [uamsonline@uams.edu](mailto:uamsonline@uams.edu).

## Widgets ##
 - - -

### UAMS Blogroll ###

> Display the most recent posts on your blog.  

> Options:  

> - **Title** : The title of the widget (*Default: Blogroll*)   
> - **Number of posts to display** : The number of post titles to show in the blogroll (*Default: 2*) 
> - **Style** : Choose one of three styles (*Default: default*)    


### UAMS Recent Posts ###

>  Similar to the default WordPress widget Recent Posts but with different options and layout.

> Options:  

> - **Title** : The title of the widget (*Default: None*)   
> - **Number of posts to display** : The number of posts to show (*Default: 1*)
> - **Display more link** : Display an anchor tag that links to the blogroll page (*Default: false*)

### UAMS RSS ###

> Similar to the WordPress RSS widget except with a branded layout that displays RSS images.

> Options:  

> - __Title__ : The title of the widget (_Default: None_)   
> - __Blurb__ : A small blurb that is shown before the RSS feed (_Default: None_)    
> - __RSS URL__ : The URL of the RSS feed to display (_Default: None_)    
> - __Number of items to display__ : The number of items in the RSS feed to display (_Default: 10_)    

### UAMS Single Image ###

> Displays a single image with a blurb of text below it.

> Options:  

> - __Title__ : The title of the widget (_Default: Image Widget_)   
> - __Select an image__ : Select an  image from the WordPress  media library (_Default: None_)    
> - __Featured text__ : A small blurb that is shown below the image (_Default: None_)    
> - __Link__ : A URL for the More link text (_Default: None_)    
> - __More link__ : The text to display in the more link (_Default: Read more_)    

### UAMS Contact Card ###

> Displays a multiple list of contacts for the group/department.

> Options:  

> - __Title__ : The title of the widget (_Default: Contact us_)   
> - __Name__ : The person's name (_Default: None_)   
> - __Title__ : The person's job title (_Default: None_) 
> - __Phone number__ : The person's phone number (_Default: None_) 
> - __Email__ : The person's email (_Default: None_)   


### UAMS Image Card ###

> Displays one of three styles of branded card. Both text and image can be customized.

> Options:  

> - __Title__ : The title of the widget (_Default: Image Widget_)   
> - __Select an image__ : Select an image from the WordPress  media library (_Default: None_)    
> - __Featured text__ : A small blurb that is shown below or on top of the image (_Default: None_)    
> - __Link__ : A URL for the More link text (_Default: None_)    
> - __More link__ : The text to display in the more link (_Default: Read more_)  
> - __Card style__ : Choose one of three styles (_Default: None_)  

### UAMS Campus Map ###

> Display a custom map with building selected.  

> Options:  

> - __Title__ : The title of the widget (_Default: Blogroll_)   
> - __Building Code__ : Dropdown of the building names (_Default: none_) 

### UAMS Social Icons ###

> Display a list of social icons.  

> Options:  

> - __Title__ : The title of the widget (_Default: Connect with us:_)   
> - __Facebook URI__ : URL of Facebook account (_Default: none_)
> - __Twitter URI__ : URL of Twitter account (_Default: none_)
> - __Instagram URI__ : URL of Instagram account (_Default: none_)
> - __YouTube URI__ : URL of YouTube account (_Default: none_)
> - __LinkedIn URI__ : URL of LinkedIn account (_Default: none_)
> - __Pinterest URI__ : URL of Pinterest account (_Default: none_)


## Shortcodes
 - - -

### Button ###

>  Displays a branded call to action button. [See some examples](http://uamsonlinedev.com/uams-button-examples/). 

> Attributes:  

> - __color__ : The color of the button. Options: __red__, __gray__, __blue__, or __green__ (_Default: none_)
> - __type__: Adjusts the image of the button. Options: __plus__, __go__, __external__, __play__, __pdf__ (_Default: go_)   
> - __size__: Adjusts the size of the button. Options: __small__, __large__ (_Default: None_)
> - __url__: The URL where the button links to (_Default: None_)

> Examples:   
```
  [button color=red url="http://uams.edu"]Button Text[/button]
  
  [button color="red" type=plus size="small" url="http://uams.edu"]Button Text[/button]
```

### Blogroll ###

>  This is a shortcode that wraps the WordPress [get\_posts](https://codex.wordpress.org/Template_Tags/get_posts) function and templates out a blogroll. Any parameter you can pass to `get_posts` will be understood along with the following. 

> Attributes:  

> -  __excerpt__ : Choose whether to show the excerpt in the blogroll. Options: __show__, __hide__. (_Default: hide_)
> - __trim__ : Whether or not to trim the words via WordPress [wp\_trim\_words](https://codex.wordpress.org/Function_Reference/wp_trim_words) function. Options: __true__, __false__. (_Default: false_)
> - __image__:  Choose whether to show the featured image thumbnail. Options: __show__, __hide__. (_Default: hide_) _\[Ignored by card style\]_
> - __author__: Choose whether to show the author. Options: __show__, __hide__. (_Default: show_)
> - __date__:  Choose whether to show the publish date. Options: __show__, __hide__. (_Default: show_)
> - __titletag__:  The html element for the post titles. (_Default: h2_)
> - __post\_type__:  The post type to look for.(_Default: post_)
> - __number__:  The maximum number of results to return (_Default: 5_)
> - ~~__mini__:  Use the miniture template instead of the default one. (_Default: false_)~~ **_depreciated, use style_**
> - __style__:  Use the template style. Options: __default__, __card__, __mini__ (_Default: default_)
> - __category__:  The WordPress category ID to limit the results from. (_Default: None_)
> - __category\_name__:  The WordPress category name to limit the results from. (_Default: None_)

> Example:
```
  [blogroll number=3 trim=true style=card]
```

### iFrame ###

>  Embed iframes into your post or page content without adjusting WordPress privileges.  
> The iframe url is tested against a list of allowed domains. If the domain is not in the list the iframe will not render.  

> Attributes:  

> - __src__ : The source URL of the embedded iframe (_Default: none_)
> - __height__ : The width of the embedded iframe (_Default: WordPress's embed size width setting_)
> - __width__ :  The height of the embedded iframe (_Default: WordPress's embed size height setting_)

> Example:   
```
    [iframe src="https://www.youtube.com/embed/Bmb270rrRqQ" height="500" width="700"]
```

> Allowed domains:
```
    uams.edu,
    uamshealth.com,
    google.com,
    youtube.com,
    pgcalc.com,
    docs.google.com,
    vimeo.com,
    facebook.com,
```


### Intro ###
> This shortcode creates an italicized block of introduction text for the content.

> No attributes.

> Example:

```
  [intro] A block on introductory text for the content. [/intro]
```

### Map ###
> This shortcode embeds a map into the body content.

> Attributes: 

> - __building__ : The building [id\_number](https://maps.uams.edu/map-ids/) (_Default: None_)
> - __width__ : The width of the embedded iframe (_Default: 100%_)
> - __height__ :  The height of the embedded iframe (_Default: 480px_) 

> Example:

```
  [map building='135' width='100%' height='480'][/map]
```

### RSS ###

>  This is a shortcode embeds an RSS blogroll into the body content. It behaves similarly to the UAMS RSS Widget.

> Attributes:  

> -  __url__ : The URL to parse for the RSS feed. (_Default: None_)
> - __number__:  The maximum number of results to return (_Default: 5_)
> - __title__:  The title for the RSS blogroll in the content (_Default: None_)
> - __heading__:  The html element for the post titles. (_Default: h3_)
> - __show_image__:  Choose whether to show the RSS thumbnail. Options: __true__, __false__. (_Default: true_)
> - __show_date__:  Choose whether to show the publish date. Options: __true__, __false__. (_Default: true_)
> - __show_more__: Choose whether to show the author. Options: __true__, __false__. (_Default: true_)

> Example:
```
  [rss url="http://www.washington.edu/marketing/topic/wordpress/feed" number=3 title="Web Team Updates" heading="h2" show_image="false" show_date="false"]
```

### Tile box ###

>  Display branded tiles to structure content in elegantly. [See an example of tiles here](http://#/).  
Each tile is setup as a series of shortcodes wrapped in `[box]` shortcode. 

> Attributes:  

> - __alignment__ : How the text is aligned in each tile. Options: __centered__ or __none__ (_Default: none_)
> - __color__ : Background color of the tiles. Options: __tan__ (_Default: none_)

> Example:   
```
[box alignment=centered]
    [tile] Text for tile one [/tile]
    [tile] Text for tile two [/tile]
    [tile] Text for tile three [/tile]
    [tile] Text for tile four [/tile]
  [/box]
```

### YouTube ###

>  Embed a YouTube video or playlist into your post content.

> Attributes:  

> -  __type__ : Pick whether to display a single video or playlist. Options: __single__, __playlist__. (_Default: None_)   
> - __id__ : The youtube video or playlist id  (_Default: None_)  
> - __max-results__ (__OPTIONAL__):  The maximum number of results to return for a playlist (_Default: None_)

> Examples: 

> __Single:__
```
  [youtube type='single' id='6sjcltWIlkQ']
``` 

> __Playlist:__
```
  [youtube type='playlist' id='PLgNkGpnjFWo9CN_HeVtujhMnUXK05iwgZ' max-results='10']
```

### Subpage List ###

> This shortcode lists out all the subpages relative to the current page. 
> There are two views this shortcode can render: list or grid. 
> The list view displays all the subpages as anchor tags in an HTML list element.
> The grid view displays all the subpages as boxes, with their title, excerpt and author if available.

> Attributes: 

> - __link__ : The text in the anchor tag that will link to the subpage (_Default: Read more_)
> - __tilebox__ : Enable the grid layout of the subpages ( _Default: false_ )


> Example: 
```
 [subpage-list link="More information here" tilebox=true ]
```

### Accordion ###
> This is an accessible version of the accordion menu based off of Nicolas Hoffmann's [accessible jQuery accordion](http://a11y.nicolas-hoffmann.net/accordion/).

> Attributes: 

> - __active__ : Open the accordion section by default (_Default: none_)
> 
> Example:   
```
  [accordion name='Accessible Accordion']
    [section title='Example'] Section[/section]
    [section title='Example' active='true'] Section[/section]
    [section title='Example'] Section[/section]
   [/accordion]
```

### Tabs ###
> This is an accessible version of the accordion menu based off of Nicolas Hoffmann's [Accessible tab panel using ARIA](https://van11y.net/accessible-tab-panel/)
>The title is what appears in each tab across the top. The link is optional.  The link is the text that is used in the hashtag (Ex. #linkname1). The Tab Text is the content for each tab.
> 
> Example:   
```
  [tabs name='Accessible Tabs']
    [tab title='Example'] Tab Text [/tab]
    [tab title='Example'] Tab Text [/tab]
    [tab title='Example'] Tab Text [/tab]
   [/tabs]
```

> Example with custom link names:   
```
  [tabs name='Accessible Tabs']
    [tab title='Example' link='linkname1'] Tab Text [/tab]
    [tab title='Example' link='linkname2'] Tab Text [/tab]
    [tab title='Example' link='linkname3'] Tab Text [/tab]
   [/tabs]
```

<!-- ### Custom Menu ###
> This shortcode pulls in a custom menu that can be created under _Dashboard > Appearance > Menus_. Icons can be added in the class field in the menu builder. View the [full set of icons](http://www.#.edu/brand/web-2/html-web-components/web-icons/) for more information.
> 
> Example:   
```
  [custommenu menu=Menu-name-here]
```
> Attributes:  

> -  __menu__ : Enter the name of the menu found in _Dashboard > Appearance > Menus_. (_Default: Main menu_)   --> 




## Features
- - -

### UAMS Widget Visibility ###

> This feature provides granular control over where each widget appears on your site. It is based on the [JetPack Widget Visibilityj plugin](http://jetpack.me/support/widget-visibility/) and allows you to choose specific pages, authors, categories etc. to show a widget on. Follow the link for a brief tutorial on its usage. 

### UAMS Media Credit ###

> This feature allows images to have author credits next to them. When an image is selected in the Media Library a field for Media Credit will appear next to its other attributes. This credit will always appear after the image caption. 

### UAMS Helper Classes ###

> A small library of CSS helper classes that can be used to reduce the amount of CSS that can be added every time you need to add a new module or elements. 

> Options:

> - __Class__ _{ CSS Property }_
> - __text-left__ { text-align: left; }
> - __text-right__ { text-align: right; }
> - __text-center__ { text-align: center; }
> - __text-just__ { text-align: justify; }
> - __left__ { float: left; }
> - __right__ { float: right; }
> - __fit__ { max-width: 100%; }
> - __half-width__ { width: 50%; }
> - __full-width__ { width: 100%; }
> - __full-height__ { height: 100%; }
> - __inline__ { display: inline; }
> - __block__ { display: block; }
> - __inline-block__ { display: inline-block; }
> - __hidden__ { display: none; }
> - __bold__ { font-weight: bold; }
> - __regular__ { font-weight: normal; }
> - __italic__ { font-style: italic; }
> - __truncate__ { overflow: hidden; text-overflow: ellipsis; }
> - __margin-top-none__ { margin-top: 0; }
> - __margin-top-quarter__ { margin-top: 0.25em; }
> - __margin-top-half__ { margin-top: 0.5em; }
> - __margin-top-one__ { margin-top: 1em; }
> - __margin-top-two__ { margin-top: 2em; }
> - __margin-top-three__ { margin-top: 3em; }
> - __margin-top-four__ { margin-top: 4em; }
> - __margin-bottom-none__ { margin-bottom: 0; }
> - __margin-bottom-quarter__ { margin-bottom: 0.25em; }
> - __margin-bottom-half__ { margin-bottom: 0.5em; }
> - __margin-bottom-one__ { margin-bottom: 1em; }
> - __margin-bottom-two__ { margin-bottom: 2em; }
> - __margin-bottom-three__ { margin-bottom: 3em; }
> - __margin-bottom-four__ { margin-bottom: 4em; }
> - __margin-right-none__ { margin-right: 0; }
> - __margin-right-quarter__ { margin-right: 0.25em; }
> - __margin-right-half__ { margin-right: 0.5em; }
> - __margin-right-one__ { margin-right: 1em; }
> - __margin-right-two__ { margin-right: 2em; }
> - __margin-right-three__ { margin-right: 3em; }
> - __margin-right-four__ { margin-right: 4em; }
> - __margin-left-none__ { margin-left: 0; }
> - __margin-left-quarter__ { margin-left: 0.25em; }
> - __margin-left-half__ { margin-left: 0.5em; }
> - __margin-left-one__ { margin-left: 1em; }
> - __margin-left-two__ { margin-left: 2em; }
> - __margin-left-three__ { margin-left: 3em; }
> - __margin-left-four__ { margin-left: 4em; }
> - __margin-none__ { margin: 0; }
> - __margin-quarter__ { margin: 0.25em; }
> - __margin-half__ { margin: 0.5em; }
> - __margin-one__ { margin: 1em; }
> - __margin-two__ { margin: 2em; }
> - __margin-three__ { margin: 3em; }
> - __margin-four__ { margin: 4em; }
> - __padding-top-none__ { padding-top: 0; }
> - __padding-top-quarter__ { padding-top: 0.25em; }
> - __padding-top-halve__ { padding-top: 0.5em; }
> - __padding-top-one__ { padding-top: 1em; }
> - __padding-top-two__ { padding-top: 2em; }
> - __padding-top-three__ { padding-top: 3em; }
> - __padding-top-four__ { padding-top: 4em; }
> - __padding-bottom-none__ { padding-bottom: 0; }
> - __padding-bottom-quarter__ { padding-bottom: 0.25em; }
> - __padding-bottom-halve__ { padding-bottom: 0.5em; }
> - __padding-bottom-one__ { padding-bottom: 1em; }
> - __padding-bottom-two__ { padding-bottom: 2em; }
> - __padding-bottom-three__ { padding-bottom: 3em; }
> - __padding-bottom-four__ { padding-bottom: 4em; }
> - __padding-right-none__ { padding-right: 0; }
> - __padding-right-quarter__ { padding-right: 0.25em; }
> - __padding-right-halve__ { padding-right: 0.5em; }
> - __padding-right-one__ { padding-right: 1em; }
> - __padding-right-two__ { padding-right: 2em; }
> - __padding-right-three__ { padding-right: 3em; }
> - __padding-right-four__ { padding-right: 4em; }
> - __padding-left-none__ { padding-left: 0; }
> - __padding-left-quarter__ { padding-left: 0.25em; }
> - __padding-left-halve__ { padding-left: 0.5em; }
> - __padding-left-one__ { padding-left: 1em; }
> - __padding-left-two__ { padding-left: 2em; }
> - __padding-left-three__ { padding-left: 3em; }
> - __padding-left-four__ { padding-left: 4em; }
> - __padding-none__ { padding: 0; }
> - __padding-quarter__ { padding: 0.25em; }
> - __padding-halve__ { padding: 0.5em; }
> - __padding-one__ { padding: 1em; }
> - __padding-two__ { padding: 2em; }
> - __padding-three__ { padding: 3em; }
> - __padding-four__ { padding: 4em; }


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

## License
- - - 
GPL-2.0+
