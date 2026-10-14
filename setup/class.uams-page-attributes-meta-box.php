<?php

class UAMS_Page_Attributes_Meta_Box
{
    const ID       = 'pageparentdiv';
    const TITLE    = 'Page Attributes';
    const POSTTYPE = 'page';
    const POSITION = 'side';
    const PRIORITY = 'core';

    public $HIDDEN = array();

    public function __construct()
    {
        $this->HIDDEN = array( 'No Sidebar' );
        add_action( 'add_meta_boxes', array( $this, 'replace_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_postdata' ) );
        add_action( 'admin_head', array( $this, 'custom_style' ) );
    }

    public function replace_meta_box()
    {
        remove_meta_box( 'pageparentdiv', 'page', 'side' );
        add_meta_box( 'uamspageparentdiv', 'Page Attributes', array( $this, 'page_attributes_meta_box' ), 'page', 'side', 'core' );
    }

    public function page_attributes_meta_box( $post )
    {
        $post_type_object = get_post_type_object( $post->post_type );

        if ( $post_type_object && $post_type_object->hierarchical ) {
            $dropdown_args = array(
                'post_type'        => $post->post_type,
                'exclude_tree'     => $post->ID,
                'selected'         => $post->post_parent,
                'name'             => 'parent_id',
                'show_option_none' => __( '(no parent)' ),
                'sort_column'      => 'menu_order, post_title, sidebar, parent',
                'echo'             => 0,
            );

            $dropdown_args = apply_filters( 'page_attributes_dropdown_pages_args', $dropdown_args, $post );
            $pages         = wp_dropdown_pages( $dropdown_args );

            if ( ! empty( $pages ) ) {
                ?>
                <p><strong><?php _e( 'Parent' ); ?></strong></p>
                <label class="screen-reader-text" for="parent_id"><?php _e( 'Parent' ); ?></label>

                <?php 
                echo $pages;
                $parent = get_post_meta( $post->ID, 'parent', true );
                wp_nonce_field( 'parent_nonce', 'parent_name' );
                ?>

                <p><input type="checkbox" id="parent_id" name="parentcheck" value="on" <?php checked( ! empty( $parent ) ); ?> /><?php _e( 'Hide from menu' ); ?></p>
                <?php
            }
        }

        if ( 'page' === $post->post_type && 0 !== count( get_page_templates( $post ) ) ) {
            $template = ! empty( $post->page_template ) ? $post->page_template : 'default';
            ?>
            <p><strong><?php _e( 'Template' ); ?></strong></p>
            <label class="screen-reader-text" for="page_template"><?php _e( 'Page Template' ); ?></label>
            <?php $this->page_template_dropdown( $template, $post ); ?>
        <?php }

        $sidebar = get_post_meta( $post->ID, 'sidebar', true );
        wp_nonce_field( 'sidebar_nonce', 'sidebar_name' );
        $breadcrumb = get_post_meta( $post->ID, 'breadcrumb', true );
        wp_nonce_field( 'breadcrumb_nonce', 'breadcrumb_name' );
        ?>

        <p><strong><?php _e( 'Sidebar' ); ?></strong></p>
        <label class="screen-reader-text" for="sidebar"><?php _e( 'Sidebar' ); ?></label>
        <p><input type="checkbox" id="sidebar_id" name="sidebarcheck" value="on" <?php checked( ! empty( $sidebar ) ); ?> /><?php _e( 'No Sidebar' ); ?></p>

        <p><strong><?php _e( 'Breadcrumbs' ); ?></strong></p>
        <label class="screen-reader-text" for="breadcrumbs"><?php _e( 'Breadcrumbs' ); ?></label>
        <p><input type="checkbox" id="breadcrumb_id" name="breadcrumbcheck" value="on" <?php checked( ! empty( $breadcrumb ) ); ?> /><?php _e( 'Hide Breadcrumbs' ); ?></p>

        <p><strong><?php _e( 'Order' ); ?></strong></p>
        <p><label class="screen-reader-text" for="menu_order"><?php _e( 'Order' ); ?></label><input name="menu_order" type="text" size="4" id="menu_order" value="<?php echo esc_attr( $post->menu_order ); ?>" /></p>

        <p><?php if ( 'page' === $post->post_type ) _e( 'Need help? Use the Help tab in the upper right of your screen.' ); ?></p>
        <?php
    }

