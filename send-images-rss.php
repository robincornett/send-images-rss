<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       Sends full size images instead of thumbs to RSS and MailChimp
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           2.0.0beta
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// add an email/feed specific image size.
add_image_size( 'mailchimp', 560 );

add_filter( 'the_excerpt_rss', 'send_rss_change_images', 20 );
add_filter( 'the_content_feed', 'send_rss_change_images', 20 );

/*
 * get the ID of each image dynamically
 * http://pippinsplugins.com/retrieve-attachment-id-from-image-url/
 * @author Pippin Williamson
 */
function send_images_get_image_id( $image_url ) {
	global $wpdb;
	$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );

	return $attachment[0];
}

function send_rss_change_images( $content ) {
	$content = '<div>' . $content . '</div>'; // set up something you can scan

	if ( shortcode_exists( 'gravityform' ) ) {
		remove_shortcode( 'gravityform' ); // because gravity forms explode the feed
	}

	$content = preg_replace( '(-\d{3,4}x\d{3,4})', '', $content );
	$doc     = new DOMDocument();

	//* Load up the document, hopefully cleanly, but otherwise, still load.
	libxml_use_internal_errors( true ); // turn off errors for HTML5
	if ( function_exists( 'mb_convert_encoding') ) {
		$doc->LoadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8') ); // convert the feed from XML to HTML
	}
	elseif ( function_exists( 'iconv' ) ) {
		$doc->LoadHTML( iconv( 'UTF-8', 'ISO-8859-1//IGNORE', $content ) );
	}
	else {
		$doc->LoadHTML( $content );
	}
	libxml_clear_errors(); // now that it's loaded, go ahead

	// remove inline style (width) from XHTML captions
	$captions = $doc->getElementsByTagName( 'div' );
	foreach ( $captions as $caption ) {
		$caption->removeAttribute( 'style' );
	}
	// remove inline style (width) from HTML5 captions
	$figures = $doc->getElementsByTagName( 'figure' );
	foreach ( $figures as $figure ) {
		$figure->removeAttribute( 'style' );
	}
	$images  = $doc->getElementsByTagName( 'img' );
	foreach ( $images as $image ) {
		$image_url = $image->getAttribute( 'src' ); // get the image URL
		$image_id  = send_images_get_image_id( $image_url ); // use the image URL to get the image ID
		$mailchimp = image_downsize( $image_id, 'mailchimp' ); // retrieve the new MailChimp sized image

		$image->removeAttribute( 'height' );

		$image->setAttribute( 'src', $mailchimp[0] );
		$image->setAttribute( 'width', '100%' );

		/*
		 * Check the image's alignment in the post. If set to right or left, do the same in the feed.
		 * Otherwise, set the image to align center.
		 * Should be OK even with authors who do funny things with images (like full width, aligned left).
		 */
		$class = $image->getAttribute( 'class' );
		if ( strpos( $class, 'alignright' ) !== false ) {
			$image->setAttribute( 'align', 'right' );
			$image->setAttribute( 'style', 'margin:0px 0px 10px 10px;max-width:280px;' );
		}
		elseif ( strpos( $class, 'alignleft' ) !== false ) {
			$image->setAttribute( 'align', 'left' );
			$image->setAttribute( 'style', 'margin:0px 10px 10px 0px;max-width:280px;' );
		}
		else {
			$image->setAttribute( 'align', 'center' );
			$image->setAttribute( 'style', 'max-width:560px;');
		}

	}
	// Strips extra added by line 37
	$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );

	return $content;
}
