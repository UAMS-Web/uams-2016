# Troubleshooting

<!-- cspell:ignore mediacredit UAMS -->

Written against v1.3.0.8.

- [A YouTube shortcode shows nothing](#a-youtube-shortcode-shows-nothing)
- [An iframe shortcode shows nothing](#an-iframe-shortcode-shows-nothing)
- [The map shortcode shows an error](#the-map-shortcode-shows-an-error)
- [A button reads "No text in this button"](#a-button-reads-no-text-in-this-button)
- [A button has no icon, or the wrong one](#a-button-has-no-icon-or-the-wrong-one)
- [An accordion section is open when it should be closed](#an-accordion-section-is-open-when-it-should-be-closed)
- [The UAMS Twitter Feed widget is not in the widget list](#the-uams-twitter-feed-widget-is-not-in-the-widget-list)
- [An image credit did not change after editing it](#an-image-credit-did-not-change-after-editing-it)
- [A helper class has no effect](#a-helper-class-has-no-effect)

## A YouTube shortcode shows nothing

**Cause:** the site has no YouTube API key, so the theme sends no request to YouTube and the space stays empty.

**Fix:** define `UAMS_YOUTUBE_API_KEY` in `wp-config.php`, as the [YouTube shortcode](Shortcodes.md#youtube) describes. Also check that `type` is `single` or `playlist` and that `id` is set; both are required.

## An iframe shortcode shows nothing

**Cause:** the address is not on the theme's list of allowed domains, or it is not `http` or `https`. The host must match an entry exactly, so `https://subdomain.example.com/` is refused when only `example.com` is listed.

**Fix:** use an address on an allowed host. Adding a host is a change to the theme; the list is `get_iframe_domains()` in [`setup/class.uams-iframes.php`](https://github.com/UAMS-Web/uams-2016/blob/master/setup/class.uams-iframes.php).

## The map shortcode shows an error

The page shows `required attribute "building" missing` or `Building "<code>" is not supported`.

**Cause:** `[map]` has no `building` attribute, or its code is not one the theme knows.

**Fix:** set `building` to a code from the list in [`setup/class.maps-shortcode.php`](https://github.com/UAMS-Web/uams-2016/blob/master/setup/class.maps-shortcode.php).

## A button reads "No text in this button"

**Cause:** the `[button]` has no enclosed text and no `text` attribute.

**Fix:** write the label between the tags, `[button url="https://www.example.com/"]Label[/button]`, or set `text="Label"`.

## A button has no icon, or the wrong one

**Cause:** `type` accepts only `plus`, `go`, `external` and `play`. Any other value, `pdf` included, is ignored and the button shows the default arrow.

**Fix:** use one of the four types, or leave `type` out.

## An accordion section is open when it should be closed

**Cause:** the section has an `active` attribute. Any value opens it, `active="false"` included.

**Fix:** remove the `active` attribute from the section.

## The UAMS Twitter Feed widget is not in the widget list

**Cause:** the widget and the `[twitter]` shortcode exist only when `wp-config.php` defines the Twitter API credentials.

**Fix:** define `TWITTER_OAUTH_TOKEN`, `TWITTER_OAUTH_TOKEN_SECRET`, `TWITTER_CONSUMER_KEY` and `TWITTER_CONSUMER_SECRET` in `wp-config.php`.

## An image credit did not change after editing it

**Cause:** the image was inserted without a caption, so the credit was saved in a `[mediacredit]` shortcode at that moment.

**Fix:** edit the `credit` attribute of the `[mediacredit]` shortcode in the content. A captioned image shows the credit from the media library each time the page loads, so only images inserted without a caption need this.

## A helper class has no effect

**Cause:** the class name is not one the theme defines. Common cases: `padding-top-half` (paddings use `halve`), `margin-top-halve` (margins use `half`), `text-just` (the class is `text-justify`), and `left` or `right` (not defined).

**Fix:** use a name defined in [`less/uams.helpers.less`](https://github.com/UAMS-Web/uams-2016/blob/master/less/uams.helpers.less), or Bootstrap's `text-left`, `text-right`, `text-center` and `text-justify`.
