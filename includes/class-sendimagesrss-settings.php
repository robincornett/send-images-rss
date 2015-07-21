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

	public $rss_setting;
	protected $page = 'sendimagesrss';
	protected $fields;
	protected $rss_option;
	protected $rss_option_words;

	/**
	 * add a submenu page under Appearance
	 * @return submenu Send Images to RSS settings page
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

		$this->rss_option       = get_option( 'rss_use_excerpt' );
		$this->rss_option_words = '1' === $this->rss_option ? __( 'summaries', 'send-images-rss' ) : __( 'full text', 'send-images-rss' );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'load-settings_page_sendimagesrss', array( $this, 'help' ) );
		add_action( 'admin_notices', array( $this, 'do_admin_notice' ) );

	}

	/**
	 * Output the plugin settings form.
	 *
	 * @since 2.7.0
	 */
	public function do_settings_form() {
		$page_title = get_admin_page_title();

		echo '<div class="wrap">';
			echo '<h2>' . esc_attr( $page_title ) . '</h2>';
			echo '<form action="options.php" method="post">';
				settings_fields( 'sendimagesrss' );
				do_settings_sections( 'sendimagesrss' );
				wp_nonce_field( 'sendimagesrss_save-settings', 'sendimagesrss_nonce', false );
				submit_button();
			echo '</form>';
		echo '</div>';
	}

	/**
	 * Add new fields to wp-admin/options-general.php?page=sendimagesrss
	 *
	 * @since 2.2.0
	 */
	public function register_settings() {

		register_setting( 'sendimagesrss', 'sendimagesrss', array( $this, 'do_validation_things' ) );

		$defaults = array(
			'simplify_feed'  => get_option( 'sendimagesrss_simplify_feed', 0 ),
			'image_size'     => get_option( 'sendimagesrss_image_size', 560 ),
			'alternate_feed' => get_option( 'sendimagesrss_alternate_feed', 0 ),
			'thumbnail_size' => 'thumbnail',
			'alignment'      => 'left',
			'excerpt_length' => 75,
			'read_more'      => sprintf( __( 'Continue reading %s at %s.', 'send-images-rss' ), '%%POSTNAME%%', '%%BLOGNAME%%' ),
		);

		$this->rss_setting = get_option( 'sendimagesrss', $defaults );

		$sections = array(
			'general' => array(
				'id'    => 'general',
				'title' => __( 'General Plugin Settings', 'send-images-rss' ),
			),
			'full' => array(
				'id'    => 'full',
				'title' => __( 'Full Text Settings', 'send-images-rss' ),
			),
			'summary' => array(
				'id'    => 'summary',
				'title' => __( 'Summary Settings', 'send-images-rss' ),
			),
		);

		$this->fields = array(
			array(
				'id'       => 'simplify_feed',
				'title'    => __( 'Simplify Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
				'args'     => array( 'setting' => 'simplify_feed', 'label' => __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'image_size',
				'title'    => __( 'RSS Image Size', 'send-images-rss' ),
				'callback' => 'do_number',
				'section'  => 'general',
				'args'     => array( 'setting' => 'image_size', 'min' => 200, 'max' => 900, 'label' => __( 'Max Width', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'alternate_feed',
				'title'    => __( 'Alternate Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
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
				'args'     => array( 'setting' => 'excerpt_length', 'min' => 1, 'max' => 200, 'label' => __( 'Number of Words', 'send-images-rss' ) ),
			),
			array(
				'id'       => 'read_more',
				'title'    => __( 'Read More Text', 'send-images-rss' ),
				'callback' => 'do_text_field',
				'section'  => 'summary',
				'args'     => array( 'setting' => 'read_more' ),
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
				sprintf( '<label for="%s">%s</label>', $field['id'], $field['title'] ),
				array( $this, $field['callback'] ),
				$this->page,
				$sections[ $field['section'] ]['id'],
				empty( $field['args'] ) ? array() : $field['args']
			);
		}
	}

	/**
	 * Callback for general plugin settings section.
	 *
	 * @since 2.4.0
	 */
	public function general_section_description() {
		$description  = __( 'The <em>Send Images to RSS</em> plugin works out of the box without changing any settings. However, depending on your RSS settings, you may want to tweak some things.', 'send-images-rss' );
		$description .= sprintf( __( ' Your feed is currently set to show the <strong>%s</strong> for each post. You can change that on the <a href="%s">Settings > Reading page</a>.', 'send-images-rss' ), $this->rss_option_words, admin_url( 'options-reading.php' ) );
		$description .= __( ' Not sure what a setting does? Check the help tab for more information.', 'send-images-rss' );
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for full text plugin settings section.
	 *
	 * @since 2.7.0
	 */
	public function full_section_description() {
		$description  = __( 'These settings apply only if your RSS feed is set to show the full text of each post.', 'send-images-rss' );
		$description .= sprintf( __( ' Since your feed is set to <strong>%s</strong>, these settings will not apply.', 'send-images-rss' ), $this->rss_option_words );
		if ( '0' === $this->rss_option ) {
			$description = sprintf( __( 'Your RSS feeds are set to show the <strong>%s</strong> of each post, so these settings will apply.', 'send-images-rss' ), $this->rss_option_words );
		}
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for summary plugin settings section.
	 *
	 * @since 2.7.0
	 */
	public function summary_section_description() {
		$description  = __( 'These settings apply only if your RSS feed is set to show the summaries of each post.', 'send-images-rss' );
		$description .= sprintf( __( ' Since your feed is set to <strong>%s</strong>, these settings will not apply.', 'send-images-rss' ), $this->rss_option_words );
		if ( '1' === $this->rss_option ) {
			$description = sprintf( __( 'Your RSS feeds are set to show the <strong>%s</strong> of each post, so these settings will apply.', 'send-images-rss' ), $this->rss_option_words );
		}
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Generic callback to create a checkbox setting.
	 *
	 * @since 2.7.0
	 */
	public function do_checkbox( $args ) {
		printf( '<input type="hidden" name="sendimagesrss[%s]" value="0" />', esc_attr( $args['setting'] ) );
		printf( '<label for="sendimagesrss[%1$s]"><input type="checkbox" name="sendimagesrss[%1$s]" id="sendimagesrss[%1$s]" value="1" %2$s class="code" />%3$s</label>',
			esc_attr( $args['setting'] ),
			checked( 1, esc_attr( $this->rss_setting[ $args['setting'] ] ), false ),
			esc_attr( $args['label'] )
		);
		$function = $args['setting'] . '_description';
		if ( method_exists( $this, $function ) ) {
			$this->$function();
		}
	}

	/**
	 * Generic callback to create a number field setting.
	 *
	 * @since 2.7.0
	 */
	public function do_number( $args ) {

		printf( '<label for="sendimagesrss[%s]">%s</label>', esc_attr( $args['setting'] ), esc_attr( $args['label'] ) );
		printf( '<input type="number" step="1" min="%1$s" max="%2$s" id="sendimagesrss[%3$s]" name="sendimagesrss[%3$s]" value="%4$s" class="small-text" />', (int) $args['min'], (int) $args['max'], esc_attr( $args['setting'] ), esc_attr( $this->rss_setting[ $args['setting'] ] ) );
		$function = $args['setting'] . '_description';
		if ( method_exists( $this, $function ) ) {
			$this->$function();
		}

	}

	/**
	 * Generic callback to create a select/dropdown setting.
	 *
	 * @since 2.7.0
	 */
	public function do_select( $args ) {
		$function = 'pick_' . $args['options'];
		$options  = $this->$function(); ?>
		<select id="sendimagesrss[<?php echo esc_attr( $args['setting'] ); ?>]" name="sendimagesrss[<?php echo esc_attr( $args['setting'] ); ?>]">
			<?php
			foreach ( (array) $options as $name => $key ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $name ), selected( $name, $this->rss_setting[ $args['setting'] ], false ), esc_attr( $key ) );
			} ?>
		</select> <?php
	}

	/**
	 * Generic callback to create a text field.
	 *
	 * @since 2.7.0
	 */
	public function do_text_field( $args ) {
		printf( '<input type="text" id="sendimagesrss[%1$s]" name="sendimagesrss[%1$s]" value="%2$s" class="regular-text" />', esc_attr( $args['setting'] ), esc_attr( $this->rss_setting[ $args['setting'] ] ) );
		$function = $args['setting'] . '_description';
		if ( method_exists( $this, $function ) ) {
			$this->$function();
		}
	}

	/**
	 * Callback to populate the thumbnail size dropdown with available image sizes.
	 * @return array selected sizes with names and dimensions
	 *
	 * @since 2.7.0
	 */
	protected function pick_sizes() {
		$intermediate_sizes = get_intermediate_image_sizes();
		foreach ( $intermediate_sizes as $_size ) {
			$default_sizes = apply_filters( 'send_images_rss_thumbnail_size_list', array( 'thumbnail', 'medium' ) );
			if ( in_array( $_size, $default_sizes ) ) {
				$width  = get_option( $_size . '_size_w' );
				$height = get_option( $_size . '_size_h' );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			} elseif ( 'mailchimp' === $_size ) {
				$width  = $this->rss_setting['image_size'];
				$height = (int) ( $this->rss_setting['image_size'] * 2 );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			}
		}
		return $options;
	}

	/**
	 * Callback to create a dropdown list for featured image alignment.
	 * @return array list of alignment choices.
	 *
	 * @since 2.7.0
	 */
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
	 * Callback for description for image size.
	 * @since 2.7.0
	 */
	protected function image_size_description() {
		$description = __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' );
		printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for description for number of words in excerpt.
	 * @since 2.7.0
	 */
	protected function excerpt_length_description() {
		$description = __( 'Set the target number of words for the RSS summary to have. The final sentence will be complete.', 'send-images-rss' );
		printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
	}
	/**
	 * Callback for alternate feed setting description.
	 *
	 * @since 2.3.0
	 */
	protected function alternate_feed_description() {

		if ( ! $this->rss_setting['alternate_feed'] || $this->rss_setting['simplify_feed'] ) {
			return;
		}
		$pretty_permalinks = get_option( 'permalink_structure' );
		$url               = $pretty_permalinks ? 'feed/email' : '?feed=email';
		$message           = sprintf(
			__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
			esc_url( trailingslashit( home_url() ) . esc_attr( $url ) )
		);
		if ( '1' === $this->rss_option ) {
			$message = __( 'Sorry, your feed is set to show summaries, so no alternate feed can be created.', 'send-images-rss' );
		}

		printf( '<p class="description">%s</p>', wp_kses_post( $message ) );

	}

	/**
	 * Callback to add a description for the read more setting
	 * @return string text with placeholders for read more link on excerpt
	 *
	 * @since 2.7.0
	 */
	protected function read_more_description() {
		$description  = __( 'You can use the following variables: the post link will be added to the entire text.', 'send-images-rss' );
		$description .= '<ul>';
		$description .= sprintf( '<li><strong>%s</strong>: %s</li>', '%%POSTNAME%%', __( 'The name of your post.', 'send-images-rss' ) );
		$description .= sprintf( '<li><strong>%s</strong>: %s</li>', '%%BLOGNAME%%', __( 'The name of your site.', 'send-images-rss' ) );
		$description .= '</ul>';
		printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Validate all settings.
	 * @param  array $new_value new values from settings page
	 * @return array            validated values
	 *
	 * @since 2.7.0
	 */
	public function do_validation_things( $new_value ) {

		if ( empty( $_POST['sendimagesrss_nonce'] ) ) {
			wp_die( esc_attr__( 'Something unexpected happened. Please try again.', 'send-images-rss' ) );
		}

		check_admin_referer( 'sendimagesrss_save-settings', 'sendimagesrss_nonce' );

		foreach ( $this->fields as $field ) {
			if ( 'do_checkbox' === $field['callback'] ) {
				$new_value[ $field['id'] ] = $this->one_zero( $new_value[ $field['id'] ] );
			} elseif ( 'do_select' === $field['callback'] ) {
				$new_value[ $field['id'] ] = esc_attr( $new_value[ $field['id'] ] );
			}
		}

		$new_value['image_size']     = $this->media_value( $new_value['image_size'] );

		$new_value['thumbnail_size'] = esc_attr( $new_value['thumbnail_size'] );

		$new_value['excerpt_length'] = (int) $new_value['excerpt_length'];

		$new_value['read_more']      = sanitize_text_field( $new_value['read_more'] );

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
	protected function one_zero( $new_value ) {
		return (int) (bool) $new_value;
	}

	/**
	 * Returns previous value for image size if not correct
	 * @param  string $new_value New value
	 * @param  string $old_value Previous value
	 * @return string            New or previous value, depending on allowed image size.
	 */
	protected function media_value( $new_value ) {
		if ( ! $new_value || $new_value < 200 || $new_value > 900 ) {
			return $this->rss_setting['image_size'];
		}
		return (int) $new_value;
	}

	/**
	 * Help tab for settings screen
	 * @return help tab with verbose information for plugin
	 *
	 * @since 2.4.0
	 */
	public function help() {
		$screen = get_current_screen();

		$general_help  = '<h3>' . __( 'RSS Image Size', 'send-images-rss' ) . '</h3>';
		$general_help .= '<p>' . __( 'If you have customized your emails to be a nonstandard width, or you are using a template with a sidebar, you will want to change your RSS Image size (width). The default is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content). Mad Mimi users should set this to 530.', 'send-images-rss' ) . '</p>';
		$general_help .= '<p class="description">' . __( 'Note: Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.', 'send-images-rss' ) . '</p>';

		$full_text_help  = '<h3>' . __( 'Simplify Feed', 'send-images-rss' ) . '</h3>';
		$full_text_help .= '<p>' . __( 'If you are not concerned about sending your feed out over email and want only your galleries changed from thumbnails to large images, select Simplify Feed.', 'send-images-rss' ) . '</p>';

		$full_text_help .= '<h3>' . __( 'Alternate Feed', 'send-images-rss' ) . '</h3>';
		$full_text_help .= '<p>' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, select this option.', 'send-images-rss' ) . '</p>';
		$full_text_help .= '<p>' . __( 'If you use custom post types with their own feeds, the alternate feed method will work even with them.', 'send-images-rss' ) . '</p>';

		$summary_help  = '<h3>' . __( 'Featured Image Size', 'send-images-rss' ) . '</h3>';
		$summary_help .= '<p>' . __( 'Select which size image you would like to use in your excerpt/summary.', 'send-images-rss' ) . '</p>';

		$summary_help .= '<h3>' . __( 'Featured Image Alignment', 'send-images-rss' ) . '</h3>';
		$summary_help .= '<p>' . __( 'Set the alignment for your post\'s featured image.', 'send-images-rss' ) . '</p>';

		$summary_help .= '<h3>' . __( 'Excerpt Length', 'send-images-rss' ) . '</h3>';
		$summary_help .= '<p>' . __( 'Set the target number of words you want your excerpt to generally have. The plugin will count that many words, and then add on as many as are required to ensure your summary ends in a complete sentence.', 'send-images-rss' ) . '</p>';

		$summary_help .= '<h3>' . __( 'Read More Text', 'send-images-rss' ) . '</h3>';
		$summary_help .= '<p>' . __( 'Enter the text you want your "read more" link in your feed to contain. You can use placeholders for the post title and blog name.', 'send-images-rss' ) . '</p>';
		$summary_help .= '<p class="description">' . __( 'Hint: "Read More" is probably inadequate for your link\'s anchor text.', 'send-images-rss' ) . '</p>';

		$help_tabs = array(
			array(
				'id'      => 'sendimagesrss_general-help',
				'title'   => __( 'General Settings', 'send-images-rss' ),
				'content' => $general_help,
			),
			array(
				'id'      => 'sendimagesrss_full_text-help',
				'title'   => __( 'Full Text Settings', 'send-images-rss' ),
				'content' => $full_text_help,
			),
			array(
				'id'      => 'sendimagesrss_summary-help',
				'title'   => __( 'Summary Settings', 'send-images-rss' ),
				'content' => $summary_help,
			),
		);

		foreach ( $help_tabs as $tab ) {
			$screen->add_help_tab( $tab );
		}

	}

	/**
	 * Set notices to display for settings incompatibilities/updates.
	 * @return admin notice
	 *
	 * @since 2.7.0
	 */
	public function do_admin_notice() {
		$class       = 'update-nag';
		$message     = sprintf( __( 'Thanks for updating <strong>Send Images to RSS</strong>. There are new features and a <a href="%s">new settings page</a>. Please visit it to verify and resave your settings.', 'send-images-rss' ), admin_url( 'options-general.php?page=sendimagesrss' ) );
		$old_setting = get_option( 'sendimagesrss_image_size' );
		$new_setting = get_option( 'sendimagesrss' );

		if ( $new_setting && ( ! $this->rss_setting['simplify_feed'] || ! $this->rss_setting['alternate_feed'] ) ) {
			return;
		} elseif ( ! $old_setting && ! $new_setting ) {
			$class   = 'updated';
			$message = sprintf( __( 'Thanks for installing <strong>Send Images to RSS</strong>. The plugin works out of the box, but since you\'re installing for the first time, you might visit the <a href="%s">settings page</a> and make sure everything is set the way you want it.', 'send-images-rss' ), admin_url( 'options-general.php?page=sendimagesrss' ) );
		} elseif ( $this->rss_setting['simplify_feed'] && $this->rss_setting['alternate_feed'] ) {
			if ( '1' === $this->rss_option ) {
				return;
			}
			$screen = get_current_screen();
			if ( ! in_array( $screen->id, array( 'settings_page_sendimagesrss', 'options-reading' ) ) ) {
				return;
			}
			$class   = 'error';
			$message = __( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' );
		}
		printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}

}
