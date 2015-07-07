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

	protected $page = 'sendimagesrss';
	protected $setting;
	protected $fields;

	/**
	 * add a submenu page under Appearance
	 * @return submenu Display Featured image settings page
	 * @since  1.4.0
	 */
	public function do_submenu_page() {

		add_options_page(
			__( 'Send Images to RSS Settings', 'send-images-rss' ),
			__( 'Send Images to RSS', 'send-images-rss' ),
			'manage_options',
			$this->page,
			array( $this, 'do_settings_form' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );

	}

	public function do_settings_form() {
		$page_title = get_admin_page_title();

		echo '<div class="wrap">';
			echo '<h2>' . esc_attr( $page_title ) . '</h2>';
			echo '<form action="options.php" method="post">';
				settings_fields( 'sendimagesrss' );
				do_settings_sections( 'sendimagesrss' );
				wp_nonce_field( 'sendimagesrss_save-settings', 'sendimagesrss_nonce', false );
				submit_button();
				settings_errors();
			echo '</form>';
		echo '</div>';
	}

	/**
	 * Add new fields to wp-admin/options-media.php page.
	 *
	 * @since 2.2.0
	 */
	public function register_settings() {

		register_setting( 'sendimagesrss', 'sendimagesrss', array( $this, 'do_validation_things' ) );

		// Original Send Images RSS Settings
		$simplify       = get_option( 'sendimagesrss_simplify_feed', 0 );
		$size           = get_option( 'sendimagesrss_image_size', 560 );
		$alternate_feed = get_option( 'sendimagesrss_alternate_feed', 0 );

		$defaults = array(
			'simplify_feed'  => $simplify ? $simplify : 0,
			'image_size'     => $size ? $size : 560,
			'alternate_feed' => $alternate_feed ? $alternate_feed : 0,
		);

		$this->setting = get_option( 'sendimagesrss', $defaults );
		$section       = 'sendimagesrss';

		$sections = array(
			'general' => array(
				'id'       => 'general',
				'title'    => __( 'General Plugin Settings', 'send-images-rss' ),
			),
			'full' => array(
				'id'       => 'full',
				'title'    => __( 'Full Text Settings', 'send-images-rss' ),
			),
			'summary' => array(
				'id'       => 'summary',
				'title'    => __( 'Summary Settings', 'send-images-rss' ),
			),
		);

		$fields = array(
			array(
				'id'       => 'simplify_feed',
				'title'    => __( 'Simplify Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => $sections['general']['id'],
				'args'     => array( 'setting' => 'simplify_feed', 'label' => __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'image_size',
				'title'    => __( 'RSS Image Size', 'send-images-rss' ),
				'callback' => 'field_image_size',
				'section'  => $sections['general']['id'],
			),
			array(
				'id'       => 'alternate_feed',
				'title'    => __( 'Alternate Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => $sections['general']['id'],
				'args'     => array( 'setting' => 'alternate_feed', 'label' => __( 'Create a custom feed and use that for sending emails.', 'send-images-rss' ) ),
			),
		);

		foreach ( $sections as $section ) {
			add_settings_section(
				$section['id'],
				$section['title'],
				array( $this, $section['id'] . '_section_description' ),
				$this->page
			);
		}

		foreach ( $fields as $field ) {
			add_settings_field(
				'[' . $field['id'] . ']',
				'<label for="' . $field['id'] . '">' . $field['title'] . '</label>',
				array( $this, $field['callback'] ),
				$this->page,
				$field['section'],
				empty( $field['args'] ) ? array() : $field['args']
			);
		}
	}

	public function do_checkbox( $args ) {
		printf( '<input type="hidden" name="sendimagesrss[%s]" value="0" />', esc_attr( $args['setting'] ) );
		printf( '<label for="sendimagesrss[%1$s]"><input type="checkbox" name="sendimagesrss[%1$s]" id="sendimagesrss[%1$s]" value="1" %2$s class="code" />%3$s</label>',
			esc_attr( $args['setting'] ),
			checked( 1, esc_attr( $this->setting[ $args['setting'] ] ), false ),
			esc_attr( $args['label'] )
		);
		if ( 'alternate_feed' === $args['setting'] ) {
			$this->field_alternate_feed();
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
	public function general_section_description() {
		printf( '<p>%s</p>', __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) );
	}

	public function full_section_description() {
		printf( '<p>%s</p>', __( 'If your RSS feed is set to full content, these settings will apply.', 'send-images-rss' ) );
	}

	public function summary_section_description() {
		printf( '<p>%s</p>', __( 'If your RSS feed is set to summaries, these settings will apply.', 'send-images-rss' ) );
	}

	/**
	 * Callback for image size field setting.
	 *
	 * @since 2.2.0
	 */
	public function field_image_size() {
		$value = $this->setting['image_size'];

		printf( '<label for="sendimagesrss[image_size]">%s</label>', __( 'Max Width', 'send-images-rss' ) );
		printf( '<input type="number" step="1" min="200" max="900" id="sendimagesrss[image_size]" name="sendimagesrss[image_size]" value="%s" class="small-text" />', esc_attr( $value ) );
		printf( '<p class="description">%s</p>', __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' ) );

	}

	/**
	 * Callback for alternate feed setting.
	 *
	 * @since 2.3.0
	 */
	public function field_alternate_feed() {

		if ( $this->setting['alternate_feed'] && ! $this->setting['simplify_feed'] ) {
			$url               = '?feed=email';
			$pretty_permalinks = get_option( 'permalink_structure' );
			if ( $pretty_permalinks ) {
				$url = 'feed/email';
			}
			$message = sprintf(
				__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
				esc_url( trailingslashit( home_url() ) . esc_attr( $url ) )
			);

			printf( '<p class="description">%s</p>', wp_kses_post( $message ) );

		}
	}

	public function do_validation_things( $new_value ) {

		if ( empty( $_POST['sendimagesrss_nonce'] ) ) {
			wp_die( esc_attr__( 'Something unexpected happened. Please try again.', 'send-images-rss' ) );
		}

		check_admin_referer( 'sendimagesrss_save-settings', 'sendimagesrss_nonce' );

		$new_value['image_size']   = $this->media_value( $new_value['image_size'] );

		// validate all checkbox fields
		foreach ( $this->fields as $field ) {
			if ( 'do_checkbox' !== $field['callback'] ) {
				continue;
			}
			$new_value[ $field['id'] ] = $this->one_zero( $new_value[ $field['id'] ] );
		}

		return $new_value;

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
