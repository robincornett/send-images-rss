<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
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
		$content = wpautop( $this->trim_excerpt( $content ) );
		$after   = wpautop( $this->read_more() );
		return $before . $content . $after;
	}

	/**
	 * Add post's featured image to beginning of excerpt
	 * @since 3.0.0
	 */
	protected function set_featured_image() {

		$concede = $this->concede_to_displayfeaturedimage();
		if ( $concede ) {
			return;
		}

		$this->setting  = get_option( 'sendimagesrss' );
		$post_id        = get_the_ID();
		$thumbnail_size = $this->setting['thumbnail_size'] ? $this->setting['thumbnail_size'] : 'thumbnail';
		$image_source   = wp_get_attachment_image_src( $this->get_image_id( $post_id ), $thumbnail_size );

		if ( ! $image_source || 'none' === $thumbnail_size ) {
			return;
		}

		if ( ! $image_source[3] && 'mailchimp' === $thumbnail_size ) {
			$image_source = wp_get_attachment_image_src( $this->get_image_id( $post_id ), 'large' );
		}

		return $this->build_image( $image_source );

	}

	/**
	 * Set the image alignment
	 * @param string $alignment image alignment as set in settings
	 *
	 * @since 3.0.0
	 */
	protected function set_image_style( $alignment ) {
		switch ( $alignment ) {
			case 'right':
				$style = sprintf( 'margin: 0 0 20px 20px;' );
				break;

			case 'center':
				$style = sprintf( 'display: block;margin: 0 auto 12px;' );
				break;

			case 'none':
				$style = sprintf( 'margin: 0 0 0 20px;' );
				break;

			default:
				$style = sprintf( 'margin: 0 20px 20px 0;' );
				break;
		}
		return $style;
	}

	/**
	 * Build the featured image
	 * @param  array $image_source attachment url, width, height
	 * @return string               image HTML
	 *
	 * @since 3.0.0
	 */
	protected function build_image( $image_source ) {

		$alignment = $this->setting['alignment'] ? $this->setting['alignment'] : 'left';
		$style     = $this->set_image_style( $alignment );

		if ( ! $image_source[3] ) {
			$max_width = $this->setting['image_size'] ? $this->setting['image_size'] : get_option( 'sendimagesrss_image_size', 560 );
			$style    .= sprintf( 'max-width:%spx;', $max_width );
		}

		$image = sprintf( '<a href="%s"><img width="%s" height="%s" src="%s" alt="%s" align="%s" style="%s" /></a>',
			get_the_permalink(),
			$image_source[1],
			$image_source[2],
			$image_source[0],
			the_title_attribute( 'echo=0' ),
			$alignment,
			apply_filters( 'send_images_rss_excerpt_image_style', $style, $alignment )
		);

		return $image;

	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @return trimmed excerpt     Excerpt reduced to appropriate number of words, but as a full sentence.
	 *
	 * @since 3.0.0
	 */
	protected function trim_excerpt( $text ) {

		$raw_excerpt = $text;
		if ( '' === $text ) {

			$text = get_the_content( '' );
			$text = strip_shortcodes( $text );
			$text = apply_filters( 'the_content', $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
			$text = strip_tags( $text, $this->allowed_tags() );
			$text = $this->count_excerpt( $text );
			$text = trim( force_balance_tags( $text ) );

			/**
			 * Filter to modify trimmed excerpt.
			 *
			 * @since 3.0.0
			 */
			$text = apply_filters( 'send_images_rss_trim_excerpt', $text, $raw_excerpt );

		}

		return $text;

	}

	/**
	 * Modify read more link.
	 * @return link to original post
	 *
	 * @since 3.0.0
	 */
	protected function read_more() {
		$read_more = $this->setting['read_more'] ? $this->setting['read_more'] : sprintf( __( 'Continue reading %s at %s.', 'send-images-rss' ), '%%POSTNAME%%', '%%BLOGNAME%%' );
		$post_name = get_the_title();
		$permalink = get_permalink();
		$blog_name = get_bloginfo( 'name' );

		$read_more = str_replace( '%%POSTNAME%%', $post_name, $read_more );
		$read_more = str_replace( '%%BLOGNAME%%', $blog_name, $read_more );

		/**
		 * Filter to modify link back to original post.
		 *
		 * @since 3.0.0
		 */
		$output = sprintf( '<a href="%s">%s</a>', esc_url( $permalink ), esc_html( $read_more ) );
		return apply_filters( 'send_images_rss_excerpt_read_more', $output, $read_more, $blog_name, $post_name );
	}

	/**
	 * Tags to allow in excerpt
	 * @return string allowed tags
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @param  boolean $image_id      image ID
	 * @return ID           ID of featured image, fallback image if no featured image, or false if no image exists.
	 *
	 * Since 3.0.0
	 */
	protected function get_image_id( $post_id, $image_id = false ) {

		$image_id = has_post_thumbnail() ? get_post_thumbnail_id( $post_id ) : $this->get_fallback_image_id( $post_id );
		return apply_filters( 'send_images_rss_featured_image_id', $image_id );

	}

	/**
	 * Get the ID of the first image in the post
	 * @param  int $post_id first image in post ID
	 * @return ID          ID of the first image attached to the post
	 *
	 * @since 3.0.0
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

	/**
	 * Check if we should not run and let (old) featured image plugin do its work instead.
	 * @param  boolean $concede false by default
	 * @return boolean          true only if plugin is 2.3.0 or later, and is set to add an image to the feed
	 */
	protected function concede_to_displayfeaturedimage() {
		if ( ! property_exists( 'Display_Featured_Image_Genesis_Common', 'version' ) ) {
			return false;
		}

		$reflection = new ReflectionProperty( 'Display_Featured_Image_Genesis_Common', 'version' );
		$is_static  = $reflection->isStatic();
		if ( ! $is_static ) {
			// we can return early here regardless of featured image settings, because the new version is smart and will quit in favor of us.
			return false;
		}
		$displaysetting = get_option( 'displayfeaturedimagegenesis' );
		return $displaysetting['feed_image'] ? true : false;
	}

}
