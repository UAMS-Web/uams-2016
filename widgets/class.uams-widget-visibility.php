<?php

//
// Hide or show widgets conditionally.
//
// Forked from the JetPack plugin.
class UAMS_Widget_Conditions
{
	public function __construct()
	{
		if ( is_admin() ) {
			add_action( 'sidebar_admin_setup', array( __CLASS__, 'widget_admin_setup' ) );
			add_filter( 'widget_update_callback', array( __CLASS__, 'widget_update' ), 10, 3 );
			add_action( 'in_widget_form', array( __CLASS__, 'widget_conditions_admin' ), 10, 3 );
			add_action( 'wp_ajax_widget_conditions_options', array( __CLASS__, 'widget_conditions_options' ) );
		} else {
			add_action( 'widget_display_callback', array( __CLASS__, 'filter_widget' ) );
			add_action( 'sidebars_widgets', array( __CLASS__, 'sidebars_widgets' ) );
		}
	}

	public static function widget_admin_setup()
	{
		wp_enqueue_style( 'widget-conditions', get_template_directory_uri() . '/assets/admin/css/uams.widget-conditions.css' );
		wp_enqueue_script( 'widget-conditions', get_template_directory_uri() . '/assets/admin/js/uams.widget-conditions.js', array( 'jquery', 'jquery-ui-core' ), '20140422', true );
		wp_enqueue_style( 'widget-cards', get_template_directory_uri() . '/assets/admin/css/uams.widgets-misc.css' );
	}

	public static function widget_conditions_options_echo( $major = '', $minor = '' )
	{
		switch ( $major ) {
			case 'category':
				?>
				<option value=""><?php _e( 'All category pages', 'jetpack' ); ?></option>
				<?php
				$categories = get_categories( array( 'number' => 1000, 'orderby' => 'count', 'order' => 'DESC' ) );
				usort( $categories, array( __CLASS__, 'strcasecmp_name' ) );

				foreach ( $categories as $category ) {
					?>
					<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $category->term_id, $minor ); ?>><?php echo esc_html( $category->name ); ?></option>
					<?php
				}
				break;

			case 'author':
				?>
				<option value=""><?php _e( 'All author pages', 'jetpack' ); ?></option>
				<?php
				foreach ( get_users( array( 'orderby' => 'name', 'exclude_admin' => true ) ) as $author ) {
					?>
					<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $author->ID, $minor ); ?>><?php echo esc_html( $author->display_name ); ?></option>
					<?php
				}
				break;

			case 'tag':
				?>
				<option value=""><?php _e( 'All tag pages', 'jetpack' ); ?></option>
				<?php
				$tags = get_tags( array( 'number' => 1000, 'orderby' => 'count', 'order' => 'DESC' ) );
				usort( $tags, array( __CLASS__, 'strcasecmp_name' ) );

				foreach ( $tags as $tag ) {
					?>
					<option value="<?php echo esc_attr( $tag->term_id ); ?>" <?php selected( $tag->term_id, $minor ); ?>><?php echo esc_html( $tag->name ); ?></option>
					<?php
				}
				break;

			case 'date':
				?>
				<option value="" <?php selected( '', $minor ); ?>><?php _e( 'All date archives', 'jetpack' ); ?></option>
				<option value="day" <?php selected( 'day', $minor ); ?>><?php _e( 'Daily archives', 'jetpack' ); ?></option>
				<option value="month" <?php selected( 'month', $minor ); ?>><?php _e( 'Monthly archives', 'jetpack' ); ?></option>
				<option value="year" <?php selected( 'year', $minor ); ?>><?php _e( 'Yearly archives', 'jetpack' ); ?></option>
				<?php
				break;