    public function page_template_dropdown( $default = '', $post = null )
    {
        $previews = array(
            'Big Hero'         => '/assets/images/template-big-hero.jpg',
            'Small Hero'       => '/assets/images/template-small-hero.jpg',
            'Home'             => '/assets/images/template-home.jpg',
            'No image'         => '/assets/images/template-no-image.jpg',
            'Blank'            => '/assets/images/template-no-title.jpg',
            'No title/image'   => '/assets/images/template-no-title.jpg',
            'Default Template' => '/assets/images/template-default.jpg',
        );

        $templates = get_page_templates( get_post() );
        ksort( $templates );

        echo "<div class='uams-admin-template'>";
        $checked = checked( $default, 'default', false );
        echo "<p><input type='radio' name='page_template' value='default' $checked >Default Template</input> (<a id='enchanced-preview' href='#'>preview<span><img src='" . esc_url( get_template_directory_uri() . $previews['Default Template'] ) . "' alt='' width='300px' height='' /></span></a>)</p>";

        foreach ( array_keys( $templates ) as $template ) {
            if ( in_array( $template, $this->HIDDEN, true ) ) {
                continue;
            }

            $checked = checked( $default, $templates[ $template ], false );
            $preview_img = array_key_exists( $template, $previews ) ? "(<a id='enchanced-preview' href='#'>preview<span><img src='" . esc_url( get_template_directory_uri() . $previews[ $template ] ) . "' alt='' width='300px' height='' /></span></a>)" : '';
            echo "<p><input type='radio' name='page_template' value='" . esc_attr( $templates[ $template ] ) . "' $checked >" . esc_html( $template ) . "</input> " . $preview_img . "</p>";
        }
        echo '</div>';
    }

    public function custom_style()
    {
        wp_enqueue_style( 'uams-admin-template', get_template_directory_uri() . '/assets/admin/css/uams.admin.template.css' );
    }

    public function save_postdata( $post_ID = 0 )
    {
        $post_ID   = (int) $post_ID;
        $post_type = get_post_type( $post_ID );

        if ( 'page' !== $post_type ) {
            return $post_ID;
        }

        if ( isset( $_POST['sidebar_name'] ) && check_admin_referer( 'sidebar_nonce', 'sidebar_name' ) ) {
            if ( isset( $_POST['sidebarcheck'] ) ) {
                update_post_meta( $post_ID, 'sidebar', sanitize_text_field( $_POST['sidebarcheck'] ) );
            } else {
                delete_post_meta( $post_ID, 'sidebar' );
            }
        }

        if ( isset( $_POST['breadcrumb_name'] ) && check_admin_referer( 'breadcrumb_nonce', 'breadcrumb_name' ) ) {
            if ( isset( $_POST['breadcrumbcheck'] ) ) {
                update_post_meta( $post_ID, 'breadcrumb', sanitize_text_field( $_POST['breadcrumbcheck'] ) );
            } else {
                delete_post_meta( $post_ID, 'breadcrumb' );
            }
        }

        if ( isset( $_POST['parent_name'] ) && check_admin_referer( 'parent_nonce', 'parent_name' ) ) {
            if ( isset( $_POST['parentcheck'] ) ) {
                update_post_meta( $post_ID, 'parent', sanitize_text_field( $_POST['parentcheck'] ) );
            } else {
                delete_post_meta( $post_ID, 'parent' );
            }
        }

        return $post_ID;
    }
}

new UAMS_Page_Attributes_Meta_Box();
