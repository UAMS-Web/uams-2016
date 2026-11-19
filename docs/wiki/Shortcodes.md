# Shortcodes

<!-- cspell:ignore Blogroll custommenu googleapps multisite Hoffmann's tilebox titletag uams -->

Written against v1.3.0.8.

The theme registers these shortcodes for post and page content. Attribute values that are not listed as options are free text. Every URL in the examples is a placeholder.

The bundled Advanced Custom Fields Pro also registers `[acf]` when no ACF plugin is active; it is described in the ACF documentation, not here.

## Accordion

An accessible accordion, based on Nicolas Hoffmann's accessible jQuery accordion. `[accordion]` wraps one `[section]` per panel, and a section can hold `[subsection]` panels.

| Shortcode | Attribute | Default | Effect |
| --- | --- | --- | --- |
| `[accordion]` | `name` | empty | Heading shown above the accordion. |
| `[section]`, `[subsection]` | `title` | empty | The panel's title. |
| `[section]`, `[subsection]` | `active` | off | Opens the panel when the page loads. **Any value other than an empty one or `0` opens it, `active="false"` included**; leave the attribute out to keep a panel closed. |

```
[accordion name="Accessible Accordion"]
  [section title="First"] Section text [/section]
  [section title="Second" active="true"] Section text [/section]
[/accordion]
```

## Blogroll

Lists posts, fetched with the WordPress `get_posts()` function. Only the attributes below are used; any other attribute is dropped, and the number of posts is always `number`.

| Attribute | Default | Effect |
| --- | --- | --- |
| `number` | `5` | The most posts to list. |
| `style` | `default` | `default`, `card` or `mini`. |
| `excerpt` | shown | `show` or `true` shows the excerpt; any other value hides it. |
| `trim` | `false` | `true` trims the excerpt with `wp_trim_words()`. |
| `image` | `hide` | `show` shows the featured image. The card style always shows it. |
| `author` | `show` | `hide` leaves the author out. |
| `date` | `show` | `hide` leaves the date out. |
| `titletag` | `h2` | The HTML element for each post title. |
| `post_type` | `post` | The post type to list. |
| `category` | empty | A category ID to limit the list to. |
| `category_name` | empty | A category slug to limit the list to. |
| `mini` | off | Deprecated; use `style="mini"`. Any value other than an empty one or `0` turns it on, `mini="false"` included. |

```
[blogroll number="3" trim="true" style="card"]
```

## Box and tile

Branded tiles that structure content. `[box]` wraps up to 12 `[tile]` shortcodes.

| Shortcode | Attribute | Default | Effect |
| --- | --- | --- | --- |
| `[box]` | `alignment` | `none` | `centered` centers the text in each tile. |
| `[box]` | `color` | empty | `tan` gives the tiles a tan background. |
| `[box]` | `padding` | empty | Any value other than an empty one or `0` removes the tiles' padding. |
| `[box]` | `shadow` | empty | Any value other than an empty one or `0` removes the tiles' shadow. |
| `[box]` | `custom` | empty | An extra CSS class for the box. |
| `[tile]` | `empty` | `false` | Marks the tile as empty. |

```
[box alignment="centered"]
  [tile] Text for tile one [/tile]
  [tile] Text for tile two [/tile]
[/box]
```

## Button

A branded call-to-action button. The enclosed text is the button's label.

| Attribute | Default | Effect |
| --- | --- | --- |
| `url` | `#` | Where the button links to. |
| `color` | none | `red`, `blue`, `green`, `yellow`, `gray` or `grey`. |
| `type` | none | The icon: `plus`, `go`, `external` or `play`. Any other value is ignored. Without a type the button shows the default arrow; `go` has no style of its own, so it looks the same. |
| `size` | none | `small` (or `sm`) or `large` (or `lg`). |
| `small` | none | Any value makes the button small, the same as `size="small"`. |
| `text` | empty | A label that replaces the enclosed text. |

With no label at all the shortcode prints the text "No text in this button" in place of a button.

```
[button color="red" url="https://www.example.com/"]Button text[/button]
[button color="blue" type="plus" size="small" url="https://www.example.com/"]Button text[/button]
```

## Cards

A group of cards. `[cards]` wraps one `[card]` per card.

