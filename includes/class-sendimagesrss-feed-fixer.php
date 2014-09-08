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

		//$this->remove_caption_style( $doc ); // deprecated as of 2.5.0

		$this->modify_images( $doc );

		// Strip extra div added by new DOMDocument
		$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );

		return $content;
	}


	/**
	 * Try and load HTML as an XML document.
	 *
	 * @since 1.0.0
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
	 * @since 1.1.1
	 *
	 * @param DOMDocument $doc
	 *
	 * deprecated as of 2.5.0 ?
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
	 * Modify images in content.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since 1.1.1
	 *
	 * @param DOMDocument $doc
	 */
	protected function modify_images( DOMDocument &$doc ) {
		// Now work on the images, which is why we're really here.
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {

			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'style' );

			$this->replace_images( $image );
		}

	}

	/**
	 * Replace large images with new email sized images.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since 2.5.0
	 *
	 * @param $image
	 */
	protected function replace_images( $image ) {
		$image_url = $image->getAttribute( 'src' ); // get the image URL
		$image_id  = $this->get_image_id( $image_url ); // use the image URL to get the image ID
		$mailchimp = wp_get_attachment_image_src( $image_id, 'mailchimp' ); // retrieve the new MailChimp sized image

		// use the MailChimp size image if it exists.
		if ( isset( $mailchimp[3] ) && $mailchimp[3] ) {
			$image->parentNode->removeAttribute( 'style' );
			$image->setAttribute( 'src', $mailchimp[0] ); // use the MC size image for source
			$image->setAttribute( 'width', $mailchimp[1] );
			$image->setAttribute( 'align', 'center' );
		}

		else {
			$this->fix_other_images( $image );
			$this->fix_captions( $image );
		}
	}
	/**
	 * Modify images in content.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since 2.5.0
	 *
	 * @param $image
	 */
	protected function fix_other_images( $image ) {
		$class     = $image->getAttribute( 'class' );
		$width     = $image->getAttribute( 'width' );
		$maxwidth  = get_option( 'sendimagesrss_image_size', 560 );
		$halfwidth = floor( $maxwidth / 2 );
		$caption   = $image->parentNode->getAttribute( 'class' ); // to cover captions

		// first check: only images uploaded before plugin activation in [gallery] should have had the width stripped out
		if ( empty( $width ) ) {
			$original = wp_get_attachment_image_src( $image_id, 'original' );
			$image->setAttribute( 'width', $original[1] );
		}
		// now, if it's a small image, aligned right. since images with captions don't have alignment, we have to check the caption alignment also.
		if ( ( ( false !== strpos( $class, 'alignright' ) ) || ( false !== strpos( $caption, 'alignright' ) ) ) && ( $width < $maxwidth ) ) {
			$image->setAttribute( 'align', 'right' );
			$image->setAttribute( 'style', esc_attr( 'margin:0px 0px 10px 10px;max-width:' . $halfwidth . 'px;' ) );
		}
		// or if it's a small image, aligned left
		elseif ( ( ( false !== strpos( $class, 'alignleft' ) ) || ( false !== strpos( $caption, 'alignleft' ) ) ) && ( $width < $maxwidth ) ) {
			$image->setAttribute( 'align', 'left' );
			$image->setAttribute( 'style', esc_attr( 'margin:0px 10px 10px 0px;max-width:' . $halfwidth . 'px;' ) );
		}
		// now what's left are large images which don't have a MailChimp sized image, so set a max-width
		else {
			$image->setAttribute( 'align', 'center' );
			$image->setAttribute( 'style', esc_attr( 'max-width:' . $maxwidth . 'px;' ) );
		}

	}

	/**
	 * Modify captions in content.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since 2.5.0
	 *
	 * @param $image
	 */
	protected function fix_captions( $image ) {
		$width        = $image->getAttribute( 'width' );
		$maxwidth     = get_option( 'sendimagesrss_image_size', 560 );
		$halfwidth    = floor( $maxwidth / 2 );
		$caption      = $image->parentNode->getAttribute( 'class' ); // to cover captions
		$captionwidth = $image->parentNode->getAttribute( 'width' );

		// now one last check if there are captions O.o
		if ( false === strpos( $caption, 'wp-caption' ) ) {
			return; // theoretically, no caption, so skip forward and finish up.
		}
		else { // we has captions and have to deal with their mess.
			$image->parentNode->removeAttribute( 'style' );
			$image->parentNode->setAttribute( 'width', $width ); // sets caption width to same as image, which might be too large.

			// if it's a small image with a caption, aligned right
			if ( false !== strpos( $caption, 'alignright' ) && $captionwidth < $maxwidth ) {
				$image->parentNode->setAttribute( 'style', esc_attr( 'float:right;max-width:' . $halfwidth . 'px;' ) );
			}
			// or if it's a small image with a caption, aligned left
			elseif ( false !== strpos( $caption, 'alignleft' ) && $captionwidth < $maxwidth ) {
				$image->parentNode->setAttribute( 'style', esc_attr( 'float:left;max-width:' . $halfwidth . 'px;' ) );
			}
			// larger images with captions, aligned center
			elseif ( false !== strpos( $caption, 'aligncenter' ) ) {
				$image->parentNode->setAttribute( 'align', 'center' );
				$image->parentNode->setAttribute( 'style', esc_attr( 'max-width:' . $maxwidth . 'px;' ) );
			}
			// larger images with alignment that doesn't make sense to me would be what's left.
			else {
				$image->parentNode->setAttribute( 'style', esc_attr( 'max-width:' . $maxwidth . 'px;' ) );
			}
		}
	}


	/**
	 * Get the ID of each image dynamically.
	 *
	 * @since 2.1.0
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
