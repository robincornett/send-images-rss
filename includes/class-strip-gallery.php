<?php

class Strip_Gallery {

	/**
	 * Strips out WP generated file names so that the original file is used instead.
	 * Desired behavior for galleries in feed regardless of whether alternate feed is used or not.
	 *
	 * Note: If alternate feed is used, gallery images in unaltered feeds will not have
	 * width/height parameters inline. Calling this OK due to example set by Envira.
	 *
	 * Gary: wondering, then, if I really need to set width on images in Feed_Converter?
	 * Figuring I may as well leave them since they work.
	 */

	function __construct() {
		add_filter( 'the_content', array( $this, 'strip_gallery_images' ), 19 );
	}

	public function strip_gallery_images( $content ) {
		global $post;
		if ( is_feed() && has_shortcode( $post->post_content, 'gallery' ) ) {
			$content = preg_replace( '(-\d{3,4}x\d{3,4}.)', '.', $content );
			$content = preg_replace( '(width="\d{2,3}")', '', $content );
			$content = preg_replace( '(height="\d{2,3}")', '', $content );
		}
		return $content;
	}
}
