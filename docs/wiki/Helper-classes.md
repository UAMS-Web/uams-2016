# Helper classes

<!-- cspell:ignore transparentgray transparentwhite uams zindex -->

Written against v1.3.0.8.

CSS classes an editor can add to an element's `class` attribute in the editor's HTML view to adjust it without writing new CSS. They are defined in [`less/uams.helpers.less`](https://github.com/UAMS-Web/uams-2016/blob/master/less/uams.helpers.less), apart from the text-alignment classes, which come from Bootstrap. Several classes can be combined: `class="text-center margin-top-two"`.

- [Spacing](#spacing)
- [Text](#text)
- [Size and display](#size-and-display)
- [Position](#position)
- [Color](#color)
- [Lists](#lists)
- [Borders and corners](#borders-and-corners)

## Spacing

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

**Note:** the half step is spelled `half` for margins and `halve` for paddings: `margin-top-half` and `padding-top-halve`. `margin-top-halve` and `padding-top-half` do nothing.

Four shorter names set the padding on every side: `pad-none` (`0`), `pad-small` (`0.25em`), `pad-normal` (`1em`) and `pad-large` (`2em`).

## Text

| Class | Effect |
| --- | --- |
| `text-left`, `text-right`, `text-center`, `text-justify` | Aligns the text. |
| `bold` | `font-weight: bold` |
| `regular` | `font-weight: normal` |
| `italic` | `font-style: italic` |
| `truncate` | Keeps the text on one line and ends it with an ellipsis where it overflows. |
| `break-word` | Lets a long word break onto the next line. |
| `no-wrap` | Keeps the text on one line. |

## Size and display

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

## Position

| Class | Effect |
| --- | --- |
| `relative`, `absolute`, `fixed` | Sets `position`. |
| `zindex-1` to `zindex-4` | Sets `z-index` to 1, 2, 3 or 4. |
| `align-top`, `align-middle`, `align-bottom`, `align-baseline` | Sets `vertical-align`. |

## Color

Text colors: `transparentwhite` (white), `transparentgray` (gray), `dark-gray`, `notice`, `success` and `alert`. Background colors: `white-bg`, `gray-bg`, `dark-gray-bg`, `notice-bg`, `success-bg` and `alert-bg`. The colors are the theme's own, set in its Less variables.

## Lists

| Class | Effect |
| --- | --- |
| `no-bullets` | Removes the bullets. |
| `list-bare` | Removes the bullets, the margin and the padding. |
| `bullets-inside` | Places the bullets inside the text block. |

## Borders and corners

| Class | Effect |
| --- | --- |
| `border-all` | A light gray 1px border on every side. |
| `border-top`, `border-bottom`, `border-left`, `border-right` | The same border on one side. |
| `border-all--dark`, `border-top--dark` and the other sides | The same, in a darker gray. |
| `radius` | Rounds every corner by 3px. |
| `radius-top`, `radius-right`, `radius-bottom`, `radius-left` | Rounds the two corners on that side. |
