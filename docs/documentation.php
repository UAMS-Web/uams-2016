<div class="wrap">
  <div id="uams-documentation"></div>
</div>

<div id="markdown" style="display:none;">
  <?php
  $readme_path = get_template_directory() . '/README.md';
  if ( file_exists( $readme_path ) ) {
      echo esc_html( file_get_contents( $readme_path ) );
  }
  ?>
</div>
