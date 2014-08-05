<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014 Robin Cornett
 * @license   GPL-2.0+
 */

/**
 * Fix content in a feed.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS_Feed_Fixer {
	/**
	 * Fix parts of a feed.
	 *
	 * This function is applied as a callback to the_content_filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Existing feed content.
	 *
	 * @return string Amended feed content.
	 */
	public function fix( $content ) {
		$content = '<div>' . $content . '</div>'; // set up something you can scan

		$doc = $this->load_html( $content );

		$this->remove_caption_style( $doc );

		// Now work on the images, which is why we're really here.
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$image_url = $image->getAttribute( 'src' ); // get the image URL
			$image_id  = $this->get_image_id( $image_url ); // use the image URL to get the image ID
			$mailchimp = wp_get_attachment_image_src( $image_id, 'mailchimp' ); // retrieve the new MailChimp sized image

			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'style' );

			// use the MailChimp size image if it exists.
			if ( isset( $mailchimp[3] ) && $mailchimp[3] ) {
				$image->setAttribute( 'src', $mailchimp[0] ); // use the MC size image for source
				$image->setAttribute( 'width', $mailchimp[1] );
				$image->setAttribute( 'align', 'center' );
			}

			/*
			 * If there is no MailChimp sized image, check alignment.
			 * If set to right or left, do the same in the feed for small images.
			 * Otherwise, align images to center.
			 * Should be OK even with authors who do funny things with images (like full width, aligned left).
			 */
			else {
				$class    = $image->getAttribute( 'class' );
				$width    = $image->getAttribute( 'width' );
				$maxwidth = esc_attr( get_option( 'sendimagesrss_image_size' ) );

				// first check: only images uploaded before plugin activation in [gallery] should have had the width stripped out
				if ( empty( $width ) ) {
					$original = wp_get_attachment_image_src( $image_id, 'original' );
					$image->setAttribute( 'width', $original[1] );
				}
				// now, if it's a small image, aligned right
				if ( ( false !== strpos( $class, 'alignright' ) ) && ( $width < $maxwidth ) ) {
					$image->setAttribute( 'align', 'right' );
					$image->setAttribute( 'style', 'margin:0px 0px 10px 10px;max-width:280px;' );
				}
				// or if it's a small image, aligned left
				elseif ( ( false !== strpos( $class, 'alignleft' ) ) && ( $width < $maxwidth ) ) {
					$image->setAttribute( 'align', 'left' );
					$image->setAttribute( 'style', 'margin:0px 10px 10px 0px;max-width:280px;' );
				}
				// now what's left are large images which don't have a MailChimp sized image, so set a max-width
				else {
					$image->setAttribute( 'align', 'center' );
					$image->setAttribute( 'style', 'max-width:' . $maxwidth . 'px;' );
				}
			}
		}

		// Strip extra div added by new DOMDocument
		$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );

		return $content;
	}

	/**
	 * Try and load HTML as an XML document.
	 *
	 * @since x.y.z
	 *
	 * @param string $content Feed content.
	 *
	 * @return DOMDocument
	 */
	protected function load_html( $content ) {
		$doc = new DOMDocument();

		// Populate the document, hopefully cleanly, but otherwise, still load.
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

		return $doc;
	}

	/**
	 * Remove caption styles.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since x.y.z
	 *
	 * @param DOMDocument $doc
	 */
	protected function remove_caption_style( DOMDocument &$doc ) {
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
	}

	/**
	 * Get the ID of each image dynamically.
	 *
	 * @since x.y.z
	 *
	 * @author Philip Newcomer
	 * @link   http://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
	 */
	protected function get_image_id( $attachment_url ) {
		global $wpdb;
		$attachment_id = false;

		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();

		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$attachment_url = preg_replace( '(-\d{3,4}x\d{3,4}.)', '.', $attachment_url );

			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

			// Finally, run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );

		}

		return $attachment_id;
	}
}
