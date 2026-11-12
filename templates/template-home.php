<?php
/**
 * Template Name: Home
 */
get_header();

$post_id = get_the_ID();
// The stubs type this value as possibly false; the call is unchanged.
// @phpstan-ignore argument.type
$sidebar = get_post_meta($post_id, 'sidebar', true);
// The stubs type this value as possibly false; the call is unchanged.
// @phpstan-ignore argument.type
$breadcrumbs = get_post_meta($post_id, 'breadcrumb', true);
$has_sidebar = ($sidebar !== 'on');

$first = true;
$i = 0;
$slidecolor = [];
$slider_type = function_exists('get_field') ? get_field('home_page_slider') : '';

if ($slider_type === 'slide' && function_exists('have_rows') && have_rows('home_slides')) { ?>

<div class="uams-homepage-slider-container" role="region" aria-label="homepage-slides">
  <?php
  while (have_rows('home_slides')) {
      the_row();
      $desktopimage = get_sub_field('home_slide_desktop');
      $mobileimage = get_sub_field('home_slide_mobile');
      $dt_url = is_array($desktopimage) && ! empty($desktopimage['url']) ? $desktopimage['url'] : (is_string($desktopimage) ? $desktopimage : '');
      $mob_url = is_array($mobileimage) && ! empty($mobileimage['url']) ? $mobileimage['url'] : (is_string($mobileimage) ? $mobileimage : '');
      $hasmobileimage = ! empty($mob_url);

      $buttonlink = get_sub_field('home_slide_internal_link');
      if (get_sub_field('home_slide_external') && get_sub_field('home_slide_external_link')) {
          $buttonlink = get_sub_field('home_slide_external_link');
      }
      $textcolor = get_sub_field('home_slide_text_color');
      $slidecolor[$i] = $textcolor ?: 'lighttext';
      ?>
    <div data-mobimg="<?php echo esc_url($hasmobileimage ? $mob_url : $dt_url); ?>" data-dtimg="<?php echo esc_url($dt_url); ?>" class="uams-hero-image uams-homepage-slider <?php echo esc_attr($slidecolor[$i]); ?> <?php echo $first ? 'activeslide' : ''; ?>" style="background-position: center center; background-image:url('<?php echo esc_url($dt_url); // @phpstan-ignore argument.type, argument.type, argument.type, argument.type?>');">
      <div>
        <h3 class="slide-title" id="slide-title-<?php echo $i; ?>"><?php echo esc_html(get_sub_field('home_slide_title')); // @phpstan-ignore argument.type?><span class="udub-slant"><span></span></span></h3>
        <p class="slide-content"><?php echo esc_html(get_sub_field('home_slide_text')); // @phpstan-ignore argument.type?></p>
        <p><a class="uams-btn btn-sm btn-none" href="<?php echo esc_url($buttonlink); ?>" aria-describedby="slide-title-<?php echo $i; ?>"><?php echo esc_html(get_sub_field('home_slide_button_text')); // @phpstan-ignore argument.type, argument.type?></a></p>
      </div>
    </div>
  <?php
          $first = false;
      $i++;
  }
    ?>
  <?php if ($i > 1) { ?>
  <div class="slideshow-controls <?php echo esc_attr($slidecolor[0] ?? ''); // @phpstan-ignore argument.type?>">
    <button class="next-headline">
      <span class="uwn-slideshow-next-text">NEXT</span>
      <span class="uwn-slideshow-next-title">NEXT TITLE HERE</span>
      <span class="udub-slant" style="margin-top: 10px;"><span></span></span>
    </button>
  </div>
  <?php } ?>
</div>

<?php } else {
    // The stubs type this value as possibly false; the call is unchanged.
    // @phpstan-ignore argument.type
    $thumb_id = get_post_thumbnail_id($post_id);
    $url = $thumb_id ? wp_get_attachment_url($thumb_id) : '';
    if (! $url) {
        $url = get_template_directory_uri().'/assets/headers/uams-pattern-grey.png';
    }

    // The stubs type this value as possibly false; the call is unchanged.
    // @phpstan-ignore argument.type
    $darktext = (array) get_post_meta($post_id, 'home_image_dark_text', true);
    $hasdarktext = in_array('1', $darktext, true) ? ' hero-text-dark' : '';
    $mobileimage_raw = function_exists('get_field') ? get_field('home_image_mobile') : '';
    $mobileimage_url = is_array($mobileimage_raw) && ! empty($mobileimage_raw['url']) ? $mobileimage_raw['url'] : (is_string($mobileimage_raw) ? $mobileimage_raw : '');
    $hasmobileimage = ! empty($mobileimage_url) ? ' hero-mobile-image' : '';
    $home_title = function_exists('get_field') && get_field('home_image_title') ? get_field('home_image_title') : get_the_title();
    $btn_url = function_exists('get_field') && get_field('home_image_external') ? get_field('home_image_external_url') : (function_exists('get_field') ? get_field('home_image_internal_url') : '');
    ?>

<div class="uams-hero-image hero-height<?php echo esc_attr($hasmobileimage); ?>" style="background-image: url('<?php echo esc_url($url); ?>');">
    <?php if (! empty($mobileimage_url)) { ?>
    <div class="mobile-image" style="background-image: url('<?php echo esc_url($mobileimage_url); // @phpstan-ignore argument.type?>');"></div>
    <?php } ?>
    <div id="hero-bg">
      <div id="hero-container" class="container">
        <h1 class="uams-site-title<?php echo esc_attr($hasdarktext); ?>"><?php echo esc_html($home_title); // @phpstan-ignore argument.type?></h1>
        <span class="udub-slant"><span></span></span>
      <?php if (function_exists('get_field') && get_field('home_image_add_button')) { ?>
        <a class="uams-btn btn-sm btn-none" href="<?php echo esc_url($btn_url); ?>"><?php echo esc_html(get_field('home_image_button_text')); // @phpstan-ignore argument.type, argument.type?></a>
      <?php } ?>
      </div>
    </div>
</div>

<?php } ?>

