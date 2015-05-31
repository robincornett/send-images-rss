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

		$page    = 'media';
		$section = 'send_rss_section';
		$settings = array(
			array(
				'name'     => 'sendimagesrss_simplify_feed',
				'callback' => 'one_zero',
			),
			array(
				'name'     => 'sendimagesrss_image_size',
				'callback' => 'media_value',
			),
			array(
				'name'     => 'sendimagesrss_alternate_feed',
				'callback' => 'one_zero',
			),
		);

		foreach ( $settings as $setting ) {
			register_setting( $page, $setting['name'], array( $this, $setting['callback'] ) );
		}

		add_settings_section(
			$section,
			__( 'RSS Feeds', 'send-images-rss' ),
			array( $this, 'section_description' ),
			$page
		);

		$fields = array(
			array(
				'id'       => 'sendimagesrss_simplify',
				'title'    => __( 'Simplify Feed', 'send-images-rss' ),
				'callback' => 'field_simplify',
			),
			array(
				'id'       => 'sendimagesrss_image_size_setting',
				'title'    => __( 'RSS Image Size', 'send-images-rss' ),
				'callback' => 'field_image_size',
			),
			array(
				'id'       => 'sendimagesrss_alternate_rss_feed',
				'title'    => __( 'Alternate Feed', 'send-images-rss' ),
				'callback' => 'field_alternate_feed',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				$field['id'],
				'<label for="' . $field['id'] . '">' . $field['title'] . '</label>',
				array( $this, $field['callback'] ),
				$page,
				$section
			);
		}
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
		printf( '<p>%s</p>', __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) );
	}

	/**
	 * Callback for feed simplification setting.
	 *
	 * @since 2.4.0
	 */
	public function field_simplify() {
		$value = get_option( 'sendimagesrss_simplify_feed' );

		printf( '<input type="checkbox" name="sendimagesrss_simplify_feed" id="sendimagesrss_simplify_feed" value="1"%s class="code" /> <label for="sendimagesrss_simplify_feed">%s</label>',
			checked( 1, $value, false ),
			__( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' )
		);

		if ( $value ) {
			printf( '<p class="description">%s</p>', __( 'The only part of your feed which will be modified are your galleries. Email sized images will not be created.', 'send-images-rss' ) );
		}
	}

	/**
	 * Callback for image size field setting.
	 *
	 * @since 2.2.0
	 */
	public function field_image_size() {
		$value = get_option( 'sendimagesrss_image_size', 560 );

		printf( '<label for="sendimagesrss_image_size">%s</label>', __( 'Max Width', 'send-images-rss' ) );
		printf( '<input type="number" step="1" min="200" max="900" id="sendimagesrss_image_size" name="sendimagesrss_image_size" value="%s" class="small-text" />', esc_attr( $value ) );
		printf( '<p class="description">%s</p>', __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' ) );

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

		printf( '<input type="checkbox" name="sendimagesrss_alternate_feed" id="sendimagesrss_alternate_feed" value="1"%s class="code" /> <label for="sendimagesrss_alternate_feed">%s</label>',
			checked( 1, $value, false ),
			__( 'Create a custom feed and use that for sending emails.', 'send-images-rss' )
		);

		if ( $value && ! $simplify ) {
			$url = '?feed=email';
			if ( $pretty_permalinks ) {
				$url = 'feed/email';
			}
			$message = sprintf(
				__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
				esc_url( trailingslashit( home_url() ) . esc_attr( $url ) )
			);

			printf( '<p class="description">%s</p>', $message );

		}
	}

	/**
	 * Error message if both Simplify Feed and Alternate Feed are checked.
	 *
	 * @since 2.4.0
	 *
	 * Error message if feed is set to summary instead of full text.
	 *
	 * @since 2.5.2
	 */
	public function error_message() {

		$screen     = get_current_screen();
		$value      = get_option( 'sendimagesrss_alternate_feed' );
		$simplify   = get_option( 'sendimagesrss_simplify_feed' );
		$rss_option = get_option( 'rss_use_excerpt' );

		if ( '1' === $rss_option && in_array( $screen->id, array( 'options-media', 'options-reading', 'plugins' ) ) ) {
			$message = __( 'Your RSS feed is set to send excerpts instead of full text, so your images will not be processed by the Send Image to RSS plugin. ', 'send-images-rss' );
			if ( 'options-reading' !== $screen->id ) {
				$message .= sprintf( __( 'You can change this on the <a href="%1$s">Settings > Reading page</a>.', 'send-images-rss' ),
					get_admin_url() . 'options-reading.php'
				);
			}
			printf( '<div class="error"><p><strong>%s</strong></p></div>', $message );
		}

		if ( $value && $simplify && 'options-media' === $screen->id ) {
			printf( '<div class="error"><p><strong>%s</strong></p></div>',
				__( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' )
			);
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

		$sendimages_rss_help  = '<h3>' . __( 'Simplify Feed', 'send-images-rss' ) . '</h3>';
		$sendimages_rss_help .= '<p>' . __( 'If you are not concerned about sending your feed out over email and want only your galleries changed from thumbnails to large images, select Simplify Feed.', 'send-images-rss' ) . '</p>';

		$sendimages_rss_help .= '<h3>' . __( 'RSS Image Size', 'send-images-rss' ) . '</h3>';
		$sendimages_rss_help .= '<p>' . __( 'If you have customized your emails to be a nonstandard width, or you are using a template with a sidebar, you will want to change your RSS Image size (width). The default is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content).', 'send-images-rss' ) . '</p>';
		$sendimages_rss_help .= '<p>' . __( 'Note: Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.', 'send-images-rss' ) . '</p>';

		$sendimages_rss_help .= '<h3>' . __( 'Alternate Feed', 'send-images-rss' ) . '</h3>';
		$sendimages_rss_help .= '<p>' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, select this option.', 'send-images-rss' ) . '</p>';
		$sendimages_rss_help .= '<p>' . __( 'If you use custom post types with their own feeds, the alternate feed method will work even with them.', 'send-images-rss' ) . '</p>';

		$screen->add_help_tab( array(
			'id'      => 'sendimagesrss-help',
			'title'   => __( 'Send Images to RSS', 'send-images-rss' ),
			'content' => $sendimages_rss_help,
		) );

	}

}
