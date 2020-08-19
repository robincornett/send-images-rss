<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2019 Robin Cornett
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
	 * The plugin setting, with defaults
	 * @var $setting
	 */
	protected $setting;

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
		$getter  = new SendImagesRSS_Document_Getter();
		$doc     = $getter->load( $content );

		$this->modify_images( $doc );
		$this->modify_videos( $doc );

		// Strip extra div added by new DOMDocument
		return substr( $doc->saveHTML( $doc->getElementsByTagName( 'div' )->item( 0 ) ), 5, -6 );
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
		$this->setting    = sendimagesrss_get_setting();

		// Now work on the images, which is why we're really here.
		$images = $doc->getElementsByTagName( 'img' );
		if ( ! $images ) {
			return;
		}
		foreach ( $images as $image ) {

			$url = $image->getAttribute( 'src' );
			$id  = $this->get_image_id( $url );

			// if the image is not part of WP, we cannot use it, although we'll provide a filter to try anyway
			if ( false === $id && ! $this->process_external_images() ) {
				continue;
			}

			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'style' );

			// external images
			if ( false === $id && $this->process_external_images() ) {
				$this->fix_other_images( $image );
				$this->fix_captions( $image );
				continue;
			}

			$this->replace_images( $image );

		}
	}

	/**
	 * Maybe modify videos.
	 * @since 3.3.0
	 *
	 * @param \DOMDocument $doc
	 */
	protected function modify_videos( DOMDocument &$doc ) {
		$videos = $doc->getElementsByTagName( 'video' );
		if ( ! $videos ) {
			return;
		}
		foreach ( $videos as $video ) {
			$video->removeAttribute( 'height' );
			$video->setAttribute( 'width', $this->get_image_size() );
			$container = $video->parentNode;
			if ( false !== strpos( $container->getAttribute( 'class' ), 'wp-video' ) ) {
				$container->removeAttribute( 'style' );
			}
		}
	}


	/**
	 * Set variables for replace_images, fix_other_images, and fix_captions to use.
	 *
	 * Argument passed by reference, so no return needed.
	 * @since 2.5.0
	 *
	 * @param $image
	 * @return object
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
		$item->source = $this->does_image_size_exist( $mailchimp ) ? $mailchimp : wp_get_attachment_image_src( $item->image_id, 'large' );

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

		$item = $this->get_image_variables( $image );

		// remove the style from parentNode, only if it's a caption.
		if ( false !== strpos( $item->caption->getAttribute( 'class' ), 'wp-caption' ) ) {
			$item->caption->removeAttribute( 'style' );
		}

		$maxwidth           = $this->get_image_size();
		$replace_this_image = $this->replace_this_image( $item, $maxwidth );

		if ( false === $replace_this_image ) {
			$image_data = false;
			if ( $item->image_url && false === $this->hackrepair ) {
				$image_data = getimagesize( $item->image_url );
			}
			$php_check = false === $image_data ? $item->width : $image_data[0];
			if ( ( ! empty( $item->width ) && (int) $item->width !== $php_check ) || $php_check >= $maxwidth ) {
				$replace_this_image = true;
			}
		}

		if ( true === $replace_this_image ) {

			$style = "display:block;margin:10px auto;max-width:{$maxwidth}px;max-width:100%;";
			/**
			 * filter the image style
			 * @since 2.6.0
			 */
			$style = apply_filters( 'send_images_rss_email_image_style', $style, $maxwidth );

			// use the MC size image, or the large image if there is no MC, for source
			if ( is_array( $item->source ) ) {
				$image->setAttribute( 'src', esc_url( $item->source[0] ) );
				$image->setAttribute( 'width', (int) $item->source[1] );
			}
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
		$style = "display:block;margin:10px auto;max-width:{$maxwidth}px;max-width:100%;";

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
		$style = "display:block;margin:0 auto;max-width:{$maxwidth}px;max-width:100%;";

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
	 * @param string $attachment_url
	 *
	 * @return bool|int
	 * @since  2.1.0
	 *
	 * @author Philip Newcomer
	 */
	protected function get_image_id( $attachment_url = '' ) {

		$attachment_id = false;

		// If there is no url, return.
		if ( '' === $attachment_url ) {
			return false;
		}

		$url_stripped  = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
		$attachment_id = attachment_url_to_postid( $url_stripped );

		if ( ! $attachment_id ) {
			$url_stripped  = preg_replace( '/(?=\.(jpg|jpeg|png|gif)$)/i', '-scaled', $attachment_url );
			$attachment_id = attachment_url_to_postid( $url_stripped );
		}

		return $attachment_id > 0 ? $attachment_id : false;
	}

	/**
	 * Check whether iThemes Security hack repair is running or not as it throws errors in the feed
	 * @return boolean              true if hack repair is set and plugin is active
	 *
	 * @since 3.0.0
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
	 * As of 3.2.0, this is default to true.
	 *
	 * @since 2.6.0
	 */
	protected function process_external_images() {
		return (bool) apply_filters( 'send_images_rss_process_external_images', true );
	}

	/**
	 * add a filter to optionally not replace smaller images, even if a larger version exists.
	 *
	 * @param $item
	 * @param $maxwidth
	 *
	 * @return bool
	 * @since 2.6.0
	 */
	protected function replace_this_image( $item, $maxwidth ) {
		$replace_this_image = apply_filters( 'send_images_rss_change_small_images', $this->setting['change_small'] );
		if ( ! $item->width || $item->width >= $maxwidth ) {
			$replace_this_image = true;
		}
		return (bool) $replace_this_image;
	}

	/**
	 * Get the email image size
	 * @return int The plugin image size (from settings page), or 560 by default
	 *
	 * @since 3.0.1
	 */
	protected function get_image_size() {
		return $this->setting ? $this->setting['image_size'] : get_option( 'sendimagesrss_image_size', 560 );
	}

	/**
	 * Helper function to determine if an image size actually exists for the selected image
	 * @param  array $source result of wp_get_attachment_image_src, array if it's an image, false if not
	 * @return boolean         true if the image exists and comes in the specific size
	 *
	 * @since 3.0.1
	 */
	protected function does_image_size_exist( $source ) {
		return (bool) isset( $source[3] ) && $source[3];
	}
}
