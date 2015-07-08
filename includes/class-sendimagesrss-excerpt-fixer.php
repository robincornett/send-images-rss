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
	 * Add post's featured image to beginning of excerpt
	 * @since x.y.z
	 */
	public function set_featured_image( $content ) {

		$this->setting = get_option( 'sendimagesrss' );

		if ( ! has_post_thumbnail( get_the_ID() ) ) {
			return $content;
		}

		$thumbnail_size = $this->setting['thumbnail_size'] ? $this->setting['thumbnail_size'] : 'thumbnail';
		$alignment      = $this->setting['alignment'] ? $this->setting['alignment'] : 'left';

		switch ( $alignment ) {
			case 'right':
				$style = 'margin: 0 0 10px 10px;';
				break;

			case 'center':
				$style = 'display: block; margin: 0 auto 10px;';
				break;

			case 'none':
				$style = 'margin: 0 0 0 10px;';
				break;

			default:
				$style = 'margin: 0 10px 10px 0;';
				break;
		}

		$image = sprintf( '<a href="%s">%s</a>', get_the_permalink(), get_the_post_thumbnail( get_the_ID(), $thumbnail_size, array( 'align' => $alignment, 'style' => apply_filters( 'sendimagesrss_excerpt_image_style', $style ) ) ) );

		return $image . $content;

	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @return trimmed excerpt     Excerpt reduced to appropriate number of words, but as a full sentence.
	 *
	 * @since x.y.z
	 */
	public function trim_excerpt( $text ) {

		$raw_excerpt = $text;
		if ( $text ) {
			return $text . $this->read_more();
		}

		$text = get_the_content( '' );
		$text = strip_shortcodes( $text );
		$text = str_replace( ']]>', ']]&gt;', $text );
		$text = strip_tags( $text, $this->allowed_tags() );
		$text = $this->count_excerpt( $text );
		$text = trim( force_balance_tags( $text ) );

		/**
		 * Filter to modify trimmed excerpt.
		 *
		 * @since x.y.z
		 */
		$text = apply_filters( 'sendimagesrss_trim_excerpt', $text, $raw_excerpt );

		return $text . $this->read_more();

	}

	/**
	 * Modify read more link.
	 * @return link to original post
	 *
	 * @since x.y.z
	 */
	protected function read_more() {
		$permalink = get_permalink();
		$title     = get_the_title();
		$blog_name = get_bloginfo( 'name' );
		$read_more = sprintf( __( '<a href="%s">Continue reading %s at %s.</a>', 'send-images-rss' ), $permalink, $title, $blog_name );

		/**
		 * Filter to modify link back to original post.
		 *
		 * @since x.y.z
		 */
		return apply_filters( 'sendimagesrss_excerpt_read_more', $read_more, $permalink, $title, $blog_name );
	}

	/**
	 * Tags to allow in excerpt
	 * @return string allowed tags
	 *
	 * @since x.y.z
	 */
	protected function allowed_tags() {
		$tags = '<style>,<br>,<em>,<i>,<ul>,<ol>,<li>,<strong>,<b>,<p>';
		return apply_filters( 'sendimagesrss_allowed_tags', $tags );
	}

	/**
	 * Trim excerpt to word count, but to the end of a sentence.
	 * @param  excerpt $text original excerpt
	 * @return trimmed excerpt       ends in a complete sentence.
	 *
	 * @since x.y.z
	 */
	protected function count_excerpt( $text ) {
		$excerpt_length = $this->setting['excerpt_length'] ? $this->setting['excerpt_length'] : 75;
		$tokens         = array();
		$count          = 0;

		// Divide the string into tokens; HTML tags, or words, followed by any whitespace
		preg_match_all( '/(<[^>]+>|[^<>\s]+)\s*/u', $text, $tokens );

		foreach ( $tokens[0] as $token ) {

			if ( $count >= $excerpt_length && preg_match( '/[\?\.\!]\s*$/uS', $token ) ) {
				// Limit reached, continue until ? . or ! occur at the end
				$text .= trim( $token );
				break;
			}

			// Add words to complete sentence
			$count++;

			// Append what's left of the token
			$text .= $token;
		}

		return $text;
	}

}