<?php if (function_exists('get_field') && get_field('action_menu_active') && function_exists('have_rows') && have_rows('action_menu')) { ?>
<div class="full-bar">
  <nav aria-label="popular links" class="container action-bar">
    <ul class="center-block">
      <?php
          $rows = get_field('action_menu');
    $row_count = is_array($rows) ? count($rows) : 1;

    while (have_rows('action_menu')) {
        the_row();
        $linktitle = get_sub_field('action_link_title');
        $icon = get_sub_field('action_link_icon');
        $external = get_sub_field('action_link');
        $internalurl = get_sub_field('action_link_page');
        $externalurl = get_sub_field('action_link_url');
        $action_link = $external ? $externalurl : $internalurl;
        ?>
        <li class="ab-1_<?php echo $row_count; ?>"><a href="<?php echo esc_url($action_link); ?>" title="<?php echo esc_attr($linktitle); ?>"><span class="icon <?php echo esc_attr($icon); ?>"></span><span><?php echo esc_html($linktitle); // @phpstan-ignore argument.type, argument.type, argument.type, argument.type?></span></a></li>
      <?php } ?>
    </ul>
  </nav>
</div>
<?php } ?>

<div class="container uams-body">

  <div class="row">

    <div class="hero-content col-md-<?php echo $has_sidebar ? '8' : '12'; ?> uams-content" role="main">

      <?php
        if (empty($breadcrumbs) || $breadcrumbs !== 'on') {
            get_template_part('breadcrumbs');
        }
?>

      <div id="mobile-sidebar">

        <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">

            <div aria-hidden="true" id="ham">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div id="mobile-sidebar-title" class="page_item">

                <?php
echo esc_html(text_cut(get_the_title(), 27, true));
?>

            </div>
        </button>
        <div id="mobile-sidebar-links" aria-hidden="true"><?php uams_sidebar_menu(); ?></div>
      </div>

      <div id="main_content" class="uams-body-copy" tabindex="-1">

        <?php
        while (have_posts()) {
            the_post();

            the_content();

            if (comments_open() || get_comments_number()) {
                comments_template();
            }

        }
?>

      </div>

    </div>

    <?php if ($has_sidebar) { ?>
    <div id="sidebar">
      <?php get_sidebar(); ?>
    </div>
    <?php } ?>

  </div>

</div>

<?php
if (function_exists('get_field') && get_field('home_page_slider') === 'slide' && function_exists('have_rows') && have_rows('home_slides')) {
    wp_enqueue_script('home-slider-script', get_template_directory_uri().'/js/home-slider.js', ['jquery'], '1.1', true);
}

get_footer();
?>
