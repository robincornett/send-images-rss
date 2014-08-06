<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014 Robin Cornett
 * @license   GPL-2.0+
 */

/**
 * Class for adding a new field to the options-media.php page.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS_Settings {
	/**
	 * Add new fields to wp-admin/options-media.php page.
	 *
	 * @since x.y.z
	 */
	public function register_settings() {
		register_setting( 'media', 'sendimagesrss_simplify_feed', 'esc_attr' );
		register_setting( 'media', 'sendimagesrss_image_size', 'esc_attr' );
		register_setting( 'media', 'sendimagesrss_alternate_feed', 'esc_attr' );

		add_settings_section(
			'send_rss_section',
			__( 'RSS Feeds', 'send-images-rss' ),
			array( $this, 'section_description'),
			'media'
		);

		add_settings_field(
			'sendimagesrss_simplify',
			'<label for "sendimagesrss_simplify_feed">' . __( 'Simplify Feed?', 'send-images-rss' ) . '</label>',
			array( $this, 'field_simplify' ),
			'media',
			'send_rss_section'
		);

		add_settings_field(
			'sendimagesrss_image_size_setting',
			'<label for="sendimagesrss_image_size">' . __( 'RSS image size:' , 'send-images-rss' ) . '</label>',
			array( $this, 'field_image_size' ),
			'media',
			'send_rss_section'
		);

		add_settings_field(
			'sendimagesrss_alternate_rss_feed',
			'<label for="sendimagesrss_alternate_feed">' . __( 'Alternate feed?' , 'send-images-rss' ) . '</label>',
			array( $this, 'field_alternate_feed' ),
			'media',
			'send_rss_section'
		);
	}


	/**
	 * Callback for RSS Feeds section.
	 *
	 * @since x.y.z
	 */
	public function section_description() {
		echo '<p>' . __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) . '</p>';
	}

	/**
	 * Callback for feed simplification setting.
	 *
	 * @since x.y.z
	 */
	public function field_simplify() {
		$value = get_option( 'sendimagesrss_simplify_feed' );

		echo '<input type="checkbox" name="sendimagesrss_simplify_feed" id="sendimagesrss_simplify_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="sendimagesrss_simplify_feed">' . __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ) . '</label>';
		echo '<p class="description">' . __( 'If you are not concerned about sending your feed out over email and want only your galleries changed from thumbnails to large images, check this box.', 'send-images-rss' ) . '</p>';

		if ( $value ) {
			echo '<p>' . __( 'The only part of your feed which will be modified are your galleries. Email sized images will not be created.', 'send-images-rss' ) . '</p>';
		}
	}

	/**
	 * Callback for image size field setting.
	 *
	 * @since x.y.z
	 */
	public function field_image_size() {
		$value = get_option( 'sendimagesrss_image_size', '560' );
		if ( !$value ) {
			$value = 560;
		}

		echo '<label for="sendimagesrss_image_size">' . __( 'Max Width', 'send-images-rss' ) . '</label>';
		echo '<input type="number" step="1" min="200" id="sendimagesrss_image_size" name="sendimagesrss_image_size" value="' . esc_attr( $value ) . '" class="small-text" />';
		echo '<p class="description">' . __( 'Most users should <strong>should not</strong> need to change this number, but if you have customized your emails to be a different width, or you are using a template with a sidebar, you will want to change the width here. The default width is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content).', 'send-images-rss' ) . '</p>';
		echo '<p class="description">' . __( '<strong>Note:</strong> Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.', 'send-images-rss' ) . '</p>';

	}

	/**
	 * Callback for alternate feed setting.
	 *
	 * @since x.y.z
	 */
	public function field_alternate_feed() {
		$value = get_option( 'sendimagesrss_alternate_feed' );
		$simplify = get_option( 'sendimagesrss_simplify_feed' );

		echo '<input type="checkbox" name="sendimagesrss_alternate_feed" id="sendimagesrss_alternate_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="sendimagesrss_alternate_feed">' . __( 'Apply sizes only to new custom feed', 'send-images-rss' ) . '</label>';
		echo '<p class="description">' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, check this box.', 'send-images-rss' ) . '</p>';

		if ( $value && ! $simplify ) {
			echo '<p>' . sprintf(
				__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.' ),
				esc_url( trailingslashit( home_url() ) . 'feed/email' )
			) . '</p>';
		}
	}

	/**
	 * Error message if both Simplify Feed and Alternate Feed are checked.
	 *
	 * @since x.y.z
	 */
	public function error_message() {
		$value = get_option( 'sendimagesrss_alternate_feed' );
		$simplify = get_option( 'sendimagesrss_simplify_feed' );

		if ( $value && $simplify ) {
			echo '<div class="error"><p><strong>' . __( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' ) . '</strong></p></div>';
		}
	}
}
