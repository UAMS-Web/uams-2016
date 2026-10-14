<h1><?php the_title(); ?></h1>

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
			if ( ! function_exists( 'text_cut' ) ) {
				function text_cut( $text, $length = 27, $dots = true ) {
					$text      = trim( preg_replace( '#[\s\n\r\t]{2,}#', ' ', (string) $text ) );
					$text_temp = $text;

					if ( strlen( $text ) > $length ) {
						while ( $length > 0 && substr( $text, $length, 1 ) !== ' ' ) {
							$length--;
						}
						$text = substr( $text, 0, $length );
					}

					return $text . ( ( $dots && $text !== '' && strlen( $text_temp ) > $length ) ? '...' : '' );
				}
			}

			echo esc_html( text_cut( get_the_title(), 27, true ) );
			?>

	  	</div>
	</button>
	<div id="mobile-sidebar-links" aria-hidden="true"><?php uams_sidebar_menu_mobile(); ?></div>
</div>

<?php the_content(); ?>
