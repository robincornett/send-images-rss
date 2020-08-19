<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2020 Robin Cornett
 * @license   GPL-2.0+
 */

/**
 * Handle gallery images in post content used in RSS feed.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS_Strip_Gallery {
	/**
	 * Strip out WP generated file names so that the original file is used instead.
	 *
	 * Desired behavior for galleries in feed regardless of whether alternate feed is used or not.
	 *
	 * Note: If alternate feed is used, gallery images in unaltered feeds will not have
	 * width/height parameters inline. Calling this OK due to example set by Envira.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string Amended post content.
	 */
	public function strip( $content ) {
		global $post;

		//* have to remove the photon filter twice as it's really aggressive
		$photon_removed = '';
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$photon_removed = remove_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ) );
		}

		if ( has_shortcode( $post->post_content, 'gallery' ) || has_block( 'core/gallery', $post ) ) {
			$content = preg_replace( '(-\d{3,4}x\d{3,4}.)', '.', $content );
			$content = preg_replace( '(width="\d{2,3}")', '', $content );
			$content = preg_replace( '(height="\d{2,3}")', '', $content );
		}

		return $content;
	}

	/**
	 * Update core gallery output in RSS feeds to visually hide <li> bullets
	 * without losing semantic markup.
	 *
	 * @since 3.4.0
	 * @param string $block_content
	 * @param array  $block
	 * @return string
	 */
	public function modify_block_gallery( $block_content, $block ) {
		if ( 'core/gallery' !== $block['blockName'] ) {
			return $block_content;
		}
		$getter = new SendImagesRSS_Document_Getter();
		$doc    = $getter->load( $block_content );
		$this->update_gallery_list( $doc );
		$this->update_gallery_items( $doc );

		return $doc->saveHTML();
	}

	/**
	 * Update the gallery list/container.
	 *
	 * @since 3.4.0
	 * @param DOMDocument $doc
	 */
	private function update_gallery_list( DOMDocument &$doc ) {
		$ul = $doc->getElementsByTagName( 'ul' );
		if ( ! $ul ) {
			return;
		}

		$style = apply_filters( 'sendimagesrss_core_gallery_ul_style', 'list-style-type:none;padding-left:0;' );
		foreach ( $ul as $list ) {
			$style = $list->getAttribute( 'style' ) . $style;
			$list->setAttribute( 'style', $style );
		}
	}

	/**
	 * Update the gallery items.
	 *
	 * @since 3.4.0
	 * @param DOMDocument $doc
	 */
	private function update_gallery_items( DOMDocument &$doc ) {
		$li = $doc->getElementsByTagName( 'li' );
		if ( ! $li ) {
			return;
		}
		$style = apply_filters( 'sendimagesrss_core_gallery_li_style', 'list-style-type:none;' );
		foreach ( $li as $item ) {
			$style = $item->getAttribute( 'style' ) . $style;
			$item->setAttribute( 'style', $style );
		}
	}
}
