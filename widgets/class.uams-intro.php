<?php

//   #UAMS Horizontal Rule Widget
//   This widget styles custom intro text.

class UAMS_Intro_Text extends WP_Widget
{
    const ID          = 'uams-intro-text';
    const TITLE       = 'UAMS Intro Text';
    const DESCRIPTION = 'Italicized block of intro text.';

    private static $SHORTCODE_DEFAULTS = array();

    public function __construct()
    {
        add_shortcode( 'intro', array( $this, 'intro_shortcode' ) );

        parent::__construct(
            self::ID,
            __( self::TITLE, 'uams' ),
            array(
                'description' => __( self::DESCRIPTION, 'uams' ),
                'classname'   => self::ID,
            )
        );
    }

    public function widget( $args, $instance )
    {
        $intro_content = ! empty( $instance['introContent'] ) ? $instance['introContent'] : '';

        if ( empty( $intro_content ) ) {
            return;
        }

        $before_widget = $args['before_widget'] ?? '';
        $after_widget  = $args['after_widget'] ?? '';

        echo $before_widget;
        echo '<p class="intro">' . esc_html( $intro_content ) . '</p>';
        echo $after_widget;
    }

    public function update( $new_instance, $old_instance )
    {
        $instance                 = array();
        $instance['introContent'] = sanitize_text_field( $new_instance['introContent'] ?? '' );
        return $instance;
    }

    public function form( $instance )
    {
        $intro_content = isset( $instance['introContent'] ) ? esc_attr( $instance['introContent'] ) : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'introContent' ) ); ?>"><?php _e( 'Intro text:', 'uams' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'introContent' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'introContent' ) ); ?>" type="text" value="<?php echo $intro_content; ?>" />
        </p>
        <?php
    }

    public function intro_shortcode( $atts, $content = null )
    {
        $content = trim( (string) $content );
        return ! empty( $content ) ? sprintf( '<p class="intro">%s</p>', esc_html( $content ) ) : '';
    }
}

add_action( 'widgets_init', function() {
    register_widget( 'UAMS_Intro_Text' );
} );
