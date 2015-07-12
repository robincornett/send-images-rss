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

class SendImagesRSS_Excerpt_Fixer {

	/**
	 * Send Images RSS option from database
	 * @var option
	 */
	protected $setting;

	/**
	 * Build RSS excerpt
	 * @param  excerpt $content default excerpt
	 * @return new excerpt          Returns newly built excerpt
	 */
	public function do_excerpt( $content ) {
		if ( ! is_feed() ) {
			return;
		}
		$before  = $this->set_featured_image();
		$content = $this->trim_excerpt( $content );
		$after   = $this->read_more();
		return wpautop( $before . $content ) . wpautop( $after );
	}

	/**
	 * Add post's featured image to beginning of excerpt
	 * @since 2.7.0
	 */
	protected function set_featured_image( $image = '' ) {

		$this->setting  = get_option( 'sendimagesrss' );
		if ( class_exists( 'Display_Featured_Image_Genesis_Common' ) ) {
			$displayfeaturedimagegenesis_common = new Display_Featured_Image_Genesis_Common();
			$version                            = $displayfeaturedimagegenesis_common->version ? $displayfeaturedimagegenesis_common->version : $displayfeaturedimagegenesis_common::$version;
			$displaysetting                     = get_option( 'displayfeaturedimagegenesis' );
			if ( $displaysetting['feed_image'] && $version <= '2.2.2' ) {
				return;
			}
		}

		$post_id        = get_the_ID();
		$thumbnail_size = $this->setting['thumbnail_size'] ? $this->setting['thumbnail_size'] : 'thumbnail';
		$alignment      = $this->setting['alignment'] ? $this->setting['alignment'] : 'left';
		$image_source   = wp_get_attachment_image_src( $this->get_image_id( $post_id ), $thumbnail_size );

		switch ( $alignment ) {
			case 'right':
				$style = 'margin: 0 0 20px 20px;';
				break;

			case 'center':
				$style = 'display: block; margin: 0 auto 12px;';
				break;

			case 'none':
				$style = 'margin: 0 0 0 20px;';
				break;

			default:
				$style = 'margin: 0 20px 20px 0;';
				break;
		}

		if ( $image_source ) {
			$image = sprintf( '<a href="%s"><img width="%s" height="%s" src="%s" alt="%s" align="%s" style="%s" /></a>',
				get_the_permalink(),
				$image_source[1],
				$image_source[2],
				$image_source[0],
				the_title_attribute( 'echo=0' ),
				$alignment,
				apply_filters( 'send_images_rss_excerpt_image_style', $style, $alignment )
			);
		}

		return $image;

	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @return trimmed excerpt     Excerpt reduced to appropriate number of words, but as a full sentence.
	 *
	 * @since 2.7.0
	 */
	protected function trim_excerpt( $text ) {

		$raw_excerpt = $text;
		if ( '' === $text ) {

			$text = get_the_content( '' );
			$text = strip_shortcodes( $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
			$text = strip_tags( $text, $this->allowed_tags() );
			$text = $this->count_excerpt( $text );
			$text = trim( force_balance_tags( $text ) );

			/**
			 * Filter to modify trimmed excerpt.
			 *
			 * @since 2.7.0
			 */
			$text = apply_filters( 'send_images_rss_trim_excerpt', $text, $raw_excerpt );

		}

		return $text;

	}

	/**
	 * Modify read more link.
	 * @return link to original post
	 *
	 * @since 2.7.0
	 */
	protected function read_more() {
		$permalink = get_permalink();
		$title     = get_the_title();
		$blog_name = get_bloginfo( 'name' );
		$read_more = sprintf( __( '<a href="%s">Continue reading %s at %s.</a>', 'send-images-rss' ), $permalink, $title, $blog_name );

		// remove the Yoast RSS footer
		add_filter( 'wpseo_include_rss_footer', '__return_false' );
		/**
		 * Filter to modify link back to original post.
		 *
		 * @since 2.7.0
		 */
		return apply_filters( 'send_images_rss_excerpt_read_more', $read_more, $permalink, $title, $blog_name );
	}

	/**
	 * Tags to allow in excerpt
	 * @return string allowed tags
	 *
	 * @since 2.7.0
	 */
	protected function allowed_tags( $tags = '' ) {
		$tags = '<style>,<br>,<br/>,<em>,<i>,<ul>,<ol>,<li>,<strong>,<b>,<p>';
		return apply_filters( 'send_images_rss_allowed_tags', $tags );
	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @param  excerpt $text original excerpt
	 * @return trimmed excerpt       ends in a complete sentence.
	 *
	 * @since 2.7.0
	 */
	protected function count_excerpt( $text, $output = '' ) {
		$excerpt_length = $this->setting['excerpt_length'] ? $this->setting['excerpt_length'] : 75;
		$tokens         = array();
		$count          = 0;

		// Divide the string into tokens; HTML tags, or words, followed by any whitespace
		preg_match_all( '/(<[^>]+>|[^<>\s]+)\s*/u', $text, $tokens );

		foreach ( $tokens[0] as $token ) {

			if ( $count >= $excerpt_length && preg_match( '/[\?\.\!]\s*$/uS', $token ) ) {
				// Limit reached, continue until ? . or ! occur at the end
				$output .= trim( $token );
				break;
			}

			// Add words to complete sentence
			$count++;

			// Append what's left of the token
			$output .= $token;
		}

		return $output;
	}

	/**
	 * Get the post's featured image id. Uses get_fallback_image_id if there is no featured image.
	 * @param  int  $post_id current post ID
	 * @param  boolean $id      image ID
	 * @return ID           ID of featured image, fallback image if no featured image, or false if no image exists.
	 *
	 * Since 2.7.0
	 */
	protected function get_image_id( $post_id, $id = false ) {

		if ( has_post_thumbnail() ) {
			$id = get_post_thumbnail_id( $post_id );
		} else {
			$id = $this->get_fallback_image_id( $post_id );
		}
		return apply_filters( 'send_images_rss_featured_image_id', $id );

	}

	/**
	 * Get the ID of the first image in the post
	 * @param  int $post_id first image in post ID
	 * @return ID          ID of the first image attached to the post
	 *
	 * @since 2.7.0
	 */
	protected function get_fallback_image_id( $post_id = null ) {
		$image_ids = array_keys(
			get_children(
				array(
					'post_parent'    => $post_id ? $post_id : get_the_ID(),
					'post_type'	     => 'attachment',
					'post_mime_type' => 'image',
					'orderby'        => 'menu_order',
					'order'	         => 'ASC',
					'numberposts'    => 1,
				)
			)
		);

		if ( isset( $image_ids[0] ) ) {
			return $image_ids[0];
		}

		return false;
	}

}