			case 'page':
				if ( ! $minor ) {
					$minor = 'post_type-page';
				} elseif ( 'post' === $minor ) {
					$minor = 'post_type-post';
				}
				?>
				<option value="front" <?php selected( 'front', $minor ); ?>><?php _e( 'Front page', 'jetpack' ); ?></option>
				<option value="posts" <?php selected( 'posts', $minor ); ?>><?php _e( 'Posts page', 'jetpack' ); ?></option>
				<option value="archive" <?php selected( 'archive', $minor ); ?>><?php _e( 'Archive page', 'jetpack' ); ?></option>
				<option value="404" <?php selected( '404', $minor ); ?>><?php _e( '404 error page', 'jetpack' ); ?></option>
				<option value="search" <?php selected( 'search', $minor ); ?>><?php _e( 'Search results', 'jetpack' ); ?></option>
				<optgroup label="<?php esc_attr_e( 'Post type:', 'jetpack' ); ?>">
					<?php
					$post_types = get_post_types( array( 'public' => true ), 'objects' );
					foreach ( $post_types as $post_type ) {
						?>
						<option value="<?php echo esc_attr( 'post_type-' . $post_type->name ); ?>" <?php selected( 'post_type-' . $post_type->name, $minor ); ?>><?php echo esc_html( $post_type->labels->singular_name ); ?></option>
						<?php
					}
					?>
				</optgroup>
				<optgroup label="<?php esc_attr_e( 'Static page:', 'jetpack' ); ?>">
					<?php
					echo str_replace( ' value="' . esc_attr( $minor ) . '"', ' value="' . esc_attr( $minor ) . '" selected="selected"', preg_replace( '/<\/?select[^>]*?>/i', '', wp_dropdown_pages( array( 'echo' => false ) ) ) );
					?>
				</optgroup>
				<?php
				break;

			case 'taxonomy':
				?>
				<option value=""><?php _e( 'All taxonomy pages', 'jetpack' ); ?></option>
				<?php
				$taxonomies = get_taxonomies( array( '_builtin' => false ), 'objects' );
				usort( $taxonomies, array( __CLASS__, 'strcasecmp_name' ) );

