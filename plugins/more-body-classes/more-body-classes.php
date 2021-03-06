<?php
/*
 *  Plugin Name: More Body Classes
 *  Description: Add more meaningful classes to body.
 *  Author: Pasi Lallinaho
 *  Version: 1.0
 *  Author URI: http://open.knome.fi/
 *  Plugin URI: http://wordpress.knome.fi/
 *
 *  License: GNU General Public License v2 or later
 *  License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

add_filter( 'body_class', 'MoreBodyClasses' );

function MoreBodyClasses( $classes ) {
	$classes[] = "blogid-" . get_current_blog_id( );

	$this_url = parse_url( get_bloginfo( 'url' ) );
	$classes[] = " host-" . str_replace( ".", "-", $this_url['host'] );

	if( is_home( ) ) {
		$classes[] = " is-home";
	} else {
		$classes[] = " is-not-home";
	}

	if( get_current_blog_id( ) == 1 && is_multisite( ) ) {
		$classes[] = " is-multisite-front";
	}

	return $classes;
}

?>
