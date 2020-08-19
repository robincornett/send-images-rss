<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2019 Robin Cornett
 * @license   GPL-2.0+
 */

class SendImagesRSS_Excerpt_Fixer {

	/**
	 * Send Images RSS option from database
	 * @var $setting array
	 */
	protected $setting;

	/**
	 * Build RSS excerpt
	 *
	 * @param  string $content default excerpt
	 *
	 * @return string excerpt          Returns newly built excerpt
	 */
	public function do_excerpt( $content ) {
		if ( ! is_feed() ) {
			return $content;
		}
		/**
		 * Add a filter to change the RSS thumbnail size.
		 * @since 3.2.0
		 */
		$thumbnail_size = apply_filters( 'send_images_rss_thumbnail_size', $this->get_setting( 'thumbnail_size' ) );

		add_filter( 'jetpack_photon_override_image_downsize', '__return_true' );
		$before  = $this->set_featured_image( $thumbnail_size );
		$content = wpautop( $this->trim_excerpt( $content ) );
		$after   = wpautop( $this->read_more() );

		return wp_kses_post( $before . $content . $after );
	}

	/**
	 * Set up the featured image for the excerpt/full text.
	 *
	 * @param string $thumbnail_size image size to use
	 * @param string $content        post content (only needed for full text feeds)
	 *
	 * @return string
	 *
	 * @since 3.0.0
	 */
	public function set_featured_image( $thumbnail_size, $content = '' ) {

		$concede = $this->concede_to_displayfeaturedimage();
		if ( $concede ) {
			return '';
		}

		$image_id = $this->get_image_id( get_the_ID() );
		if ( ! $image_id || 'none' === $thumbnail_size ) {
			return '';
		}
		$rss_option = get_option( 'rss_use_excerpt' );
		$in_content = '0' === $rss_option ? $this->is_image_in_content( $image_id, $content ) : false;
		if ( $in_content ) {
			return '';
		}

		return $this->build_image( $image_id, $thumbnail_size );
	}

	/**
	 * Get the image source.
	 * @since 3.3.0
	 *
	 * @param $image_id
	 * @param $thumbnail_size
	 *
	 * @return array|false
	 */
	protected function get_image_source( $image_id, $thumbnail_size ) {
		$image_source = wp_get_attachment_image_src( $image_id, $thumbnail_size );
		if ( isset( $image_source[3] ) && ! $image_source[3] && 'mailchimp' === $thumbnail_size ) {
			$image_source = wp_get_attachment_image_src( $image_id, 'large' );
		}

		return $image_source;
	}

	/**
	 * Get the plugin setting.
	 * @since 3.3.0
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	protected function get_setting( $key = '' ) {
		if ( isset( $this->setting ) ) {
			return $key ? $this->setting[ $key ] : $this->setting;
		}
		$this->setting = sendimagesrss_get_setting();

		return $key ? $this->setting[ $key ] : $this->setting;
	}

	/**
	 * Set the image alignment
	 *
	 * @param string $alignment image alignment as set in settings
	 *
	 * @return string
	 * @since  3.0.0
	 */
	protected function set_image_style( $alignment ) {
		/**
		 * Add a filter to change the margin on the image.
		 * @since 3.2.0
		 */
		$margin = apply_filters( 'sendimagesrss_image_margin', 20 );
		switch ( $alignment ) {
			case 'right':
				$style = sprintf( 'margin: 0 0 %1$spx %1$spx;', $margin );
				break;

			case 'center':
				$style = sprintf( 'display: block;margin: 0 auto %1$spx;', $margin );
				break;

			case 'none':
				$style = sprintf( 'margin: 0 0 %1$spx 0;', $margin );
				break;

			default:
				$style = sprintf( 'margin: 0 %1$spx %1$spx 0;', $margin );
				break;
		}

		/**
		 * Filter the image style.
		 */
		return apply_filters( 'send_images_rss_excerpt_image_style', $style, $alignment, $margin );
	}

