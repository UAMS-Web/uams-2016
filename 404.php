<?php get_header(); ?>

<?php get_template_part('header', 'image'); ?>

<div class="container uams-body">

  <div class="row">

    <div <?php uams_content_class(); ?> role="main">

      <?php get_template_part('breadcrumbs'); ?>

        <div class="row show-grid">
          <div class="col-md-12">

            <div class="reddie"><img src="<?php echo get_template_directory_uri().'/assets/images/reddie-404.png' ?>" alt="Never fear. Reddie is here!" /></div>

            <div class="row show-grid">

              <div class="col-md-6">
                <?php get_search_form(); ?>
                <h3>Can't find what you're looking for?</h3>
                <p> Reddie, the red blood cell — and official UAMS mascot, is here to help you find what you're looking for.</p>
              </div>

              <div class="col-md-5 col-md-offset-1">
                <ul>
                  <li><a href="//www.uams.edu">UAMS home page</a></li>
                  <li><a href="//www.uamshealth.com/">UAMSHealth home page</a></li>
                  <li><a href="//www.uamshealth.com/maps/">Maps</a></li>
                  <li><a href="//www.uamshealth.com/news/">UAMS Today</a></li>
                </ul>
              </div>
            </div>

          </div>
        </div>

      </div>

  </div>

</div>

<?php get_footer(); ?>
