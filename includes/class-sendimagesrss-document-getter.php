<?php

/**
 * Document getter class.
 * @since 3.4.0
 */
class SendImagesRSS_Document_Getter {

	/**
	 * Try and load content as an HTML document with special characters, etc. intact.
	 *
	 * @since 1.0.0
	 * @param string $content
	 * @return DOMDocument
	 */
	public function load( $content ) {
		$doc = new DOMDocument();

		// Populate the document, hopefully cleanly, but otherwise, still load.
		libxml_use_internal_errors( true ); // turn off errors for HTML5
		// best option due to special character handling
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$currentencoding = mb_internal_encoding();
			$content         = mb_convert_encoding( $content, 'HTML-ENTITIES', $currentencoding ); // convert the feed from XML to HTML
		} elseif ( function_exists( 'iconv' ) ) {
			// not sure this is an improvement over straight load (for special characters)
			$currentencoding = iconv_get_encoding( 'internal_encoding' );
			$content         = iconv( $currentencoding, 'ISO-8859-1//IGNORE', $content );
		}
		$doc->LoadHTML( $content );
		libxml_clear_errors(); // now that it's loaded, go ahead

		return $doc;
	}
}