| Shortcode | Attribute | Default | Effect |
| --- | --- | --- | --- |
| `[cards]` | `name` | empty | Heading shown above the cards. |
| `[card]` | `title` | empty | The card title. |
| `[card]` | `image`, `alt` | empty | An image URL and its alternative text. |
| `[card]` | `link` | empty | Where the card links to. |
| `[card]` | `size`, `color`, `type` | empty | Each becomes a CSS class on the card. |
| `[card]` | `action`, `footer` | empty | Text for the card's action and footer areas; limited HTML is allowed. |

```
[cards name="Our services"]
  [card title="First" link="https://www.example.com/"] Card text [/card]
[/cards]
```

## Custom menu

Shows a menu built under *Appearance*, *Menus*. It wraps `wp_nav_menu()`, and its attributes are that function's arguments.

| Attribute | Default | Effect |
| --- | --- | --- |
| `menu` | empty | The menu's name. |
| `container` | `div` | The element around the menu. |
| `container_class` | `icon-menu` | The container's CSS class. |
| `menu_class` | `menu` | The list's CSS class. |
| `depth` | `0` | How many levels to show; `0` shows all. |

`theme_location`, `container_id`, `menu_id`, `before`, `after`, `link_before`, `link_after`, `fallback_cb` (default `wp_page_menu`) and `walker` are also passed through.

```
[custommenu menu="Menu name"]
```

## Google Calendar (googleapps)

Embeds a Google Calendar with the `[googleapps]` shortcode. **For a user who may not post unfiltered HTML, pasting the embed code Google Calendar gives into the editor converts it to this shortcode** when the post is saved, with the width capped at 630. The conversion runs only where WordPress filters the content (authors and contributors, and on a multisite network everyone but super admins); an administrator or editor on a single site keeps the pasted iframe.

| Attribute | Default | Effect |
| --- | --- | --- |
| `dir` | empty | Must be `calendar/embed`, or nothing is shown. |
| `query` | empty | The calendar's query string from the embed code. |
| `domain` | `www` | Accepted but has no effect; the calendar is always loaded from `www.google.com`. |
| `width` | `620` | Width in pixels. |
| `height` | `500` | Height in pixels. |
| `app` | `calendar` | Becomes the CSS class `googleapps-<app>` on the wrapper; the embed is always a calendar. |

## iFrame

