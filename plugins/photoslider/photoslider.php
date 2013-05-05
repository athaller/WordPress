<?php
/*
 *  Plugin Name: Photoslider
 *  Description: Show a slideshow of user uploaded photos.
 *  Author: Pasi Lallinaho
 *  Version: 1.4
 *  Author URI: http://open.knome.fi/
 *  Plugin URI: http://wordpress.knome.fi/
 *
 */

/*  FIXME: Add this to README
 *  Uses images uploaded to the current post. Available as widget and shortcode [picslide].
 */

/*  On plugin activation, create options for default values if needed
 *
 */

register_activation_hook( __FILE__, 'PhotosliderActivate' );

function PhotosliderActivate( ) {
	add_option( 'photoslider_default_size', 'medium' );
}

/*  Init plugin
 *
 */

add_action( 'plugins_loaded', 'PhotosliderInit' );

function PhotosliderInit( ) {
	/* Load text domain for i18n */
	load_plugin_textdomain( 'photoslider', false, dirname( plugin_basename( FILE ) ) . '/languages/' );
}

/*  Include scripts and default stylesheets
 *
 */

add_action( 'wp_enqueue_scripts', 'PhotosliderScripts' );

function PhotosliderScripts( ) {
	wp_enqueue_script( 'jquery' );

	wp_register_script( 'photoslider', plugins_url( 'slider.js', __FILE__ ), array ( 'jquery' ), '1.3' );
	wp_enqueue_script( 'photoslider' );

	wp_register_style( 'photoslider-defaults', plugins_url( 'defaults.css', __FILE__ ) );
	wp_enqueue_style( 'photoslider-defaults' );
}

/*  Widget
 *
 */

add_action( 'widgets_init', function( ) { register_widget( 'PhotosliderWidget' ); } );

class PhotosliderWidget extends WP_Widget {
	/** constructor */
	function __construct() {
		$widget_ops = array( 'description' => __( 'Show a slideshow of user uploaded photos.', 'photoslider' ) );

		parent::__construct( 'photoslider', _x( 'Photoslider', 'widget name', 'photoslider' ), $widget_ops );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$attachments = PhotosliderAttachments( $instance['post'] );
	
		echo $before_widget;
		if( $instance['title'] && $attachments ) { $title = $before_title . $title . $after_title; }
		if( $attachments ) { echo GetPhotoslider( $instance, $attachments, $title ); }
		echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['instance_id'] = strip_tags( $new_instance['instance_id'] );
		$instance['controls'] = strip_tags( $new_instance['controls'] );
		$instance['timeout'] = (int) $new_instance['timeout'];
		$instance['captions'] = (int) $new_instance['captions'];
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
		$size = esc_attr( $instance['size'] );

		if( !$size ) { $size = get_option( 'photoslider_default_size' ); }
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'photoslider' ); ?><br />
				<input style="width: 220px;" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Image size', 'photoslider' ); ?><br />
				<select class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>">
				<?php
					global $_wp_additional_image_sizes;

					$default_sizes['thumbnail'] = array( "width" => get_option( 'thumbnail_size_w' ), "height" => get_option( 'thumbnail_size_h' ), "crop" => get_option( 'thumbnail_crop' ) );
					$default_sizes['medium'] = array( "width" => get_option( 'medium_size_w' ), "height" => get_option( 'medium_size_h' ) );
					$default_sizes['large'] = array( "width" => get_option( 'large_size_w' ), "height" => get_option( 'large_size_h' ) );

					$image_sizes = array_merge( $default_sizes, (array) $_wp_additional_image_sizes );

					foreach( $image_sizes as $name => $attr ) {
						if( $attr['crop'] == 1 ) { $is_cropped = ", cropped"; } else { unset( $is_cropped ); }
						if( $instance['size'] == $name ) { $is_selected = ' selected="selected" '; } else { unset( $is_selected ); }

						print '<option value="' . $name . '"' . $is_selected . '>' . $name . " (" . $attr['width'] . "&times;" . $attr['height'] . $is_cropped . ")</option>";
					}
				?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'controls' ); ?>"><?php _e( 'Controls', 'photoslider' ); ?><br />
				<select class="widefat" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>">
					<?php
						$c_opt = array(
							"none" => _x( "None", "controls", "photoslider" ),
							"above" => _x( "Above", "controls", "photoslider" ),
							"ontop" => _x( "On top", "controls", "photoslider" )
						);
						foreach( $c_opt as $id => $name ) {
							if( $id == $instance['controls'] ) { $is_selected = ' selected="selected " '; } else { unset( $is_selected ); }
							print '<option value="' . $id . '"' . $is_selected . '>' . $name . '</option>';
						}
					?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'timeout' ); ?>"><?php _e( 'Time between transitions', 'photoslider' ); ?><br />
				<select class="widefat" id="<?php echo $this->get_field_id( 'timeout' ); ?>" name="<?php echo $this->get_field_name( 'timeout' ); ?>">
					<?php
						$to_opt = array(
							"0" => __( "Don't advance automatically", "photoslider" ),
							"6500" => _x( "Fast", "transition speed", "photoslider" ),
							"9000" => _x( "Default", "transition speed", "photoslider" ),
							"12000" => _x( "Slow", "transition speed", "photoslider" )
						);
						foreach( $to_opt as $id => $name ) {
							if( $id == $instance['timeout'] ) { $is_selected = ' selected="selected " '; } else { unset( $is_selected ); }
							print '<option value="' . $id . '"' . $is_selected . '>' . $name . '</option>';
						}
					?>
				</select>
			</label>
		</p>
		<p>
			<?php _e( 'Show captions?', 'photoslider' ); ?><br />
			<?php if( $instance['captions'] < 1 ) { $capt_no = ' checked="checked" '; } else { $capt_yes = ' checked="checked" '; } ?>
			<input type="radio" id="<?php echo $this->get_field_id( 'captions_yes' ); ?>" name="<?php echo $this->get_field_name( 'captions' ); ?>" value="1" <?php echo $capt_yes; ?> />
			<label for="<?php echo $this->get_field_id( 'captions_yes' ); ?>"><?php _e( "Yes", "photoslider" ); ?></label>&nbsp;&nbsp;&nbsp;
			<input type="radio" id="<?php echo $this->get_field_id( 'captions_no' ); ?>" name="<?php echo $this->get_field_name( 'captions' ); ?>" value="0" <?php echo $capt_no; ?> />
			<label for="<?php echo $this->get_field_id( 'captions_no' ); ?>"><?php _e( "No", "photoslider" ); ?></label>
		</p>

		<input type="hidden" name="<?php echo $this->get_field_name( 'instance_id' ); ?>" value="<?php echo uniqid( 'photoslider_' ); ?>" />

		<?php 
	}
}

