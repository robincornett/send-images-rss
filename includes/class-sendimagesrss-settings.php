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
		add_action( 'load-settings_page_sendimagesrss', array( $this, 'help' ) );

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
			'thumbnail_size' => 'mailchimp',
			'alignment'      => 'left',
			'excerpt_length' => 75,
		);

		$this->setting = get_option( 'sendimagesrss', $defaults );
		$section       = 'sendimagesrss';

		$sections = array(
			'general' => array(
				'id'       => 'general',
				'title'    => __( 'General Plugin Settings', 'send-images-rss' ),
			),
			// 'full' => array(
			// 	'id'       => 'full',
			// 	'title'    => __( 'Full Text Settings', 'send-images-rss' ),
			// ),
			'summary' => array(
				'id'       => 'summary',
				'title'    => __( 'Summary Settings', 'send-images-rss' ),
			),
		);

		$this->fields = array(
			array(
				'id'       => 'simplify_feed',
				'title'    => __( 'Simplify Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'general',
				'args'     => array( 'setting' => 'simplify_feed', 'label' => __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'image_size',
				'title'    => __( 'RSS Image Size', 'send-images-rss' ),
				'callback' => 'do_number',
				'section'  => 'general',
				'args'     => array( 'setting' => 'image_size', 'min' => 200, 'max' => 900, 'label' => __( 'Max Width', 'send-images-rss' ), 'description' => __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'alternate_feed',
				'title'    => __( 'Alternate Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'general',
				'args'     => array( 'setting' => 'alternate_feed', 'label' => __( 'Create a custom feed and use that for sending emails.', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'thumbnail_size',
				'title'    => __( 'Featured Image Size', 'send-images-rss' ),
				'callback' => 'do_select',
				'section'  => 'summary',
				'args'     => array( 'setting' => 'thumbnail_size', 'options' => 'sizes' ),
			),
			array(
				'id'       => 'alignment',
				'title'    => __( 'Featured Image Alignment', 'send-images-rss' ),
				'callback' => 'do_select',
				'section'  => 'summary',
				'args'     => array( 'setting' => 'alignment', 'options' => 'alignment' ),
			),
			array(
				'id'       => 'excerpt_length',
				'title'    => __( 'Excerpt Length', 'send-images-rss' ),
				'callback' => 'do_number',
				'section'  => 'summary',
				'args'     => array( 'setting' => 'excerpt_length', 'min' => 1, 'max' => 200, 'label' => __( 'Number of Words', 'send-images-rss' ), 'description' => __( 'Set the minimum number of words for the RSS summary to have. The final sentence will be complete.', 'send-images-rss' ) ),
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

		foreach ( $this->fields as $field ) {
			add_settings_field(
				'[' . $field['id'] . ']',
				'<label for="' . $field['id'] . '">' . $field['title'] . '</label>',
				array( $this, $field['callback'] ),
				$this->page,
				$sections[ $field['section'] ]['id'],
				empty( $field['args'] ) ? array() : $field['args']
			);
		}
	}

	/**
	 * Callback for RSS Feeds section.
	 *
	 * @since 2.4.0
	 */
	public function general_section_description() {
		printf( '<p>%s</p>', wp_kses_post( __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) ) );
	}

	public function full_section_description() {
		printf( '<p>%s</p>', esc_attr__( 'If your RSS feed is set to full content, these settings will apply.', 'send-images-rss' ) );
	}

	public function summary_section_description() {
		printf( '<p>%s</p>', esc_attr__( 'If your RSS feed is set to summaries, these settings will apply.', 'send-images-rss' ) );
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

	public function do_number( $args ) {

		printf( '<label for="sendimagesrss[%s]">%s</label>', esc_attr( $args['setting'] ), esc_attr( $args['label'] ) );
		printf( '<input type="number" step="1" min="%1$s" max="%2$s" id="sendimagesrss[%3$s]" name="sendimagesrss[%3$s]" value="%4$s" class="small-text" />', (int) $args['min'], (int) $args['max'], esc_attr( $args['setting'] ), esc_attr( $this->setting[ $args['setting'] ] ) );
		printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );

	}

	public function do_select( $args ) {
		$function = 'pick_' . $args['options'];
		$options  = $this->$function(); ?>
		<select id="sendimagesrss[<?php echo esc_attr( $args['setting'] ); ?>]" name="sendimagesrss[<?php echo esc_attr( $args['setting'] ); ?>]">
			<?php
			foreach ( (array) $options as $name => $key ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $name ), selected( $name, $this->setting[ $args['setting'] ], false ), esc_attr( $key ) );
			} ?>
		</select> <?php
	}

	protected function pick_sizes() {
		$intermediate_sizes = get_intermediate_image_sizes();
		foreach ( $intermediate_sizes as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium' ) ) ) {
				$width  = get_option( $_size . '_size_w' );
				$height = get_option( $_size . '_size_h' );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			} elseif ( 'mailchimp' === $_size ) {
				$width  = $this->setting['image_size'];
				$height = (int) ( $this->setting['image_size'] * 2 );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			}
		}
		return $options;
	}

	protected function pick_alignment() {
		$options = array(
			'left'   => 'Left',
			'right'  => 'Right',
			'center' => 'Center',
			'none'   => 'None',
		);
		return $options;
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

		foreach ( $this->fields as $field ) {
			if ( 'do_checkbox' === $field['callback'] ) {
				$new_value[ $field['id'] ] = $this->one_zero( $new_value[ $field['id'] ] );
			}
			if ( 'do_select' === $field['callback'] ) {
				$new_value[ $field['id'] ] = esc_attr( $new_value[ $field['id'] ] );
			}
		}

		$new_value['image_size']     = $this->media_value( $new_value['image_size'] );

		$new_value['thumbnail_size'] = esc_attr( $new_value['thumbnail_size'] );

		$new_value['excerpt_length'] = (int) $new_value['excerpt_length'];

		return $new_value;

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
			return $this->setting['image_size'];
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
		$value      = $this->setting['alternate_feed'];
		$simplify   = $this->setting['simplify_feed'];
		$rss_option = get_option( 'rss_use_excerpt' );

		if ( '1' === $rss_option && in_array( $screen->id, array( 'settings_page_sendimagesrss', 'options-reading', 'plugins' ) ) ) {
			$message = __( 'Your RSS feed is set to send excerpts instead of full text, so your images will not be processed by the Send Image to RSS plugin. ', 'send-images-rss' );
			if ( 'options-reading' !== $screen->id ) {
				$message .= sprintf( __( 'You can change this on the <a href="%1$s">Settings > Reading page</a>.', 'send-images-rss' ),
					get_admin_url() . 'options-reading.php'
				);
			}
			printf( '<div class="error"><p><strong>%s</strong></p></div>', wp_kses_post( $message ) );
		}

		if ( $value && $simplify && 'options-media' === $screen->id ) {
			printf( '<div class="error"><p><strong>%s</strong></p></div>',
				esc_attr__( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' )
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
