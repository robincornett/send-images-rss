<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       Sends full size images instead of thumbs to RSS and MailChimp
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           1.1.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'the_excerpt_rss', 'change_feed_images', 20 );
add_filter( 'the_content_feed', 'change_feed_images', 20 );

function change_feed_images( $content ) {
	if ( is_feed() ) {
		$content = preg_replace( '(-\d{3,4}x\d{3,4})', '', $content );
		$content = preg_replace( '(width="\d{3,4}")', 'width="100%"', $content );
		$content = preg_replace( '(height="\d{3,4}")', 'style="max-width:560;"', $content );
	}

	return $content;
}