				foreach ( $taxonomies as $taxonomy ) {
					?>
					<optgroup label="<?php esc_attr_e( $taxonomy->labels->name . ':', 'jetpack' ); ?>">
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $taxonomy->name, $minor ); ?>><?php echo 'All ' . esc_html( $taxonomy->name ) . ' pages'; ?></option>
						<?php
						$terms = get_terms( array( 'taxonomy' => $taxonomy->name, 'number' => 250, 'hide_empty' => false ) );
						if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
							foreach ( $terms as $term ) {
								?>
								<option value="<?php echo esc_attr( $taxonomy->name . '_tax_' . $term->term_id ); ?>" <?php selected( $taxonomy->name . '_tax_' . $term->term_id, $minor ); ?>><?php echo esc_html( $term->name ); ?></option>
								<?php
							}
						}
						?>
					</optgroup>
					<?php
				}
				break;
		}
	}

	public static function widget_conditions_options()
	{
		$major = isset( $_REQUEST['major'] ) ? sanitize_text_field( $_REQUEST['major'] ) : '';
		$minor = isset( $_REQUEST['minor'] ) ? sanitize_text_field( $_REQUEST['minor'] ) : '';
		self::widget_conditions_options_echo( $major, $minor );
		wp_die();
	}

	public static function widget_conditions_admin( $widget, $return, $instance )
	{
		$conditions = ( isset( $instance['conditions'] ) && is_array( $instance['conditions'] ) ) ? $instance['conditions'] : array();

		if ( ! isset( $conditions['action'] ) ) {
			$conditions['action'] = 'show';
		}

		if ( empty( $conditions['rules'] ) || ! is_array( $conditions['rules'] ) ) {
			$conditions['rules'] = array( array( 'major' => '', 'minor' => '' ) );
		}

		$is_visible = ! empty( $_POST['widget-conditions-visible'] );
		?>
		<div class="widget-conditional <?php echo $is_visible ? '' : 'widget-conditional-hide'; ?>">
			<input type="hidden" name="widget-conditions-visible" value="<?php echo $is_visible ? '1' : '0'; ?>" />
			<?php if ( ! isset( $_POST['widget-conditions-visible'] ) ) : ?>
				<a href="#" class="button display-options"><?php _e( 'Visibility', 'jetpack' ); ?></a>
			<?php endif; ?>
			<div class="widget-conditional-inner">
				<div class="condition-top">
					<?php printf( _x( '%s if:', 'placeholder: dropdown menu to select widget visibility; hide if or show if', 'jetpack' ), '<select name="conditions[action]"><option value="show" ' . selected( $conditions['action'], 'show', false ) . '>' . esc_html_x( 'Show', 'Used in the "%s if:" translation for the widget visibility dropdown', 'jetpack' ) . '</option><option value="hide" ' . selected( $conditions['action'], 'hide', false ) . '>' . esc_html_x( 'Hide', 'Used in the "%s if:" translation for the widget visibility dropdown', 'jetpack' ) . '</option></select>' ); ?>
				</div>

				<div class="conditions">
					<?php
					foreach ( $conditions['rules'] as $rule ) {
						$rule_major = $rule['major'] ?? '';
						$rule_minor = $rule['minor'] ?? '';
						?>
						<div class="condition">
							<div class="alignleft">
								<select class="conditions-rule-major" name="conditions[rules_major][]">
									<option value="" <?php selected( '', $rule_major ); ?>><?php echo esc_html_x( '-- Select --', 'Used as the default option in a dropdown list', 'jetpack' ); ?></option>
									<option value="category" <?php selected( 'category', $rule_major ); ?>><?php esc_html_e( 'Category', 'jetpack' ); ?></option>
									<option value="author" <?php selected( 'author', $rule_major ); ?>><?php echo esc_html_x( 'Author', 'Noun, as in: "The author of this post is..."', 'jetpack' ); ?></option>
									<option value="tag" <?php selected( 'tag', $rule_major ); ?>><?php echo esc_html_x( 'Tag', 'Noun, as in: "This post has one tag."', 'jetpack' ); ?></option>
									<option value="date" <?php selected( 'date', $rule_major ); ?>><?php echo esc_html_x( 'Date', 'Noun, as in: "This page is a date archive."', 'jetpack' ); ?></option>
									<option value="page" <?php selected( 'page', $rule_major ); ?>><?php echo esc_html_x( 'Page', 'Example: The user is looking at a page, not a post.', 'jetpack' ); ?></option>
									<?php if ( get_taxonomies( array( '_builtin' => false ) ) ) : ?>
										<option value="taxonomy" <?php selected( 'taxonomy', $rule_major ); ?>><?php echo esc_html_x( 'Taxonomy', 'Noun, as in: "This post has one taxonomy."', 'jetpack' ); ?></option>
									<?php endif; ?>
								</select>
								<?php _ex( 'is', 'Widget Visibility: {Rule Major [Page]} is {Rule Minor [Search results]}', 'jetpack' ); ?>
								<select class="conditions-rule-minor" name="conditions[rules_minor][]" <?php echo empty( $rule_major ) ? 'disabled="disabled"' : ''; ?> data-loading-text="<?php esc_attr_e( 'Loading...', 'jetpack' ); ?>">
									<?php self::widget_conditions_options_echo( $rule_major, $rule_minor ); ?>
								</select>
								<span class="condition-conjunction"><?php echo esc_html_x( 'or', 'Shown between widget visibility conditions.', 'jetpack' ); ?></span>
							</div>
							<div class="condition-control alignright">
								<a href="#" class="delete-condition"><?php esc_html_e( 'Delete', 'jetpack' ); ?></a> | <a href="#" class="add-condition"><?php esc_html_e( 'Add', 'jetpack' ); ?></a>
							</div>
							<br class="clear" />
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	public static function widget_update( $instance, $new_instance, $old_instance )
	{
		if ( ! isset( $_POST['conditions']['action'], $_POST['conditions']['rules_major'] ) || ! is_array( $_POST['conditions']['rules_major'] ) ) {
			return $instance;
		}

		$conditions          = array();
		$conditions['action'] = sanitize_text_field( $_POST['conditions']['action'] );
		$conditions['rules']  = array();

		foreach ( $_POST['conditions']['rules_major'] as $index => $major_rule ) {
			if ( empty( $major_rule ) ) {
				continue;
			}

			$conditions['rules'][] = array(
				'major' => sanitize_text_field( $major_rule ),
				'minor' => isset( $_POST['conditions']['rules_minor'][ $index ] ) ? sanitize_text_field( $_POST['conditions']['rules_minor'][ $index ] ) : '',
			);
		}

		if ( ! empty( $conditions['rules'] ) ) {
			$instance['conditions'] = $conditions;
		} else {
			unset( $instance['conditions'] );
		}

		if (
			( isset( $instance['conditions'] ) && ! isset( $old_instance['conditions'] ) )
			|| ( isset( $instance['conditions'], $old_instance['conditions'] ) && serialize( $instance['conditions'] ) !== serialize( $old_instance['conditions'] ) )
		) {
			do_action( 'widget_conditions_save' );
		} elseif ( ! isset( $instance['conditions'] ) && isset( $old_instance['conditions'] ) ) {
			do_action( 'widget_conditions_delete' );
		}

		return $instance;
	}

	public static function sidebars_widgets( $widget_areas )
	{
		$settings = array();

		foreach ( $widget_areas as $widget_area => $widgets ) {
			if ( empty( $widgets ) || 'wp_inactive_widgets' === $widget_area ) {
				continue;
			}

			foreach ( $widgets as $position => $widget_id ) {
				if ( preg_match( '/^(.+?)-(\d+)$/', $widget_id, $matches ) ) {
					$id_base       = $matches[1];
					$widget_number = (int) $matches[2];
				} else {
					$id_base       = $widget_id;
					$widget_number = null;
				}

				if ( ! isset( $settings[ $id_base ] ) ) {
					$settings[ $id_base ] = get_option( 'widget_' . $id_base );
				}

				if ( ! is_null( $widget_number ) ) {
					if ( isset( $settings[ $id_base ][ $widget_number ] ) && false === self::filter_widget( $settings[ $id_base ][ $widget_number ] ) ) {
						unset( $widget_areas[ $widget_area ][ $position ] );
					}
				} elseif ( ! empty( $settings[ $id_base ] ) && false === self::filter_widget( $settings[ $id_base ] ) ) {
					unset( $widget_areas[ $widget_area ][ $position ] );
				}
			}
		}

		return $widget_areas;
	}

	public static function filter_widget( $instance )
	{
		global $post, $wp_query;

		if ( empty( $instance['conditions'] ) || empty( $instance['conditions']['rules'] ) || ! is_array( $instance['conditions']['rules'] ) ) {
			return $instance;
		}

		$condition_result = false;

		foreach ( $instance['conditions']['rules'] as $rule ) {
			$major = $rule['major'] ?? '';
			$minor = $rule['minor'] ?? '';

			switch ( $major ) {
				case 'date':
					switch ( $minor ) {
						case '':
							$condition_result = is_date();
							break;
						case 'month':
							$condition_result = is_month();
							break;
						case 'day':
							$condition_result = is_day();
							break;
						case 'year':
							$condition_result = is_year();
							break;
					}
					break;

				case 'page':
					if ( 'post' === $minor ) {
						$minor = 'post_type-post';
					} elseif ( ! $minor ) {
						$minor = 'post_type-page';
					}

					switch ( $minor ) {
						case '404':
							$condition_result = is_404();
							break;
						case 'search':
							$condition_result = is_search();
							break;
						case 'archive':
							$condition_result = is_archive();
							break;
						case 'posts':
							$condition_result = isset( $wp_query->is_posts_page ) && $wp_query->is_posts_page;
							break;
						case 'home':
							$condition_result = is_home();
							break;
						case 'front':
							if ( current_theme_supports( 'infinite-scroll' ) ) {
								$condition_result = is_front_page();
							} else {
								$condition_result = is_front_page() && ! is_paged();
							}
							break;
						default:
							if ( 0 === strpos( $minor, 'post_type-' ) ) {
								$condition_result = is_singular( substr( $minor, 10 ) );
							} else {
								$condition_result = is_page( $minor );
							}
							break;
					}
					break;

				case 'tag':
					if ( ! $minor && is_tag() ) {
						$condition_result = true;
					} elseif ( is_singular() && $minor && has_tag( $minor ) ) {
						$condition_result = true;
					} else {
						$tag = get_tag( $minor );
						if ( $tag && is_tag( $tag->slug ) ) {
							$condition_result = true;
						}
					}
					break;

				case 'category':
					if ( ! $minor && is_category() ) {
						$condition_result = true;
					} elseif ( is_category( $minor ) ) {
						$condition_result = true;
					} elseif ( is_singular() && $minor && in_array( 'category', get_post_taxonomies(), true ) && has_category( $minor ) ) {
						$condition_result = true;
					}
					break;

				case 'author':
					if ( ! $minor && is_author() ) {
						$condition_result = true;
					} elseif ( $minor && is_author( $minor ) ) {
						$condition_result = true;
					} elseif ( is_singular() && $minor && isset( $post->post_author ) && (string) $minor === (string) $post->post_author ) {
						$condition_result = true;
					}
					break;

				case 'taxonomy':
					$term  = explode( '_tax_', $minor );
					$tax   = $term[0] ?? '';
					$term_id = $term[1] ?? '';
					$terms = ( isset( $post->ID ) && $tax ) ? get_the_terms( $post->ID, $tax ) : false;

					if ( $tax && $term_id && is_tax( $tax, (int) $term_id ) ) {
						$condition_result = true;
					} elseif ( is_singular() && $term_id && $tax && has_term( (int) $term_id, $tax ) ) {
						$condition_result = true;
					} elseif ( is_singular() && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$condition_result = true;
					}
					break;
			}

			if ( $condition_result ) {
				break;
			}
		}

		$action = $instance['conditions']['action'] ?? 'show';

		if ( ( 'show' === $action && ! $condition_result ) || ( 'hide' === $action && $condition_result ) ) {
			return false;
		}

		return $instance;
	}

	public static function strcasecmp_name( $a, $b )
	{
		return strcasecmp( $a->name, $b->name );
	}
}

new UAMS_Widget_Conditions();