<?php
/*
 *  Plugin Name: Compact & Chronological
 *  Description: Show month archive links in a compact view.
 *  Author: Pasi Lallinaho
 *  Version: 1.1
 *  Author URI: http://open.knome.fi/
 *  Plugin URI: http://wordpress.knome.fi/
 *
 */

/*  Init plugin
 *
 */

add_action( 'plugins_loaded', 'CompactChronoInit' );

function CompactChronoInit( ) {
	/* Load text domain for i18n */
	load_plugin_textdomain( 'compact-chronological', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/*
 *  Include default stylesheets
 *
 */

add_action( 'wp_enqueue_scripts', 'CompactChronoScripts' );

function CompactChronoScripts( ) {
	wp_register_style( 'compact-chrono-defaults', plugins_url( 'defaults.css', __FILE__ ) );
	wp_enqueue_style( 'compact-chrono-defaults' );
}


/*  Widget
 *
 */

add_action( 'widgets_init', function( ) { register_widget( 'CompactChronoWidget' ); } );

class CompactChronoWidget extends WP_Widget {
	/** constructor */
	function __construct() {
		$widget_ops = array( 'description' => __( 'Show month archive links in a compact view.', 'compact-chronological' ) );

		parent::__construct( 'compact-chronological', _x( 'Compact & Chronological', 'widget name', 'compact-chronological' ), $widget_ops );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if( $title ) { echo $before_title . $title . $after_title; }
		/* */
		global $wpdb, $wp_locale;

		$where = "WHERE post_type = 'post' AND post_status = 'publish'";
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY YEAR(post_date) DESC, MONTH(post_date) ASC";
		$archives = $wpdb->get_results( $query );

		if( is_array( $archives ) ) {
			print '<div class="compact_chrono group">';

			foreach( $archives as $current ) {
				$arch[$current->year][$current->month] = $current->posts;
				$ycounts[$current->year] += $current->posts;
			}

			$last_year = $archives['0']->year;
			$first_year = end( $archives )->year;

			for( $y = $last_year; $y >= $first_year; $y-- ) {
				if( $instance['split_rows'] == "on" ) {
					print '<ul class="split">';
				} else {
					print '<ul>';
				}
				print '<li class="year">';
				print '<a href="' . get_year_link( $y ) . '" title="' . $y . ": " . sprintf( _n( '%d topic', '%d topics', $ycounts[$y], 'compact-chronological' ), $ycounts[$y] ) . '">';
				print '<span class="name">' . $y . '</span>';
				print '</a>';
				print '</li>'; // intended space!
				for( $m = 1; $m <= 12; $m++ ) {
					if( $m > date( 'n' ) && $y >= date( 'Y' ) ) { $class = 'month future'; }
					elseif( $m == date( 'n' ) && $y == date( 'Y' ) ) { $class = 'month now'; }
					else { $class = 'month past'; }

					print '<li class="' . $class . '">';

					$month_name = ucfirst( strftime( "%B %Y", mktime( 0, 0, 0, $m, 10, $y ) ) );
					$month_abbr = ucfirst( strftime( "%b", mktime( 0, 0, 0, $m, 10, $y ) ) );

					if( $arch[$y][$m] < 1 ) {
						print substr( $month_abbr, 0, 3 );
					} else {
						print '<a href="' . get_month_link( $y, $m ) . '" title="' . $month_name . ": " . sprintf( _n( '%d topic', '%d topics', $arch[$y][$m], 'compact-chronological' ), $arch[$y][$m] ) . '">';
						print '<span class="name">' . substr( $month_abbr, 0, 3 ) . '</span>';
						if( $instance['article_counts'] == 1 ) {
							print '<span class="count">' . $arch[$y][$m] . '</span>';
						}
						print '</a>';
					}
					print '</li>';
				}
				print '</ul>';
			}

			print '</div>';
		}
		/* */
		echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['split_rows'] = $new_instance['split_rows'];

		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$title = esc_attr( $instance['title'] );

		if( $instance['split_rows'] == "on" ) { $is_split = "checked=\"checked\""; } else { unset( $is_split ); }

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'compact-chronological' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
			<label for="<?php echo $this->get_field_id( 'split_rows' ); ?>">
				<input id="<?php echo $this->get_field_id( 'split_rows' ); ?>" name="<?php echo $this->get_field_name( 'split_rows' ); ?>" type="checkbox" value="on" <?php echo $is_split; ?> />
				<?php echo _e( 'Split to two rows?', 'compact-chronological' ); ?>
			</label>
		</p>
		<?php 
	}
}

?>