	/**
	 * Build the featured image
	 *
	 * @param $image_id
	 * @param $thumbnail_size
	 *
	 * @return string               image HTML
	 *
	 * @since 3.0.0
	 */
	protected function build_image( $image_id, $thumbnail_size ) {

		$image_source = $this->get_image_source( $image_id, $thumbnail_size );
		$rss_option   = get_option( 'rss_use_excerpt' );
		$setting      = $this->get_setting();
		$alignment    = $setting['alignment'] ? $setting['alignment'] : 'left';
		$permalink    = $this->get_permalink();
		$title        = the_title_attribute( 'echo=0' );
		$style        = $this->set_image_style( $alignment );
		$max_width    = isset( $setting['image_size'] ) ? $setting['image_size'] : get_option( 'sendimagesrss_image_size', 560 );
		if ( ( '1' === $rss_option || $this->can_process() ) && isset( $image_source[1] ) && $image_source[1] > $max_width ) {
			$style .= sprintf( 'max-width:%spx;', $max_width );
		}
		$style .= 'max-width:100%;';

		return apply_filters(
			'sendimagesrss_featured_image',
			sprintf(
				'<a href="%s"><img width="%s" height="%s" src="%s" alt="%s" align="%s" style="%s" /></a>',
				$permalink,
				$image_source[1],
				$image_source[2],
				$image_source[0],
				$title,
				$alignment,
				$style
			),
			$image_id,
			$image_source,
			$permalink,
			$title,
			$alignment,
			$style
		);
	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 *
	 * @param $text
	 *
	 * @return string trimmed excerpt     Excerpt reduced to appropriate number of words, but as a full sentence.
	 *
	 * @since 3.0.0
	 */
	protected function trim_excerpt( $text ) {

		$raw_excerpt = $text;
		if ( '' === $text ) {

			$text    = get_the_content( '' );
			$text    = apply_filters( 'sendimagesrss_keep_shortcodes', false ) ? $text : strip_shortcodes( $text );
			$text    = apply_filters( 'the_content', $text );
			$text    = str_replace( ']]>', ']]&gt;', $text );
			$tags    = $this->allowed_tags();
			$text    = strip_tags( $text, $tags );
			$counted = $this->count_excerpt( $text );
			$text    = trim( force_balance_tags( $counted ) );

			/**
			 * Filter to modify trimmed excerpt.
			 *
			 * @since 3.0.0
			 */
			$text = apply_filters( 'send_images_rss_trim_excerpt', $text, $raw_excerpt, $tags, $counted );

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
		$read_more = $this->get_setting( 'read_more' );
		if ( ! $read_more ) {
			/* translators: 1. post name 2. site name */
			$read_more = sprintf(
				__( 'Continue reading %1$s at %2$s.', 'send-images-rss' ),
				'%%POSTNAME%%',
				'%%BLOGNAME%%'
			);
		}
		$post_name = get_the_title();
		$permalink = $this->get_permalink();
		$blog_name = get_bloginfo( 'name' );

		$read_more = str_replace( '%%POSTNAME%%', $post_name, $read_more );
		$read_more = str_replace( '%%BLOGNAME%%', $blog_name, $read_more );

		/**
		 * Use Yoast's nofollow filter.
		 *
		 * @since 3.2.2
		 */
		$no_follow = apply_filters( 'nofollow_rss_links', true );
		$rel       = $no_follow ? ' rel="nofollow"' : '';

		/**
		 * Filter to modify link back to original post.
		 *
		 * @since 3.0.0
		 */
		$output = sprintf( '<a href="%s"%s>%s</a>', esc_url( $permalink ), $rel, esc_html( $read_more ) );

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
	 *
	 * @param        $text string original excerpt
	 *
	 * @param string $output
	 *
	 * @return string excerpt       ends in a complete sentence.
	 *
	 * @since 3.0.0
	 */
	protected function count_excerpt( $text, $output = '' ) {
		$excerpt_length = $this->get_setting( 'excerpt_length' );
		if ( ! $excerpt_length ) {
			$excerpt_length = 75;
		}
		$tokens = array();
		$count  = 0;

		// Divide the string into tokens; HTML tags, or words, followed by any whitespace
		preg_match_all( '/(<[^>]+>|[^<>\s]+)\s*/u', $text, $tokens );

		foreach ( $tokens[0] as $token ) {

			if ( $count >= $excerpt_length && preg_match( '/[\?\.\!]\s*$/uS', $token ) ) {
				// Limit reached, continue until ? . or ! occur at the end
				$output .= trim( $token );
				break;
			}

			// Add words to complete sentence
			$count ++;

			// Append what's left of the token
			$output .= $token;
		}

		return $output;
	}

	/**
	 * Get the post's featured image id. Uses get_fallback_image_id if there is no featured image.
	 *
	 * @param  int     $post_id  current post ID
	 * @param  boolean $image_id image ID
	 *
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
	 *
	 * @param  int $post_id first image in post ID
	 *
	 * @return mixed|string         ID of the first image attached to the post
	 *
	 * @since 3.0.0
	 */
	protected function get_fallback_image_id( $post_id = null ) {
		$image_ids = array_keys(
			get_children(
				array(
					'post_parent'    => $post_id ? $post_id : get_the_ID(),
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'orderby'        => 'menu_order ID',
					'order'          => 'ASC',
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
	 * Allow users to filter the permalink for the read more/image.
	 *
	 * @since 3.3.0
	 * @return string
	 */
	protected function get_permalink() {
		return apply_filters( 'sendimagesrss_get_permalink', get_permalink() );
	}

	/**
	 * Check if we should not run and let (old) featured image plugin do its work instead.
	 *
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
	 *
	 * @param      $image_id
	 * @param      $content
	 * @param bool $in_content
	 *
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
	 *
	 * @param bool $can_process
	 *
	 * @return bool
	 * @since 3.1.0
	 */
	public function can_process( $can_process = false ) {
		$alt_feed = $this->get_setting( 'alternate_feed' );

		return ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ? true : false;
	}
}