/*  Add shortcode
 *
 */

add_shortcode( 'photoslider', 'PhotosliderShortcode' );

function PhotosliderShortcode( $atts, $content, $code ) {
	$slider_opts = shortcode_atts( array(
		'size' => 'medium',
		'controls' => 'none',
		'timeout' => 8000,
		'captions' => 'no',
		'post' => 0,
	), $atts );

	$slider_opts['instance_id'] = uniqid( 'photoslider_' );

	$attachments = PhotosliderAttachments( $atts['post'] );
	if( $attachments ) { $out = GetPhotoslider( $slider_opts, $attachments ); }

	return $out;
}

/*  Function that actually outputs the sliders
 *
 */

Function GetPhotoslider( $opts, $attachments, $title ) {
	/* determine photo size */
	if( !$opts['size'] ) { $opts['size'] = get_option( 'photoslider_default_size' ); }

	/* determine exact dimensions for first photo for non-js users */
	$first_item = array_shift( array_values( $attachments ) );
	$first_attr = wp_get_attachment_image_src( $first_item->ID, $opts['size'] );
	$first_dmns = 'style="width: ' . $first_attr[1] . 'px; height: ' . $first_attr[2] . 'px;"';

	/* start wrapping div */
	$output = '<div class="ps_wrap" ' . $first_dmns . '>';
	$output .= '<div class="photoslider ctrl-' . $opts['controls'] . '" id="' . $opts['instance_id'] . '">';

		$output .= '<div class="title">' . $title . '</div>';

		$output .= '<ul>';

		$is_first = TRUE;
		foreach( $attachments as $a ) {
			if( $is_first ) {
				$output .= '<li class="first active">';
				$is_first = FALSE;
			} else {
				$output .= '<li>';
			}

			$output .= '<div class="image">';
			$output .= wp_get_attachment_image( $a->ID, $opts['size'] );
			$output .= '</div>';

			$output .= '<div class="captions">';
			if( $opts['captions'] == 1 ) {
				$output .= '<p>' . $a->post_title . '</p>';
				if( $a->post_content ) {
					$output .= '<p>' . $a->post_content  . '</p>';
				}
			}
			$output .= '</div>';

			$output .= '</li>';
		}

		$output .= '</ul>';

		$output .= '<div class="controls">';
		if( $opts['controls'] != "none" ) {
			$output .= '<a href="#" class="c-prev">&laquo;</a>';
			$output .= '<a href="#" class="c-next">&raquo;</a>';
		}
		$output .= '</div>';

	$output .= '</div>';
	$output .= '</div>';

	$output .= PhotosliderScriptsDynamic( $opts );

	return $output;
}

/*  Add a helper function that writes per-slider options
 *
 */

function PhotosliderScriptsDynamic( $args ) {
	$out  = '<script type="text/javascript">';
	$out .= 'jQuery( window ).load( function( ) {';

	$out .= 'var ' . $args['instance_id'] . ' = ' . "\n";
	$out .= '{ "id": "' . $args['instance_id'] . '", "controls": "' . $args['controls'] . '", "timeout": "' . $args['timeout'] . '" } ' . "\n";
	$out .= '; ';

	$out .= 'runPhotoslider( ' . $args['instance_id'] . ' );';

	$out .= '} );';
	$out .= '</script>';

	return $out;
}

/*  Add a helper function that determines the attachments wanted for a slider
 *
 */

function PhotosliderAttachments( $input ) {
	if( is_front_page( ) && ( !$input || $input < 1 ) ) {
		$post_id = get_option( 'page_on_front' );
	} elseif( $input > 0 ) {
		$post_id = $input;
	} else {
		global $post;
		$post_id = $post->ID;
	}

	$args = array(
		'post_parent' => $post_id,
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
		'order_by' => 'menu_order'
	);

	$attachments = get_children( $args );

	return $attachments;
}

?>
