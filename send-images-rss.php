<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       Sends full size images instead of thumbs to RSS and MailChimp
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           1.0.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'the_excerpt_rss', 'change_feed_images', 20 );
add_filter( 'the_content_feed', 'change_feed_images', 20 );
function change_feed_images( $content ) {
	if ( is_feed() ) {
		$content = '<div>' . $content . '</div>';
		$content = preg_replace( '(-\d{3,4}x\d{3,4})', '', $content );
		$doc     = new DOMDocument();
		$doc->LoadXML( $content );
		$images  = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$image->removeAttribute( 'height' );
			$image->setAttribute( 'width', '560px' );
			//$image->setAttribute( 'width', '250px' ); // uncomment if you want a smaller image
			//$image->setAttribute( 'align', 'right;' ); //uncomment if you want a smaller image with alignment instead
		}
	// Strip weird DOCTYPE that DOMDocument() adds in
	$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
	}
	// Send the content on its way
	return $content;
}
