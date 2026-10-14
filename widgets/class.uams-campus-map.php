<?php

/**
 * UAMS Campus Map Widget
 */

class UAMS_Campus_Map extends WP_Widget
{
	const URL = '//maps.uams.edu/full-screen/?marker=';

	public function __construct()
	{
		parent::__construct(
			'uams-campus-map',
			__( 'UAMS Campus Map', 'uams' ),
			array(
				'description' => __( 'Show your building on the UAMS campus map.', 'uams' ),
				'classname'   => 'uams-widget-campus-map',
			)
		);
	}

	public function widget( $args, $instance )
	{
		$title        = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$buildingCode = apply_filters( 'uams_campus_map_buildingcode', $instance['buildingCode'] ?? '' );

		$content = '';

		if ( ! empty( $title ) ) {
			$before_title = $args['before_title'] ?? '<h3 class="widget-title">';
			$after_title  = $args['after_title'] ?? '</h3>';
			$content     .= $before_title . esc_html( $title ) . $after_title;
		}

		$content .= '<div class="uams-campus-map-widget">
					  <iframe id="map-widget" width="100%" height="365" src="' . esc_url( self::URL . $buildingCode ) . '" style="border:0" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" scrolling="no" class="hidden"></iframe>
					  <a href="' . esc_url( 'https://maps.uams.edu/maps/fullscreen/4/?marker=' . $buildingCode ) . '" target="_blank" rel="noopener noreferrer">View larger</a>
					</div>
					<script>
					jQuery(document).ready(function($){
						var iframe = document.querySelector("#map-widget");
						if (iframe) {
							iframe.addEventListener("load", function() {
								setTimeout(function(){ $("#map-widget").removeClass("hidden"); }, 2000);
							});
						}
					});
					</script>';

		echo $args['before_widget'] . $content . $args['after_widget'];
	}

	public function update( $new_instance, $old_instance )
	{
		$instance                 = array();
		$instance['title']        = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['buildingCode'] = sanitize_text_field( $new_instance['buildingCode'] ?? '' );

		return $instance;
	}

	public function form( $instance )
	{
		$title        = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$buildingCode = isset( $instance['buildingCode'] ) ? esc_attr( $instance['buildingCode'] ) : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'buildingCode' ) ); ?>"><?php _e( 'Building Code:', 'uams' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'buildingCode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'buildingCode' ) ); ?>">
				<option value="">Select ...</option>
				<option value="127" <?php selected( $buildingCode, '127' ); ?>>12th St. Clinic</option>
				<option value="116" <?php selected( $buildingCode, '116' ); ?>>Administration West (ADMINW)</option>
				<option value="117" <?php selected( $buildingCode, '117' ); ?>>Barton Research (BART)</option>
				<option value="118" <?php selected( $buildingCode, '118' ); ?>>Biomedical Research Center I (BMR1)</option>
				<option value="119" <?php selected( $buildingCode, '119' ); ?>>Biomedical Research Center II (BMR2)</option>
				<option value="120" <?php selected( $buildingCode, '120' ); ?>>Bioventures (BVENT)</option>
				<option value="121" <?php selected( $buildingCode, '121' ); ?>>Boiler House (BH)</option>
				<option value="122" <?php selected( $buildingCode, '122' ); ?>>Central Building (CENT)</option>
				<option value="123" <?php selected( $buildingCode, '123' ); ?>>College of Public Health (COPH)</option>
				<option value="124" <?php selected( $buildingCode, '124' ); ?>>Computer Building (COMP)</option>
				<option value="125" <?php selected( $buildingCode, '125' ); ?>>Cottage 3 (C3)</option>
				<option value="128" <?php selected( $buildingCode, '128' ); ?>>Distribution Center (DIST)</option>
				<option value="129" <?php selected( $buildingCode, '129' ); ?>>Donald W. Reynolds Institute on Aging (RIOA)</option>
				<option value="126" <?php selected( $buildingCode, '126' ); ?>>Ear Nose Throat (ENT)</option>
				<option value="131" <?php selected( $buildingCode, '131' ); ?>>Education Building South (EDS)</option>
				<option value="130" <?php selected( $buildingCode, '130' ); ?>>Education II (EDII)</option>
				<option value="132" <?php selected( $buildingCode, '132' ); ?>>Family Medical Center (FMC)</option>
				<option value="133" <?php selected( $buildingCode, '133' ); ?>>Freeway Medical Tower (FWAY)</option>
				<option value="134" <?php selected( $buildingCode, '134' ); ?>>Harvey and Bernice Jones Eye Institute (JEI)</option>
				<option value="135" <?php selected( $buildingCode, '135' ); ?>>Hospital (HOSP)</option>
				<option value="136" <?php selected( $buildingCode, '136' ); ?>>I. Dodd Wilson Education Building (IDW)</option>
				<option value="137" <?php selected( $buildingCode, '137' ); ?>>Jackson T. Stephens Spine Institute (JTSSI)</option>
				<option value="138" <?php selected( $buildingCode, '138' ); ?>>Magnetic Resonance Imaging (MRI)</option>
				<option value="139" <?php selected( $buildingCode, '139' ); ?>>Mediplex Apartments (1 unit) (MEDPX)</option>
				<option value="141" <?php selected( $buildingCode, '141' ); ?>>Outpatient Center (OPC)</option>
				<option value="142" <?php selected( $buildingCode, '142' ); ?>>Outpatient Diagnostic Center (OPDC)</option>
				<option value="143" <?php selected( $buildingCode, '143' ); ?>>Paint Shop &amp; Flammable Storage (PAINT)</option>
				<option value="144" <?php selected( $buildingCode, '144' ); ?>>PET (PET)</option>
				<option value="145" <?php selected( $buildingCode, '145' ); ?>>Physical Plant (PP)</option>
				<option value="146" <?php selected( $buildingCode, '146' ); ?>>Psychiatric Research Institute (PRI)</option>
				<option value="147" <?php selected( $buildingCode, '147' ); ?>>Radiation Oncology [ROC] (RADONC)</option>
				<option value="148" <?php selected( $buildingCode, '148' ); ?>>Residence Hall Complex (RHC)</option>
				<option value="149" <?php selected( $buildingCode, '149' ); ?>>Ricks Armory</option>
				<option value="150" <?php selected( $buildingCode, '150' ); ?>>Walker Annex (ANNEX)</option>
				<option value="151" <?php selected( $buildingCode, '151' ); ?>>Ward Tower (WARD)</option>
				<option value="152" <?php selected( $buildingCode, '152' ); ?>>West Central Energy Plant (WCEP)</option>
				<option value="153" <?php selected( $buildingCode, '153' ); ?>>Westmark (WESTM)</option>
				<option value="154" <?php selected( $buildingCode, '154' ); ?>>Winston K. Shorey Building (SHOR)</option>
				<option value="115" <?php selected( $buildingCode, '115' ); ?>>Winthrop P. Rockefeller Cancer Institute (WPRCI)</option>
				<option value="2" <?php selected( $buildingCode, '2' ); ?>>Parking Deck 1 Entrance</option>
				<option value="3" <?php selected( $buildingCode, '3' ); ?>>Parking Deck 2 Entrance</option>
				<option value="4" <?php selected( $buildingCode, '4' ); ?>>Parking Deck 3 Entrance</option>
				<option value="7" <?php selected( $buildingCode, '7' ); ?>>Outpatient Valet Parking</option>
				<option value="6" <?php selected( $buildingCode, '6' ); ?>>Stephens Institute Valet Parking</option>
			</select>
		</p>
		<?php
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Campus_Map' );
} );