Embeds an iframe without giving the editor permission to post raw HTML. The address is checked against a list of allowed domains kept in the theme ([`get_iframe_domains()` in `setup/class.uams-iframes.php`](https://github.com/UAMS-Web/uams-2016/blob/master/setup/class.uams-iframes.php)). **The host must match an entry exactly**, so a subdomain of an allowed domain is refused unless it is listed itself. An address that is refused, or is not `http` or `https`, shows nothing. An address with no host, such as a path on the same site, is not checked against the list.

| Attribute | Default | Effect |
| --- | --- | --- |
| `src` | empty | The address to embed. |
| `width` | the site's embed width | Width of the iframe. |
| `height` | the site's embed height | Height of the iframe. |

The page's own query string is added to the iframe's address.

```
[iframe src="https://www.example.com/embed/page" width="700" height="500"]
```

## Intro

A block of introduction text in a larger type. HTML and shortcodes inside it are shown as text.

```
[intro] A block of introductory text for the content. [/intro]
```

## Map

Embeds the campus map with one building marked, and a "View full map" link.

| Attribute | Default | Effect |
| --- | --- | --- |
| `building` | none | Required. A building code from the list in [`setup/class.maps-shortcode.php`](https://github.com/UAMS-Web/uams-2016/blob/master/setup/class.maps-shortcode.php). |
| `width` | `100%` | Width of the map. |
| `height` | `480px` | Height of the map. |

Without a building, or with a code that is not in the list, the shortcode shows an error instead of a map; see [Troubleshooting](https://github.com/UAMS-Web/uams-2016/wiki/Troubleshooting#the-map-shortcode-shows-an-error).

```
[map building="135" width="100%" height="480"]
```

## Media credit

Shows "Image by" and a credit under an image. The editor writes this shortcode itself when an image that has an **Image Credit** is inserted without a caption; see [Image credits](https://github.com/UAMS-Web/uams-2016/wiki/Features#image-credits).

| Attribute | Default | Effect |
| --- | --- | --- |
| `credit` | empty | The credit text. |
| `id` | empty | Required: the image's attachment ID. Without it the shortcode prints nothing, the image included. |
| `align` | empty | The image's alignment. |
| `size` | `full` | The image size. |

## Row and column

A grid. `[row]` wraps `[col]` shortcodes, and each `class` is a Bootstrap grid class.

| Shortcode | Attribute | Default |
| --- | --- | --- |
| `[row]` | `class` | `row` |
| `[col]` | `class` | `col-sm-1` |

```
[row]
  [col class="col-sm-6"] Left half [/col]
  [col class="col-sm-6"] Right half [/col]
[/row]
```

## RSS

Shows items from an RSS feed in the content, like the [UAMS RSS widget](https://github.com/UAMS-Web/uams-2016/wiki/Widgets#uams-rss).

| Attribute | Default | Effect |
| --- | --- | --- |
| `url` | none | Required. The feed to show; without it the shortcode shows nothing. |
| `number` | `5` | The most items to show. |
| `show_image` | `true` | `false` hides each item's image. |
| `show_date` | `true` | `false` hides each item's date. |
| `show_desc` | `false` | `true` shows each item's description. |
| `show_more` | `true` | `false` hides the "More" link to the feed. |
| `more` | the feed's own link | Where the "More" link goes. |

`title`, `heading`, `span` and `has_blurb` are accepted but have no visible effect.

```
[rss url="https://www.example.com/feed/" number="3" show_image="false"]
```

## Subpage list

Lists the child pages of the current page.

| Attribute | Default | Effect |
| --- | --- | --- |
| `link` | `Read more` | The text of the button that links to each child page. Empty leaves the button out. |
| `tilebox` | `false` | `true` shows the child pages as two-column tiles with a thumbnail. |

The default view shows each child page's title as a heading, its excerpt and the button, and its author when the site shows bylines on posts.

```
[subpage-list link="More information" tilebox="true"]
```

## Tabs

An accessible tab panel, based on Nicolas Hoffmann's accessible tab panel using ARIA. `[tabs]` wraps one `[tab]` per tab.

| Shortcode | Attribute | Default | Effect |
| --- | --- | --- | --- |
| `[tabs]` | `name` | empty | Used as a CSS class, with spaces removed; it is not shown. |
| `[tab]` | `title` | empty | The tab's label. |
| `[tab]` | `link` | `tab-1`, `tab-2` and so on | The tab's anchor, for linking to it as `#link`. |

```
[tabs name="Accessible Tabs"]
  [tab title="First" link="first"] Tab text [/tab]
  [tab title="Second" link="second"] Tab text [/tab]
[/tabs]
```

## Twitter

Shows recent posts from a Twitter account, like the [UAMS Twitter Feed widget](https://github.com/UAMS-Web/uams-2016/wiki/Widgets#uams-twitter-feed), and exists only where that widget does.

| Attribute | Default |
| --- | --- |
| `title` | empty |
| `name` | `twitter` |
| `count` | `5` |

## YouTube

Embeds a YouTube video or playlist. **It needs a YouTube API key**; see **The API key** below.

| Attribute | Default | Effect |
| --- | --- | --- |
| `type` | none | Required: `single` or `playlist`. |
| `id` | none | Required: the video or playlist ID. |
| `max-results` (or `max_results`) | 20 for a playlist | The most playlist items to show. |

```
[youtube type="single" id="VIDEO_ID"]
[youtube type="playlist" id="PLAYLIST_ID" max-results="10"]
```

### The API key

The shortcode's script asks the YouTube Data API for the video or playlist details, and the request needs an API key. The theme does not carry a key. Define it in the site's `wp-config.php`, above the line that says to stop editing:

```
define( 'UAMS_YOUTUBE_API_KEY', 'the-key-from-google-cloud' );
```

The theme passes the value to the browser as `uams_youtube.apiKey`, so it is a **browser key** that every visitor can read, and it has to be restricted in Google Cloud:

- **API restrictions:** allow only the YouTube Data API v3.
- **Application restrictions:** HTTP referrers, listing the site's domains.

When the constant is not defined or is empty, the theme sends no request to YouTube and the shortcode's placeholder stays empty. To rotate the key, change the constant; no theme release is needed.
