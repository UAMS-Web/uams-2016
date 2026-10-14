<?php
if ( get_post_meta( get_the_ID(), 'show_image_single', true ) && is_single() && get_post_thumbnail_id() ) { ?>
    <p class="featured-image">
      <a href="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'full' ) ); ?>" title="<?php echo esc_attr( get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) ); ?>" data-title="<?php echo esc_attr( get_post_field( 'post_title', get_post_thumbnail_id() ) ); ?>" data-caption="<?php echo esc_attr( get_post_field( 'post_excerpt', get_post_thumbnail_id() ) ); ?>">
        <span class="screen-reader-text"><?php esc_attr_e( 'View Larger Image', 'UAMS' ); ?></span>
      <?php the_post_thumbnail( 'post-image' ); ?>
      <?php if ( get_post_field( 'post_excerpt', get_post_thumbnail_id() ) ) { ?>
        <br/><span class="wp-caption-text"><?php echo esc_html( get_post_field( 'post_excerpt', get_post_thumbnail_id() ) ); ?></span>
      <?php } ?>
      </a>
      <?php 
          $credit = get_post_meta( get_post_thumbnail_id(), '_media_credit', true ); 
          echo $credit ? '<span class="wp-media-credit">Image by ' . esc_html( $credit ) . '</span>' : '';
      ?>
    </p>
<?php
}
if ( is_single() || is_home() ) {
    the_date( 'F j, Y', '<p class="date">', '</p>' );
}
?>
<h1><?php the_title(); ?></h1>
<?php
if ( ( is_single() || is_home() ) && get_option( 'show_byline_on_posts' ) ) :
?>
<div class="author-info">
    By <?php if ( function_exists( 'coauthors' ) ) { coauthors(); } else { the_author(); } ?>
    <p class="author-desc"> <small><?php the_author_meta( 'description' ); ?></small></p>
</div>
<?php
endif;
if ( is_single() && get_option( 'show_category_on_posts' ) ) :
?>
<div class="category-info">
    <p class="<?php echo ( get_option( 'show_byline_on_posts' ) ) ? 'category-list' : ''; ?>"> <?php the_category( ', ' ); ?></p>
</div>
<?php
endif;
if ( ! is_home() && ! is_search() && ! is_archive() ) :
    uams_mobile_menu();
endif;

if ( is_archive() || is_home() ) {
    the_post_thumbnail( array( 130, 130 ), array( 'class' => 'attachment-post-thumbnail blogroll-img' ) );
    the_excerpt();
    echo '<hr>';
} else {
    the_content();
}
?>
