<?php
/*
 *  Plugin Name: Links Widget Improved
 *  Description: Add a widget to show easy-clickable links with inline descriptions.
 *  Author: Pasi Lallinaho
 *  Version: 1.0
 *  Author URI: http://open.knome.fi/
 *  Plugin URI: http://wordpress.knome.fi/
 *
 *  License: GNU General Public License v2 or later
 *  License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

/*  Init plugin
 *
 */

add_action( 'plugins_loaded', 'LinksWImprovedInit' );

function LinksWImprovedInit( ) {
	/* Load text domain for i18n */
	load_plugin_textdomain( 'links-widget-improved', false, dirname( plugin_basename( FILE ) ) . '/languages/' );
}

/*  Widget
 *
 */

add_action( 'widgets_init', function( ) { register_widget( 'LinksImprovedWidget' ); } );

class LinksImprovedWidget extends WP_Widget {
	/** constructor */
	function __construct( ) {
		$widget_ops = array( 'description' => __( 'Show easy-clickable links with inline descriptions.', 'links-widget-improved' ) );

		parent::__construct( 'links-widget-improved', _x( 'Links (Improved)', 'widget name', 'links-widget-improved' ), $widget_ops );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$links = get_bookmarks( array( 'category' => $instance['category'] ) );

		if( is_array( $links ) ) {
			print '<div class="links-improved">';
			print $before_widget;
			$cat_info = get_term( $instance['category'], 'link_category' );
			print $before_title . $cat_info->name . $after_title;
			print '<ul>';
			foreach( $links as $link ) {
				print '<li><a href="' . $link->link_url . '">';
				print '<span class="name">' . $link->link_name . '</span><span class="description">' . $link->link_description . '</span>';
				print '</a></li>';
			}
			print '</ul>';
			print $after_widget;
			print '</div>';
		}
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['category'] = $new_instance['category'];
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$link_cats = get_terms( 'link_category' );
		$title = esc_attr( $instance['title'] );
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Link category:', 'links-widget-improved' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>">
				<option value=""><?php _ex( 'All link categories', 'widget option', 'links-widget-improved' ); ?></option>
				<?php
				foreach( $link_cats as $link_cat ) {
					echo '<option value="' . $link_cat->term_id . '" '	. selected( $instance['category'], $link_cat->term_id, false )	. '>' . $link_cat->name . "</option>\n";
				}
				?>
				</select>
			</p>
		<?php
	}
}
