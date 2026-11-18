# Setting the YouTube API key

<!-- cspell:ignore uams -->

Written against v1.3.0.8.

The [`[youtube]` shortcode](Shortcodes.md#youtube) asks the YouTube Data API for a video's or playlist's details from the visitor's browser, and the request needs an API key. The theme does not carry one. At the end of these steps the shortcode shows videos on the site.

1. In Google Cloud, create an API key for the YouTube Data API v3.
2. Restrict the key. The theme passes it to the browser (as `uams_youtube.apiKey`), so every visitor can read it:
   - **API restrictions:** allow only the YouTube Data API v3.
   - **Application restrictions:** HTTP referrers, listing the site's domains.
3. Define the key in the site's `wp-config.php`, above the line that says to stop editing:

   ```php
   define( 'UAMS_YOUTUBE_API_KEY', 'YOUR_API_KEY' );
   ```

4. Load a page that uses the shortcode. The video or playlist now shows.

**Note:** when the constant is not defined, or is empty, the theme sends no request to YouTube and the shortcode's space on the page stays empty; see [Troubleshooting](Troubleshooting.md#a-youtube-shortcode-shows-nothing).

To rotate the key, change the constant. No theme release is needed.

## Reference

- [Shortcodes: YouTube](Shortcodes.md#youtube)
