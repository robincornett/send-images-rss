<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       Sends full size images instead of thumbs to RSS and MailChimp
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           1.2.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'the_excerpt_rss', 'change_feed_images', 20 );
add_filter( 'the_content_feed', 'change_feed_images', 20 );

function change_feed_images( $content ) {
	if ( is_feed() ) {
		$content = '<div>' . $content . '</div>'; // set up something you can scan
		$content = preg_replace( '(-\d{3,4}x\d{3,4})', '', $content );
		$doc     = new DOMDocument();
		$doc->LoadHTML( $content );
		$images  = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$image->removeAttribute( 'height' );
			$image->setAttribute( 'width', '100%' ); // comment out for smaller image
			$image->setAttribute( 'style', 'max-width:560px;'); // comment out for smaller image

			/* It's possible to use the same technique to set up smaller images for your emails, with alignment.
			 * To use this, comment out lines 30-31, and uncomment lines 37-39.
			 */

			//$image->setAttribute( 'width', '250' ); // uncomment if you want a smaller image
			//$image->setAttribute( 'align', 'right' ); // uncomment if you want a smaller image
			//$image->setAttribute( 'style', 'margin:0px 0px 10px 10px;' );
		}
		// Strips extra added by line 23
		$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
	}

	return $content;
}
