# Widgets

<!-- cspell:ignore Blogroll UAMS -->

Written against v1.3.0.8.

The theme registers the widgets below. Add them under *Appearance*, *Widgets*. The theme has one widget area, **Sidebar** ("Right column widgets"). Each widget also has a **Visibility** button that limits where it shows; see [Widget visibility](https://github.com/UAMS-Web/uams-2016/wiki/Features#widget-visibility).

The names are the ones the widget list shows. Defaults are what a newly added widget starts with.

## UAMS Blogroll

Lists the most recent posts. The widget renders the [`[blogroll]` shortcode](https://github.com/UAMS-Web/uams-2016/wiki/Shortcodes#blogroll) with the number and style chosen here.

| Option | Default | Effect |
| --- | --- | --- |
| Title | `UAMS Blogroll` | Heading above the list. |
| Number of posts to show | `2` | How many posts to list. |
| Style | Default style | Default style, Card style or Mini style. |

## UAMS Campus Map

Shows an embedded campus map with one building marked, and a "View larger" link.

| Option | Default | Effect |
| --- | --- | --- |
| Title | empty | Heading above the map. |
| Building Code | none (Select ...) | The building to mark, chosen from a list of building names. |

## UAMS Image Cards

A branded card with an image, a short text and a link.

| Option | Default | Effect |
| --- | --- | --- |
| Title | `Image Widget` | The card title. |
| Select an Image | none | An image from the media library. |
| Featured text | empty | A short text shown with the image. |
| Link | empty | Where the card links to. |
| Link text | `Read more` | The text of the link. Saved empty, the card shows `More`. |
| Card style | Default card | Default card or Styled card. |

## Contact list

A list of contacts for a group or department. Add a row per person with **Add Item**, remove one with **Remove**, and drag rows to reorder them.

| Option | Default | Effect |
| --- | --- | --- |
| Title | empty | Heading above the list. Saved empty, the widget shows `List`. |
| Name | empty | The person's name. |
| Title (per person) | empty | The person's job title. |
| Phone | empty | Shown as a link that dials the number. |
| Email | empty | Shown as a mail link, encoded against address harvesting. |

## UAMS Intro Text

A block of introduction text, the widget form of the [`[intro]` shortcode](https://github.com/UAMS-Web/uams-2016/wiki/Shortcodes#intro).

| Option | Default | Effect |
| --- | --- | --- |
| Intro text | empty | The text. HTML tags are removed when the widget is saved. With no text, the widget shows nothing. |

## UAMS Recent Posts

Like the WordPress Recent Posts widget, with the theme's layout.

| Option | Default | Effect |
| --- | --- | --- |
| Title | `UAMS Recent Posts` | Heading above the list. |
| Number of items to display | `5` | 1 to 5. |
| Display more link | off | Adds a link to the posts page. It shows only when a posts page is set under *Settings*, *Reading*. |

## UAMS RSS

Shows items from an RSS feed in the theme's layout, with the feed's images.

| Option | Default | Effect |
| --- | --- | --- |
| Give the feed a title (optional) | empty | Heading above the feed. |
| Featured blurb | empty | A short text shown before the feed. Users who may post unfiltered HTML can use HTML here; for others it is filtered. |
| Enter the RSS feed URL here | empty | The feed to show. |
| Number of items to display | `10` | 1 to 20. |
| Show the date? | on | Shows each item's date. |

## UAMS Social Icons

A row of links to the department's social accounts. Leave a field empty to leave its icon out.

| Option | Default | Effect |
| --- | --- | --- |
| Title ("Appears above the icons") | `Connect with us:` | Text above the icons. |
| Facebook URI, Twitter URI, Instagram URI, YouTube URI, LinkedIn URI, Pinterest URI | empty | The account address for each icon. |

## UAMS Twitter Feed

Shows recent posts from a Twitter account. **This widget, and the `[twitter]` shortcode, exist only on a site whose `wp-config.php` defines `TWITTER_OAUTH_TOKEN`**; without it the widget is not in the widget list. The feed also needs `TWITTER_OAUTH_TOKEN_SECRET`, `TWITTER_CONSUMER_KEY` and `TWITTER_CONSUMER_SECRET`.

| Option | Default | Effect |
| --- | --- | --- |
| Title | `UAMS Twitter Feed` | Heading above the feed. |
| Twitter screen name | `twitter` | The account to show. |
| Number of tweets to show | `5` | How many posts to show. |
