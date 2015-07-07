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

	protected $rss_setting;

	public function __construct() {
		$this->rss_setting = get_option( 'sendimagesrss' );
	}

	public function set_featured_image( $content ) {

		if ( ! has_post_thumbnail( get_the_ID() ) ) {
			return $content;
		}

		switch ( $this->rss_setting['alignment'] ) {
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

		$image = sprintf( '<a href="%s">%s</a>', get_the_permalink(), get_the_post_thumbnail( get_the_ID(), $this->rss_setting['thumbnail_size'], array( 'align' => $this->rss_setting['alignment'], 'style' => apply_filters( 'sendimagesrss_excerpt_image_style', $style ) ) ) );

		return $image . $content;

	}

	function trim_excerpt( $excerpt ) {
		$raw_excerpt = $excerpt;
		if ( ! $excerpt ) {

			$excerpt = get_the_content( '' );
			$excerpt = strip_shortcodes( $excerpt );
			$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
			$excerpt = strip_tags( $excerpt, $this->allowed_tags() );

			// Set the excerpt word count and only break after sentence is complete.
			$excerpt_length = $this->settings['excerpt_length'];
			$tokens         = array();
			$excerpt_output = '';
			$count          = 0;

			// Divide the string into tokens; HTML tags, or words, followed by any whitespace
			preg_match_all( '/(<[^>]+>|[^<>\s]+)\s*/u', $excerpt, $tokens );

			foreach ( $tokens[0] as $token ) {

				if ( $count >= $excerpt_length && preg_match( '/[\?\.\!]\s*$/uS', $token ) ) {
					// Limit reached, continue until ? . or ! occur at the end
					$excerpt_output .= trim( $token );
					break;
				}

				// Add words to complete sentence
				$count++;

				// Append what's left of the token
				$excerpt_output .= $token;
			}

			$excerpt     = trim( force_balance_tags( $excerpt_output ) );
			$read_more   = sprintf( __( 'Continue reading %s at %s.', 'send-images-rss' ), get_the_title(), get_bloginfo( 'name' ) );
			$read_more   = apply_filters( 'sendimagesrss_excerpt_read_more', $read_more );
			$excerpt_end = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink() ), $read_more );
			$excerpt    .= $excerpt_end;

		}

		return apply_filters( 'sendimagesrss_trim_excerpt', $excerpt, $raw_excerpt );

	}

	protected function allowed_tags() {
		$tags = '<style>,<br>,<em>,<i>,<ul>,<ol>,<li>,<strong>,<b>,<p>';
		return apply_filters( 'sendimagesrss_allowed_tags', $tags );
	}

}
