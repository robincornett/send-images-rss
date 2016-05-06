<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2016 Robin Cornett
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
			return $content;
		}
		$this->setting  = sendimagesrss_get_setting();
		/**
		 * Add a filter to change the RSS thumbnail size.
		 * @since 3.2.0
		 */
		$thumbnail_size = apply_filters( 'send_images_rss_thumbnail_size', $this->setting[ 'thumbnail_size' ] );

		add_filter( 'jetpack_photon_override_image_downsize', '__return_true' );
		$before  = $this->set_featured_image( $thumbnail_size );
		$content = wpautop( $this->trim_excerpt( $content ) );
		$after   = wpautop( $this->read_more() );
		return wp_kses_post( $before . $content . $after );
	}

	/**
	 * Set up the featured image for the excerpt/full text.
	 * @param $thumbnail_size image size to use
	 * @param string $content post content (only needed for full text feeds)
	 * @return string|void
	 *
	 * @since 3.0.0
	 */
	public function set_featured_image( $thumbnail_size, $content = '' ) {

		$concede = $this->concede_to_displayfeaturedimage();
		if ( $concede ) {
			return;
		}

		$this->setting = sendimagesrss_get_setting();
		$image_id      = $this->get_image_id( get_the_ID() );
		if ( ! $image_id || 'none' === $thumbnail_size ) {
			return;
		}
		$rss_option = get_option( 'rss_use_excerpt' );
		$in_content = '0' === $rss_option ? $this->is_image_in_content( $image_id, $content ) : false;
		if ( $in_content ) {
			return;
		}

		$image_source = wp_get_attachment_image_src( $image_id, $thumbnail_size );
		if ( isset( $image_source[3] ) && ! $image_source[3] && 'mailchimp' === $thumbnail_size ) {
			$image_source = wp_get_attachment_image_src( $image_id, 'large' );
		}

		return $this->build_image( $image_source );

	}

	/**
	 * Set the image alignment
	 * @param string $alignment image alignment as set in settings
	 * @return string
	 * @since  3.0.0
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

		/**
		 * Filter the image style.
		 */
		return apply_filters( 'send_images_rss_excerpt_image_style', $style, $alignment );
	}

	/**
	 * Build the featured image
	 * @param  array $image_source attachment url, width, height
	 * @return string               image HTML
	 *
	 * @since 3.0.0
	 */
	protected function build_image( $image_source ) {

		$rss_option = get_option( 'rss_use_excerpt' );
		$alignment  = $this->setting['alignment'] ? $this->setting['alignment'] : 'left';
		$style      = $this->set_image_style( $alignment );
		$max_width  = isset( $this->setting['image_size'] ) ? $this->setting['image_size'] : get_option( 'sendimagesrss_image_size', 560 );
		if ( ( '1' === $rss_option || $this->can_process() ) && isset( $image_source[1] ) && $image_source[1] > $max_width ) {
			$style .= sprintf( 'max-width:%spx;', $max_width );
		}

		$image = sprintf( '<a href="%s"><img width="%s" height="%s" src="%s" alt="%s" align="%s" style="%s" /></a>',
			get_the_permalink(),
			$image_source[1],
			$image_source[2],
			$image_source[0],
			the_title_attribute( 'echo=0' ),
			$alignment,
			$style
		);

		return $image;

	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @return string trimmed excerpt     Excerpt reduced to appropriate number of words, but as a full sentence.
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
	 * @return string link to original post
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
		return apply_filters( 'send_images_rss_excerpt_read_more', $output, $read_more, $blog_name, $post_name, $permalink );
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
	 * @param  $text original excerpt
	 * @return string excerpt       ends in a complete sentence.
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
	 * @return string  ID of featured image, fallback image if no featured image, or false if no image exists.
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

	/**
	 * For full text feeds when the featured image has been added to the feed, check
	 * if the image already exists in the post content (mailchimp size for altered
	 * feeds; full size for unaltered feeds).
	 * @param $image_id
	 * @param $content
	 * @param bool $in_content
	 * @return bool
	 *
	 * @since 3.1.0
	 */
	protected function is_image_in_content( $image_id, $content, $in_content = false ) {
		$image_size   = $this->can_process() ? 'mailchimp' : 'full';
		$source       = wp_get_attachment_image_src( $image_id, $image_size );
		$post_content = strpos( $content, 'src="' . $source[0] );

		if ( false !== $post_content ) {
			$in_content = true;
		}
		return apply_filters( 'send_images_rss_image_in_content', $in_content, $image_id );
	}

	/**
	 * Function to check whether the feed/image should be processed or not
	 * @param bool $can_process
	 *
	 * @return bool
	 * @since 3.1.0
	 */
	public function can_process( $can_process = false ) {
		$setting  = sendimagesrss_get_setting();
		$alt_feed = $setting['alternate_feed'];
		return ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ? true : false;
	}
}
