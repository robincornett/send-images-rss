<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2015 Robin Cornett
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
	 * This function is applied as a callback to the_content filter.
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

		$this->modify_images( $doc );

		//* Strip extra div added by new DOMDocument
		if ( version_compare( PHP_VERSION, '5.3.6', '>=' ) ) {
			$content = substr( $doc->saveHTML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
		}
		else {
			$content = substr( $doc->saveXML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
		}

		return $content;
	}


	/**
	 * Try and load HTML as an HTML document with special characters, etc. intact.
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
		// best option due to special character handling
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$currentencoding = mb_internal_encoding();
			$content = mb_convert_encoding( $content, 'HTML-ENTITIES', $currentencoding ); // convert the feed from XML to HTML
		}
		// not sure this is an improvement over straight load (for special characters)
		elseif ( function_exists( 'iconv' ) ) {
			$currentencoding = iconv_get_encoding( 'internal_encoding' );
			$content = iconv( $currentencoding, 'ISO-8859-1//IGNORE', $content );
		}
		$doc->LoadHTML( $content );
		libxml_clear_errors(); // now that it's loaded, go ahead

		return $doc;
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
		$images  = $doc->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {

			$item = $this->get_image_variables( $image );

			// if the image is not part of WP, we cannot use it
			if ( false !== $item->image_id ) {
				$image->removeAttribute( 'height' );
				$image->removeAttribute( 'style' );

				$this->replace_images( $image );
			}
		}

	}


	/**
	 * Set variables for replace_images, fix_other_images, and fix_captions to use.
	 *
	 * Argument passed by reference, so no return needed.
	 *
	 * @since 2.5.0
	 *
	 * @param $image
	 */
	protected function get_image_variables( $image ) {
		$item            = new stdClass();
		$item->image_url = $image->getAttribute( 'src' );
		$item->image_id  = $this->get_image_id( $item->image_url ); // use the image URL to get the image ID
		$item->mailchimp = wp_get_attachment_image_src( $item->image_id, 'mailchimp' ); // retrieve the new MailChimp sized image
		$item->large     = wp_get_attachment_image_src( $item->image_id, 'large' ); // retrieve the large image size
		$item->caption   = $image->parentNode->getAttribute( 'class' ); // to cover captions
		$item->class     = $image->getAttribute( 'class' );
		$item->width     = $image->getAttribute( 'width' );
		$item->maxwidth  = get_option( 'sendimagesrss_image_size', 560 );
		$item->halfwidth = floor( $item->maxwidth / 2 );

		return $item;
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

		$item            = $this->get_image_variables( $image );
		$mailchimp_check = isset( $item->mailchimp[3] ) && $item->mailchimp[3];
		$large_check     = isset( $item->large[3] ) && $item->large[3];
		$php_check       = getimagesize( $item->image_url )[0];

		/**
		 * add a filter to optionally not replace smaller images, even if a larger version exists.
		 * @var boolean
		 *
		 * @since 2.6.0
		 *
		 */
		$replace_small_images = apply_filters( 'send_images_rss_change_small_images', true, ( ! $item->width || $item->width >= $item->maxwidth ) );
		$replace_small_images = false === $replace_small_images ? $replace_small_images : true;

		if ( ( ! empty( $item->width ) && absint( $item->width ) !== absint( $php_check ) ) || $php_check >= $item->maxwidth ) {
			$replace_small_images = true;
		}

		if ( ( $mailchimp_check || $large_check ) && $replace_small_images ) {
			if ( false !== strpos( $item->caption, 'wp-caption' ) ) {
				$image->parentNode->removeAttribute( 'style' ); // remove the style from parentNode, only if it's a caption.
			}

			$size_to_use = $item->large;
			if ( $mailchimp_check ) {
				$size_to_use = $item->mailchimp;
			}

			// use the MC size image, or the large image if there is no MC, for source
			$image->setAttribute( 'src', esc_url( $size_to_use[0] ) );
			$image->setAttribute( 'width', absint( $size_to_use[1] ) );
			$image->setAttribute( 'style', esc_attr( 'display:block;margin:10px auto;' ) );

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

		$item  = $this->get_image_variables( $image );
		$width = $item->width;
		if ( empty( $item->width ) ) {
			$width = getimagesize( $item->image_url )[0];
		}

		// guard clause: set everything to be centered
		$style = 'display:block;margin:10px auto;max-width:' . $item->maxwidth . 'px;';

		// first check: only images uploaded before plugin activation in [gallery] should have had the width stripped out,
		// but some plugins or users may remove the width on their own. Opting not to add the width in
		// because it complicates things.
		if ( ! empty( $width ) && $width < $item->maxwidth ) {
			// now, if it's a small image, aligned right. since images with captions don't have alignment, we have to check the caption alignment also.
			if ( false !== strpos( $item->class, 'alignright' ) || false !== strpos( $item->caption, 'alignright' ) ) {
				$image->setAttribute( 'align', 'right' );
				$style = 'margin:0px 0px 10px 10px;max-width:' . $item->halfwidth . 'px;';
			}
			// or if it's a small image, aligned left
			elseif ( false !== strpos( $item->class, 'alignleft' ) || false !== strpos( $item->caption, 'alignleft' ) ) {
				$image->setAttribute( 'align', 'left' );
				$style = 'margin:0px 10px 10px 0px;max-width:' . $item->halfwidth . 'px;';
			}
		}

		$image->setAttribute( 'style', esc_attr( $style ) );

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

		$item = $this->get_image_variables( $image );

		// now one last check if there are captions O.o
		if ( false === strpos( $item->caption, 'wp-caption' ) ) {
			return; // theoretically, no caption, so skip forward and finish up.
		}
		// we has captions and have to deal with their mess.
		$image->parentNode->removeAttribute( 'style' );

		// guard clause: set the caption style to full width and center
		$style = 'margin:0 auto;max-width:' . $item->maxwidth . 'px;';

		// if a width is set, then let's adjust for alignment
		if ( ! empty( $item->width ) && $item->width < $item->maxwidth ) {
			// if it's a small image with a caption, aligned right
			if ( false !== strpos( $item->caption, 'alignright' ) ) {
				$style = 'float:right;max-width:' . $item->halfwidth . 'px;';
			}
			// or if it's a small image with a caption, aligned left
			elseif ( false !== strpos( $item->caption, 'alignleft' ) ) {
				$style = 'float:left;max-width:' . $item->halfwidth . 'px;';
			}
		}

		$image->parentNode->setAttribute( 'style', esc_attr( $style ) );
	}


	/**
	 * Get the ID of each image dynamically.
	 *
	 * @since 2.1.0
	 *
	 * @author Philip Newcomer
	 * @link   http://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
	 */
	protected function get_image_id( $attachment_url = '' ) {

		$attachment_id = false;

		// If there is no url, return.
		if ( '' == $attachment_url ) {
			return;
		}

		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();

		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$url_stripped   = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );

			// Finally, run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id  = $this->fetch_image_id_query( $url_stripped, $attachment_url );

		}

		return $attachment_id;
	}

	/**
	 * Fetch image ID from database
	 * @param  var $url_stripped   image url without WP resize string (eg 150x150)
	 * @param  var $attachment_url image url
	 * @return int (image id)                 image ID, or false
	 *
	 * @since 2.6.0
	 *
	 * @author hellofromtonya
	 */
	protected function fetch_image_id_query( $url_stripped, $attachment_url ) {

		global $wpdb;

		$query_sql = $wpdb->prepare(
			"
				SELECT wposts.ID
				FROM {$wpdb->posts} wposts, {$wpdb->postmeta} wpostmeta
				WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value IN ( %s, %s ) AND wposts.post_type = 'attachment'
			",
			$url_stripped, $attachment_url
		);

		$result = $wpdb->get_col( $query_sql );

		return empty( $result ) || ! is_numeric( $result[0] ) ? false : intval( $result[0] );
	}

}
