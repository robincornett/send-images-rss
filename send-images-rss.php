<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       Sends full size images instead of thumbs to RSS and MailChimp
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           2.1.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// add an email/feed specific image size.
add_image_size( 'mailchimp', 560 );

add_filter( 'the_content', 'send_rss_scan_gallery', 19 );
function send_rss_scan_gallery( $content ) {
	global $post;
	if ( is_feed() && has_shortcode( $post->post_content, 'gallery' ) ) {
		$content = preg_replace( '(-\d{3,4}x\d{3,4})', '', $content );
		$content = preg_replace( '(width="\d{2,3}")', '', $content );
	}
	return $content;
}

add_filter( 'the_content', 'send_rss_change_images', 20 ); // using the_content because it's less fragile
function send_rss_change_images( $content ) {
	if ( is_feed() ) {
		$content = '<div>' . $content . '</div>'; // set up something you can scan

		$doc     = new DOMDocument();
		//* Load up the document, hopefully cleanly, but otherwise, still load.
		libxml_use_internal_errors( true ); // turn off errors for HTML5
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$doc->LoadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) ); // convert the feed from XML to HTML
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

		// Now work on the images, which is why we're really here.
		$images  = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$image_url = $image->getAttribute( 'src' ); // get the image URL
			$image_id  = send_rss_get_image_id( $image_url ); // use the image URL to get the image ID
			$mailchimp = wp_get_attachment_image_src( $image_id, 'mailchimp' ); // retrieve the new MailChimp sized image

			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'style' );

			// use the MailChimp size image if it exists.
			if ( isset( $mailchimp[3] ) && $mailchimp[3] ) {
				$image->setAttribute( 'src', $mailchimp[0] ); // use the MC size image for source
				$image->setAttribute( 'width', '560' );
				$image->setAttribute( 'align', 'center' );
			}

			/*
			 * If there is no MailChimp sized image, check alignment.
			 * If set to right or left, do the same in the feed for small images.
			 * Otherwise, align images to center.
			 * Should be OK even with authors who do funny things with images (like full width, aligned left).
			 */
			else {
				$class = $image->getAttribute( 'class' );
				$width = $image->getAttribute( 'width' );

				// first check: only images in [gallery] should have had the width stripped out
				if ( empty( $width ) ) {
					$original_width = wp_get_attachment_image_src( $image_id, 'original' );
					$image->setAttribute( 'width', $original_width[1] );
				}
				// now, if it's a small image, aligned right
				if ( ( strpos( $class, 'alignright' ) !== false ) && ( $width < '560' ) ) {
					$image->setAttribute( 'align', 'right' );
					$image->setAttribute( 'style', 'margin:0px 0px 10px 10px;max-width:280px;' );
				}
				// or if it's a small image, aligned left
				elseif ( ( strpos( $class, 'alignleft' ) !== false ) && ( $width < '560' ) ) {
					$image->setAttribute( 'align', 'left' );
					$image->setAttribute( 'style', 'margin:0px 10px 10px 0px;max-width:280px;' );
				}
				// otherwise, what's left are large images which still have width (therefore not in a [gallery])
				else {
					$image->setAttribute( 'align', 'center' );
					$image->setAttribute( 'style', 'max-width:560px;' );
				}
			}

		}
		// Strips extra added by line 38
		$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
	}
	return $content;

}

/**
 * get the ID of each image dynamically
 * http://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
 * @author Philip Newcomer
 */
function send_rss_get_image_id( $attachment_url = '' ) {

	global $wpdb;
	$attachment_id = false;

	// If there is no url, return.
	if ( '' == $attachment_url ) {
		return;
	}

	// Get the upload directory paths
	$upload_dir_paths = wp_upload_dir();

	// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
	if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

		// If this is the URL of an auto-generated thumbnail, get the URL of the original image
		$attachment_url = preg_replace( '(-\d{3,4}x\d{3,4})', '', $attachment_url );

		// Remove the upload path base directory from the attachment URL
		$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

		// Finally, run a custom database query to get the attachment ID from the modified attachment URL
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );

	}

	return $attachment_id;
}
