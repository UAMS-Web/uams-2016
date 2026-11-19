<?php
/**
 * The admin Documentation page (see docs/class.uams-documentation.php).
 *
 * Prints the theme's reference pages from docs/wiki/, in this order, into a hidden element;
 * assets/admin/js/admin.js renders that Markdown into #uams-documentation. The same pages are
 * published to the repository's GitHub wiki. A page that is missing is skipped.
 */
$uams_documentation_pages = array( 'Widgets.md', 'Shortcodes.md', 'Features.md' );
?>
<div class="wrap">
  <div id="uams-documentation"></div>
</div>

<div id="markdown" style="display:none;">
  <?php
  foreach ( $uams_documentation_pages as $uams_documentation_page ) {
      $uams_documentation_path = get_template_directory() . '/docs/wiki/' . $uams_documentation_page;
      if ( file_exists( $uams_documentation_path ) ) {
          // A blank line between pages, so one page's last line never runs into the next heading.
          echo esc_html( file_get_contents( $uams_documentation_path ) ) . "\n\n";
      }
  }
  ?>
</div>
