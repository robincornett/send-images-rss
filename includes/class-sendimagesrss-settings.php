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
	 * @since 2.2.0
	 */
	public function register_settings() {
		register_setting( 'media', 'sendimagesrss_simplify_feed', array( $this, 'one_zero' ) );
		register_setting( 'media', 'sendimagesrss_image_size', array( $this, 'media_value' ) );
		register_setting( 'media', 'sendimagesrss_alternate_feed', array( $this, 'one_zero' ) );

		add_settings_section(
			'send_rss_section',
			__( 'RSS Feeds', 'send-images-rss' ),
			array( $this, 'section_description'),
			'media'
		);

		add_settings_field(
			'sendimagesrss_simplify',
			'<label for "sendimagesrss_simplify_feed">' . __( 'Simplify feed?', 'send-images-rss' ) . '</label>',
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
	 * Returns a 1 or 0, for all truthy / falsy values.
	 *
	 * Uses double casting. First, we cast to bool, then to integer.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $new_value Should ideally be a 1 or 0 integer passed in
	 * @return integer 1 or 0.
	 */
	function one_zero( $new_value ) {
	    return (int) (bool) $new_value;
	}

	/**
	 * Returns previous value for image size if not correct
	 * @param  string $new_value New value
	 * @param  string $old_value Previous value
	 * @return string            New or previous value, depending on allowed image size.
	 */
	function media_value( $new_value ) {
		$new_value = absint( $new_value );
		if ( ! $new_value || $new_value < 200 || $new_value > 900 ) {
			return get_option( 'sendimagesrss_image_size', 560 );
		}
		return $new_value;
	}

	/**
	 * Callback for RSS Feeds section.
	 *
	 * @since 2.4.0
	 */
	public function section_description() {
		echo '<p>' . __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) . '</p>';
	}

	/**
	 * Callback for feed simplification setting.
	 *
	 * @since 2.4.0
	 */
	public function field_simplify() {
		$value = get_option( 'sendimagesrss_simplify_feed' );

		echo '<input type="checkbox" name="sendimagesrss_simplify_feed" id="sendimagesrss_simplify_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="sendimagesrss_simplify_feed">' . __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ) . '</label>';

		if ( $value ) {
			echo '<p>' . __( 'The only part of your feed which will be modified are your galleries. Email sized images will not be created.', 'send-images-rss' ) . '</p>';
		}
	}

	/**
	 * Callback for image size field setting.
	 *
	 * @since 2.2.0
	 */
	public function field_image_size() {
		$value = get_option( 'sendimagesrss_image_size', 560 );

		echo '<label for="sendimagesrss_image_size">' . __( 'Max Width', 'send-images-rss' ) . '</label>';
		echo '<input type="number" step="1" min="200" max="900" id="sendimagesrss_image_size" name="sendimagesrss_image_size" value="' . esc_attr( $value ) . '" class="small-text" />';
		echo '<p class="description">' . __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' ) . '</p>';

	}

	/**
	 * Callback for alternate feed setting.
	 *
	 * @since 2.3.0
	 */
	public function field_alternate_feed() {
		$value             = get_option( 'sendimagesrss_alternate_feed' );
		$simplify          = get_option( 'sendimagesrss_simplify_feed' );
		$pretty_permalinks = get_option( 'permalink_structure' );

		echo '<input type="checkbox" name="sendimagesrss_alternate_feed" id="sendimagesrss_alternate_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="sendimagesrss_alternate_feed">' . __( 'Create a custom feed and use that for sending emails.', 'send-images-rss' ) . '</label>';

		if ( $value && ! $simplify ) {
			if ( $pretty_permalinks ){
				echo '<p>' . sprintf(
					__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
					esc_url( trailingslashit( home_url() ) . 'feed/email' )
				) . '</p>';
			}
			else {
				echo '<p>' . sprintf(
					__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
					esc_url( trailingslashit( home_url() ) . '?feed=email' )
				) . '</p>';
			}
		}
	}

	/**
	 * Error message if both Simplify Feed and Alternate Feed are checked.
	 *
	 * @since 2.4.0
	 */
	public function error_message() {
		$value    = get_option( 'sendimagesrss_alternate_feed' );
		$simplify = get_option( 'sendimagesrss_simplify_feed' );

		if ( $value && $simplify ) {
			echo '<div class="error"><p><strong>' . __( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' ) . '</strong></p></div>';
		}
	}

	/**
	 * Help tab for media screen
	 * @return help tab with verbose information for plugin
	 *
	 * @since 2.4.0
	 */
	public function help() {
		$screen = get_current_screen();

		$sendimages_rss_help =
			'<h3>' . __( 'Simplify Feed', 'send-images-rss' ) . '</h3>' .
			'<p>' . __( 'If you are not concerned about sending your feed out over email and want only your galleries changed from thumbnails to large images, select Simplify Feed.', 'send-images-rss' ) . '</p>' .

			'<h3>' . __( 'RSS Image Size', 'send-images-rss' ) . '</h3>' .
			'<p>' . __( 'If you have customized your emails to be a nonstandard width, or you are using a template with a sidebar, you will want to change your RSS Image size (width). The default is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content).', 'send-images-rss' ) . '</p>' .
			'<p>' . __( 'Note: Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.', 'send-images-rss' ) . '</p>' .

			'<h3>' . __( 'Alternate Feed', 'send-images-rss' ) . '</h3>' .
			'<p>' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, select this option.', 'send-images-rss' ) . '</p>' .
			'<p>' . __( 'If you use custom post types with their own feeds, the alternate feed method will work even with them.', 'send-images-rss' ) . '</p>';

		$screen->add_help_tab( array(
			'id'      => 'sendimagesrss-help',
			'title'   => __( 'Send Images to RSS', 'send-images-rss' ),
			'content' => $sendimages_rss_help,
		) );

	}

}
