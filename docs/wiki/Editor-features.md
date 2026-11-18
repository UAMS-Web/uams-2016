# Editor features

<!-- cspell:ignore Jetpack's mediacredit -->

Written against v1.3.0.8.

Features the theme adds to the WordPress dashboard.

- [Widget visibility](#widget-visibility)
- [Image credits](#image-credits)
- [The Documentation page](#the-documentation-page)

## Widget visibility

Every widget form under *Appearance*, *Widgets* has a **Visibility** button. It sets rules for where the widget shows, so one widget area can carry widgets meant for different parts of the site. The feature is a fork of Jetpack's Widget Visibility module.

1. Open a widget and select **Visibility**.
2. Choose **Show if** or **Hide if**.
3. Choose what the rule tests, and the value it must match:
   - **Category**, **Author**, **Tag** or **Date**.
   - **Page**: the front page, the posts page, an archive page, the 404 error page, search results, a post type, or a particular page.
   - **Taxonomy**, offered only when the site has custom taxonomies.
4. Add more rules as needed. The widget's rule applies when **any** of its rules matches.
5. Save the widget.

## Image credits

Images in the media library have an **Image Credit** field, next to the caption and the other attachment details. Fill it in with the photographer or source.

- **A captioned image** shows the credit after its caption. The credit is read from the image each time the page is shown, so editing the field updates every captioned use of the image.
- **An image inserted without a caption** shows "Image by" and the credit below it. The editor saves this as a [`[mediacredit]` shortcode](Shortcodes.md#media-credit) holding the credit as it was when the image was inserted, so a later edit to the field does not change it; edit the shortcode's `credit` attribute instead.

The field is not offered for custom header images.

## The Documentation page

The dashboard has a **Documentation** menu, open to every logged-in user, that shows the theme's [README](https://github.com/UAMS-Web/uams-2016/blob/master/README.md). It is read from the `README.md` file in the installed theme, so the page is empty on a site whose theme was installed without that file.

The dashboard's **Documentation and FAQs** widget, which the theme moves to the side column, carries links inherited from the theme it was built on.
