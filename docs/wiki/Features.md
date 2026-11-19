# Features

<!-- cspell:ignore Jetpack's mediacredit transparentgray transparentwhite uams zindex -->

Written against v1.3.0.8.

Features the theme adds to the WordPress dashboard, and the CSS helper classes it provides for content.

## Widget visibility

Every widget form under *Appearance*, *Widgets* has a **Visibility** button. It sets rules for where the widget shows, so one widget area can carry widgets meant for different parts of the site. The feature is a fork of Jetpack's Widget Visibility module.

1. Open a widget and select **Visibility**.
2. Choose **Show if** or **Hide if**.
3. Choose what the rule tests, and the value it must match:
   - **Category**, **Author**, **Tag** or **Date**.
   - **Page**: the front page, the posts page, an archive page, the 404 error page, search results, a post type, or a particular page.
   - **Taxonomy**, offered only when the site has custom taxonomies.
4. Add more rules as needed. The chosen action (show or hide) applies when **any** of the rules matches.
5. Save the widget.

## Image credits

Images in the media library have an **Image Credit** field, next to the caption and the other attachment details. Fill it in with the photographer or source.

- **A captioned image** shows the credit after its caption. The credit is read from the image each time the page is shown, so editing the field updates every captioned use of the image.
- **An image inserted without a caption** shows "Image by" and the credit below it. The editor saves this as a [`[mediacredit]` shortcode](https://github.com/UAMS-Web/uams-2016/wiki/Shortcodes#media-credit) holding the credit as it was when the image was inserted, so a later edit to the field does not change it; edit the shortcode's `credit` attribute instead.

The field is not offered for custom header images.

## Helper classes

CSS classes an editor can add to an element's `class` attribute in the editor's HTML view to adjust it without writing new CSS. They are defined in [`less/uams.helpers.less`](https://github.com/UAMS-Web/uams-2016/blob/master/less/uams.helpers.less), apart from the text-alignment classes, which come from Bootstrap. Several classes can be combined: `class="text-center margin-top-two"`.


### Spacing

The margin and padding classes share one scale. Build a class name from the property, an optional side, and the size: `margin-top-two`, `padding-left-quarter`, `margin-none`.

| Size | Value |
| --- | --- |
| `none` | `0` |
| `quarter` | `0.25em` |
| `half` (margin), `halve` (padding) | `0.5em` |
| `one` | `1em` |
| `two` | `2em` |
| `three` | `3em` |
| `four` | `4em` |

| Classes | Property |
| --- | --- |
| `margin-<size>` | `margin` |
| `margin-top-<size>`, `margin-bottom-<size>`, `margin-left-<size>`, `margin-right-<size>` | the margin on that side |
| `padding-<size>` | `padding` |
| `padding-top-<size>`, `padding-bottom-<size>`, `padding-left-<size>`, `padding-right-<size>` | the padding on that side |

**Note:** the half step is spelled `half` for margins and `halve` for paddings: `margin-top-half` and `padding-top-halve`. Swapping the two spellings gives a class the theme does not define.

Four shorter names set the padding on every side: `pad-none` (`0`), `pad-small` (`0.25em`), `pad-normal` (`1em`) and `pad-large` (`2em`).

### Text

| Class | Effect |
| --- | --- |
| `text-left`, `text-right`, `text-center`, `text-justify` | Aligns the text. |
| `bold` | `font-weight: bold` |
| `regular` | `font-weight: normal` |
| `italic` | `font-style: italic` |
| `truncate` | Keeps the text on one line and ends it with an ellipsis where it overflows. |
| `break-word` | Lets a long word break onto the next line. |
| `no-wrap` | Keeps the text on one line. |

### Size and display

| Class | Effect |
| --- | --- |
| `fit` | `max-width: 100%` |
| `half-width` | `width: 50%` |
| `full-width` | `width: 100%` |
| `full-height` | `height: 100%` |
| `inline` | `display: inline` |
| `block` | `display: block` |
| `inline-block` | `display: inline-block` |
| `hidden` | `display: none` |

### Position

| Class | Effect |
| --- | --- |
| `relative`, `absolute`, `fixed` | Sets `position`. |
| `zindex-1` to `zindex-4` | Sets `z-index` to 1, 2, 3 or 4. |
| `align-top`, `align-middle`, `align-bottom`, `align-baseline` | Sets `vertical-align`. |

### Color

Text colors: `transparentwhite` (white), `transparentgray` (gray), `dark-gray`, `notice`, `success` and `alert`. Background colors: `white-bg`, `gray-bg`, `dark-gray-bg`, `notice-bg`, `success-bg` and `alert-bg`. The colors are the theme's own, set in its Less variables.

### Lists

| Class | Effect |
| --- | --- |
| `no-bullets` | Removes the bullets. |
| `list-bare` | Removes the bullets, the margin and the padding. |
| `bullets-inside` | Places the bullets inside the text block. |

### Borders and corners

| Class | Effect |
| --- | --- |
| `border-all` | A light gray 1px border on every side. |
| `border-top`, `border-bottom`, `border-left`, `border-right` | The same border on one side. |
| `border-all--dark`, `border-top--dark` and the other sides | The same, in a darker gray. |
| `radius` | Rounds every corner by 3px. |
| `radius-top`, `radius-right`, `radius-bottom`, `radius-left` | Rounds the two corners on that side. |

## The Documentation page

The dashboard has a **Documentation** menu, open to every logged-in user, that shows the Widgets, Shortcodes and Features pages of this wiki. It reads them from `docs/wiki/` in the installed theme, so it shows the pages as they were in the release the site runs.
