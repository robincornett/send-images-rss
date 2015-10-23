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
	 * if iThemes Security is set to use the hackrepair blacklist
	 * @var boolean
	 */
	protected $hackrepair;

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

		// Strip extra div added by new DOMDocument
		if ( version_compare( PHP_VERSION, '5.3.6', '>=' ) ) {
			$content = substr( $doc->saveHTML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
		} else {
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
		} elseif ( function_exists( 'iconv' ) ) {
			// not sure this is an improvement over straight load (for special characters)
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

		$this->hackrepair = $this->is_hackrepair();

		// Now work on the images, which is why we're really here.
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {

			$url = $image->getAttribute( 'src' );
			$id  = $this->get_image_id( $url );

			// if the image is not part of WP, we cannot use it, although we'll provide a filter to try anyway
			if ( false === $id && false === $this->process_external_images() ) {
				continue;
			}

			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'style' );

			// external images
			if ( false === $id && true === $this->process_external_images() ) {
				$this->fix_other_images( $image );
				$this->fix_captions( $image );
				continue;
			}

			$this->replace_images( $image );

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
		$item->class     = $image->getAttribute( 'class' );
		$item->width     = $image->getAttribute( 'width' );
		// this may or may not be a caption
		$item->caption = $image->parentNode;
		if ( false === strpos( $item->caption->getAttribute( 'class' ), 'wp-caption' ) ) {
			// this would kick in if the image inside the caption is linked.
			$item->caption = $image->parentNode->parentNode;
		}

		if ( false === $item->image_id ) {
			return $item;
		}

		$mailchimp    = wp_get_attachment_image_src( $item->image_id, 'mailchimp' );
		$item->source = isset( $mailchimp[3] ) && true === $mailchimp[3] ? $mailchimp : wp_get_attachment_image_src( $item->image_id, 'large' );

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

		$item                 = $this->get_image_variables( $image );
		$maxwidth             = $this->get_image_size();
		$source_check         = ( isset( $item->source[3] ) && $item->source[3] ) ? true : false;
		$replace_small_images = $this->replace_small_images( $item );

		if ( false === $replace_small_images ) {
			$image_data = false;
			if ( $item->image_url && false === $this->hackrepair ) {
				$image_data = getimagesize( $item->image_url );
			}
			$php_check = false === $image_data ? $item->width : $image_data[0];
			if ( ( ! empty( $item->width ) && (int) $item->width !== $php_check ) || $php_check >= $maxwidth ) {
				$replace_small_images = true;
			}
		}

		if ( $source_check && true === $replace_small_images ) {

			// remove the style from parentNode, only if it's a caption.
			if ( false !== strpos( $item->caption->getAttribute( 'class' ), 'wp-caption' ) ) {
				$item->caption->removeAttribute( 'style' );
			}

			$style = sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );
			/**
			 * filter the image style
			 * @since 2.6.0
			 */
			$style = apply_filters( 'send_images_rss_email_image_style', $style, $maxwidth );

			// use the MC size image, or the large image if there is no MC, for source
			$image->setAttribute( 'src', esc_url( $item->source[0] ) );
			$image->setAttribute( 'width', (int) $item->source[1] );
			$image->setAttribute( 'style', esc_attr( $style ) );

		} else {
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
			$image_data = $item->image_url && ! $this->hackrepair ? getimagesize( $item->image_url ) : false;
			$width      = false === $image_data ? $item->width : $image_data[0];
		}
		$maxwidth   = $this->get_image_size();
		$halfwidth  = floor( $maxwidth / 2 );
		$alignright = $alignleft = false;
		if ( false !== strpos( $item->class, 'alignright' ) || false !== strpos( $item->caption->getAttribute( 'class' ), 'alignright' ) ) {
			$alignright = true;
		} elseif ( false !== strpos( $item->class, 'alignleft' ) || false !== strpos( $item->caption->getAttribute( 'class' ), 'alignleft' ) ) {
			$alignleft = true;
		}

		// guard clause: set everything to be centered
		$style = sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );

		// first check: only images uploaded before plugin activation in [gallery] should have had the width stripped out,
		// but some plugins or users may remove the width on their own. Opting not to add the width in
		// because it complicates things.
		if ( ! empty( $width ) && $width < $maxwidth ) {
			// now, if it's a small image, aligned right. since images with captions don't have alignment, we have to check the caption alignment also.
			if ( true === $alignright ) {
				$image->setAttribute( 'align', 'right' );
				$style = sprintf( 'margin:0px 0px 10px 10px;max-width:%spx;', $halfwidth );
			} elseif ( true === $alignleft ) { // or if it's a small image, aligned left
				$image->setAttribute( 'align', 'left' );
				$style = sprintf( 'margin:0px 10px 10px 0px;max-width:%spx;', $halfwidth );
			}
		}

		/**
		 * filter the image style
		 *
		 * @since 2.6.0
		 */
		$style = apply_filters( 'send_images_rss_other_image_style', $style, $width, $maxwidth, $halfwidth, $alignright, $alignleft );

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

		$item       = $this->get_image_variables( $image );
		$width      = $item->width;
		$maxwidth   = $this->get_image_size();
		$halfwidth  = floor( $maxwidth / 2 );
		$alignright = $alignleft = false;
		if ( false !== strpos( $item->caption->getAttribute( 'class' ), 'alignright' ) ) {
			$alignright = true;
		} elseif ( false !== strpos( $item->caption->getAttribute( 'class' ), 'alignleft' ) ) {
			$alignleft = true;
		}

		// now one last check if there are captions O.o
		if ( false === strpos( $item->caption->getAttribute( 'class' ), 'wp-caption' ) ) {
			return; // theoretically, no caption, so skip forward and finish up.
		}
		// we has captions and have to deal with their mess.
		$item->caption->removeAttribute( 'style' );

		// guard clause: set the caption style to full width and center
		$style = sprintf( 'margin:0 auto;max-width:%spx;', $maxwidth );

		// if a width is set, then let's adjust for alignment
		if ( ! empty( $width ) && $width < $maxwidth ) {
			// if it's a small image with a caption, aligned right
			if ( true === $alignright ) {
				$style = sprintf( 'float:right;max-width:%spx;', $halfwidth );
			} elseif ( true === $alignleft ) { // or if it's a small image with a caption, aligned left
				$style = sprintf( 'float:left;max-width:%spx;', $halfwidth );
			}
		}

		/**
		 * filter the caption style
		 *
		 * @since 2.6.0
		 */
		$style = apply_filters( 'send_images_rss_caption_style', $style, $width, $maxwidth, $halfwidth, $alignright, $alignleft );

		$item->caption->setAttribute( 'style', esc_attr( $style ) );
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
		if ( '' === $attachment_url ) {
			return;
		}

		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();
		$base_url         = wp_make_link_relative( $upload_dir_paths['baseurl'] );
		$attachment_url   = wp_make_link_relative( $attachment_url );

		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $base_url ) ) {

			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $base_url . '/', '', $attachment_url );

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

	/**
	 * Check whether iThemes Security hack repair is running or not as it throws errors in the feed
	 * @param  boolean $hack_repair false by default
	 * @return boolean              true if hack repair is set and plugin is active
	 *
	 * since 3.0.0
	 */
	protected function is_hackrepair() {
		$hack_repair = false;
		if ( ! class_exists( 'ITSEC_Core' ) ) {
			return $hack_repair;
		}

		$ithemes_ban = get_option( 'itsec_ban_users' );

		if ( is_array( $ithemes_ban ) && isset( $ithemes_ban['default'] ) ) {
			$hack_repair = $ithemes_ban['default'];
		}
		return $hack_repair;
	}

	/**
	 * Add filter to optionally process external images as best we can.
	 * @var boolean
	 *
	 * @since 2.6.0
	 */
	protected function process_external_images() {
		$process_external = apply_filters( 'send_images_rss_process_external_images', false );
		return (bool) true === $process_external ? true : false;
	}

	/**
	 * add a filter to optionally not replace smaller images, even if a larger version exists.
	 * @var boolean
	 *
	 * @since 2.6.0
	 *
	 */
	protected function replace_small_images( $item ) {
		$maxwidth             = $this->get_image_size();
		$replace_small_images = apply_filters( 'send_images_rss_change_small_images', true, ( ! $item->width || $item->width >= $maxwidth ) );
		return (bool) false === $replace_small_images ? false : true;
	}

	/**
	 * Get the email image size
	 * @return int The plugin image size (from settings page), or 560 by default
	 */
	protected function get_image_size() {
		$setting = get_option( 'sendimagesrss' );
		return $setting ? $setting['image_size'] : get_option( 'sendimagesrss_image_size', 560 );
	}
}
