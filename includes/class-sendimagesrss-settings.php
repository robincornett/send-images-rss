<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2019 Robin Cornett
 * @license   GPL-2.0+
 */

/**
 * Class for adding a new settings page to the WordPress admin, under Settings.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS_Settings {

	/**
	 * Option registered by plugin.
	 * @var array
	 */
	protected $rss_setting;

	/**
	 * Slug for settings page.
	 * @var string
	 */
	protected $page = 'sendimagesrss';

	/**
	 * Settings fields registered by plugin.
	 * @var array
	 */
	protected $fields;

	/**
	 * RSS feed option set in WP (full text or summaries).
	 * @var string 1/0
	 */
	protected $rss_option;

	/**
	 * translation of $rss_option into text.
	 * @var string full text/summaries
	 */
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
		$this->rss_setting      = $this->get_rss_setting();

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		$sections     = $this->register_sections();
		$this->fields = $this->register_fields();
		$this->add_sections( $sections );
		$this->add_fields( $this->fields, $sections );

	}

	/**
	 * Output the plugin settings form.
	 *
	 * @since 3.0.0
	 */
	public function do_settings_form() {

		echo '<div class="wrap">';
			echo '<h1>' . esc_attr( get_admin_page_title() ) . '</h1>';
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
	}

	/**
	 * @return array Setting for plugin, or defaults.
	 */
	public function get_rss_setting() {

		$old_setting = get_option( 'sendimagesrss_image_size' );
		$defaults    = array(
			'simplify_feed'  => $old_setting ? get_option( 'sendimagesrss_simplify_feed', 0 ) : 0,
			'image_size'     => $old_setting ? get_option( 'sendimagesrss_image_size', 560 ) : 560,
			'alternate_feed' => $old_setting ? get_option( 'sendimagesrss_alternate_feed', 0 ) : 0,
			'thumbnail_size' => 'thumbnail',
			'alignment'      => 'left',
			'excerpt_length' => 75,
			/* translators: placeholders for 1. the post title 2. the site title */
			'read_more'      => sprintf( __( 'Continue reading %1$s at %2$s.', 'send-images-rss' ), '%%POSTNAME%%', '%%BLOGNAME%%' ),
			'featured_image' => 0,
			'change_small'   => 1,
			'process_both'   => 0,
		);

		$setting = get_option( 'sendimagesrss', $defaults );
		return wp_parse_args( $setting, $defaults );
	}

	/**
	 * Register sections for settings page.
	 *
	 * @since 3.0.0
	 */
	protected function register_sections() {

		$sections = array(
			'general' => array(
				'id'    => 'general',
				'title' => __( 'General Image Settings', 'send-images-rss' ),
			),
			'full'    => array(
				'id'    => 'full',
				'title' => __( 'Full Text Settings', 'send-images-rss' ),
			),
			'summary' => array(
				'id'    => 'summary',
				'title' => __( 'Summary Settings', 'send-images-rss' ),
			),
		);
		return $sections;
	}

	/**
	 * Adds the registered sections to this settings page.
	 * @param $sections array sections for this settings page.
	 */
	protected function add_sections( $sections ) {
		foreach ( $sections as $section ) {
			add_settings_section(
				$section['id'],
				$section['title'],
				array( $this, $section['id'] . '_section_description' ),
				$this->page
			);
		}
	}

	/**
	 * Register settings fields
	 * @return array          settings fields
	 *
	 * @since 3.0.0
	 */
	protected function register_fields() {

		$fields = array(
			array(
				'id'       => 'simplify_feed',
				'title'    => __( 'Simplify Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
				'args'     => array(
					'setting' => 'simplify_feed',
					'label'   => __( 'Convert galleries only; do not fix feeds for email.', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'image_size',
				'title'    => __( 'RSS/Email Image Width', 'send-images-rss' ),
				'callback' => 'do_number',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'image_size',
					'min'     => 200,
					'max'     => 900,
					'label'   => __( 'Max Width', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'alternate_feed',
				'title'    => __( 'Alternate Feed', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
				'args'     => array(
					'setting' => 'alternate_feed',
					'label'   => __( 'Create a custom feed and use that for sending emails.', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'thumbnail_size',
				'title'    => __( 'Featured Image Size', 'send-images-rss' ),
				'callback' => 'do_select',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'thumbnail_size',
					'options' => 'sizes',
				),
			),
			array(
				'id'       => 'alignment',
				'title'    => __( 'Featured Image Alignment', 'send-images-rss' ),
				'callback' => 'do_select',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'alignment',
					'options' => 'alignment',
				),
			),
			array(
				'id'       => 'excerpt_length',
				'title'    => __( 'Excerpt Length', 'send-images-rss' ),
				'callback' => 'do_number',
				'section'  => 'summary',
				'args'     => array(
					'setting' => 'excerpt_length',
					'min'     => 1,
					'max'     => 200,
					'label'   => __( 'Number of Words', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'read_more',
				'title'    => __( 'Read More Text', 'send-images-rss' ),
				'callback' => 'do_text_field',
				'section'  => 'summary',
				'args'     => array( 'setting' => 'read_more' ),
			),
			array(
				'id'       => 'featured_image',
				'title'    => __( 'Featured Image', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
				'args'     => array(
					'setting' => 'featured_image',
					'label'   => __( 'Add the featured image to the beginning of the full post (uses General Image Settings).', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'process_both',
				'title'    => __( 'Process Both Feeds', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'process_both',
					'label'   => __( 'Process both the full text and summary of the feed.', 'send-images-rss' ),
				),
			),
			array(
				'id'       => 'change_small',
				'title'    => __( 'Change Small Images', 'send-images-rss' ),
				'callback' => 'do_checkbox',
				'section'  => 'full',
				'args'     => array(
					'setting' => 'change_small',
					'label'   => __( 'If a larger version of the image exists, the small image will be replaced in the email.', 'send-images-rss' ),
				),
			),
		);

		return $fields;
	}

	/**
	 * Adds the settings fields to each section.
	 * @param $fields array
	 * @param $sections array
	 */
	protected function add_fields( $fields, $sections ) {
		foreach ( $fields as $field ) {
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
		$description = __( 'The <em>Send Images to RSS</em> plugin works out of the box without changing any settings. However, depending on your RSS settings, you may want to tweak some things.', 'send-images-rss' );
		/* translators: 1. the RSS feed option (full content or descriptions) 2. link to the settings page */
		$description .= sprintf( __( ' Your feed is currently set to show the <strong>%1$s</strong> for each post. You can change that on the <a href="%2$s">Settings > Reading page</a>.', 'send-images-rss' ), $this->rss_option_words, admin_url( 'options-reading.php' ) );
		$description .= __( ' Not sure what a setting does? Check the help tab for more information.', 'send-images-rss' );
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for full text plugin settings section.
	 *
	 * @since 3.0.0
	 */
	public function full_section_description() {
		$description = __( 'These settings apply only if your RSS feed is set to show the full text of each post.', 'send-images-rss' );
		/* translators: the the RSS feed option (full content or descriptions) */
		$description .= sprintf( __( ' Since your feed is set to <strong>%s</strong>, these settings will not apply.', 'send-images-rss' ), $this->rss_option_words );
		if ( '0' === $this->rss_option ) {
			/* translators: the RSS feed option (full content or descriptions) */
			$description = sprintf( __( 'Your RSS feeds are set to show the <strong>%s</strong> of each post, so these settings will apply.', 'send-images-rss' ), $this->rss_option_words );
		}
		if ( $this->rss_setting['process_both'] ) {
			$description = __( 'These settings apply to the full content RSS feed.', 'send-images-rss' );
		}
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	public function images_section_description() {
		$description = __( 'Modify your image settings here.', 'send-images-rss' );
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for summary plugin settings section.
	 *
	 * @since 3.0.0
	 */
	public function summary_section_description() {
		$description = __( 'These settings apply only if your RSS feed is set to show the summaries of each post.', 'send-images-rss' );
		/* translators: the RSS feed option (full content or descriptions) */
		$description .= sprintf( __( ' Since your feed is set to <strong>%s</strong>, these settings will not apply.', 'send-images-rss' ), $this->rss_option_words );
		if ( '1' === $this->rss_option ) {
			/* translators: the RSS feed option (full content or descriptions) */
			$description = sprintf( __( 'Your RSS feeds are set to show the <strong>%s</strong> of each post, so these settings will apply.', 'send-images-rss' ), $this->rss_option_words );
		}
		if ( $this->rss_setting['process_both'] ) {
			$description = __( 'These settings apply to the excerpt/summary RSS feed.', 'send-images-rss' );
		}
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Generic callback to create a checkbox setting.
	 *
	 * @since 3.0.0
	 */
	public function do_checkbox( $args ) {
		printf( '<input type="hidden" name="%s[%s]" value="0" />', esc_attr( $this->page ), esc_attr( $args['setting'] ) );
		printf(
			'<label for="%1$s[%2$s]"><input type="checkbox" name="%1$s[%2$s]" id="%1$s[%2$s]" value="1" %3$s class="code" />%4$s</label>',
			esc_attr( $this->page ),
			esc_attr( $args['setting'] ),
			checked( 1, esc_attr( $this->rss_setting[ $args['setting'] ] ), false ),
			esc_attr( $args['label'] )
		);
		$this->do_description( $args['setting'] );
	}

	/**
	 * Generic callback to create a number field setting.
	 *
	 * @since 3.0.0
	 */
	public function do_number( $args ) {

		printf( '<label for="%s[%s]">%s</label>', esc_attr( $this->page ), esc_attr( $args['setting'] ), esc_attr( $args['label'] ) );
		printf(
			'<input type="number" step="1" min="%1$s" max="%2$s" id="%5$s[%3$s]" name="%5$s[%3$s]" value="%4$s" class="small-text" />',
			(int) $args['min'],
			(int) $args['max'],
			esc_attr( $args['setting'] ),
			esc_attr( $this->rss_setting[ $args['setting'] ] ),
			esc_attr( $this->page )
		);
		$this->do_description( $args['setting'] );

	}

	/**
	 * Generic callback to create a select/dropdown setting.
	 *
	 * @since 3.0.0
	 */
	public function do_select( $args ) {
		$function = 'pick_' . $args['options'];
		$options  = $this->$function();
		printf( '<label for="%s[%s]">', esc_attr( $this->page ), esc_attr( $args['setting'] ) );
		printf( '<select id="%1$s[%2$s]" name="%1$s[%2$s]">', esc_attr( $this->page ), esc_attr( $args['setting'] ) );
		foreach ( (array) $options as $name => $key ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $name ), selected( $name, $this->rss_setting[ $args['setting'] ], false ), esc_attr( $key ) );
		}
		echo '</select>';
		$this->do_description( $args['setting'] );
	}

	/**
	 * Generic callback to create a text field.
	 *
	 * @since 3.0.0
	 */
	public function do_text_field( $args ) {
		printf(
			'<input type="text" id="%3$s[%1$s]" aria-label="%3$s[%1$s]" name="%3$s[%1$s]" value="%2$s" class="regular-text" />',
			esc_attr( $args['setting'] ),
			esc_attr( $this->rss_setting[ $args['setting'] ] ),
			esc_attr( $this->page )
		);
		$this->do_description( $args['setting'] );
	}

	/**
	 * Callback to populate the thumbnail size dropdown with available image sizes.
	 * @return array selected sizes with names and dimensions
	 *
	 * @since 3.0.0
	 */
	protected function pick_sizes() {
		$options['none']    = __( 'No Image', 'send-images-rss' );
		$intermediate_sizes = get_intermediate_image_sizes();
		foreach ( $intermediate_sizes as $_size ) {
			$default_sizes = apply_filters( 'send_images_rss_thumbnail_size_list', array( 'thumbnail', 'medium' ) );
			if ( in_array( $_size, $default_sizes, true ) ) {
				$width             = get_option( $_size . '_size_w' );
				$height            = get_option( $_size . '_size_h' );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			} elseif ( 'mailchimp' === $_size ) {
				$width             = $this->rss_setting['image_size'];
				$height            = (int) ( $this->rss_setting['image_size'] * 2 );
				$options[ $_size ] = sprintf( '%s ( %sx%s )', $_size, $width, $height );
			}
		}
		return $options;
	}

	/**
	 * Callback to create a dropdown list for featured image alignment.
	 * @return array list of alignment choices.
	 *
	 * @since 3.0.0
	 */
	protected function pick_alignment() {
		$options = array(
			'left'   => __( 'Left', 'send-images-rss' ),
			'right'  => __( 'Right', 'send-images-rss' ),
			'center' => __( 'Center', 'send-images-rss' ),
			'none'   => __( 'None', 'send-images-rss' ),
		);
		return $options;
	}

	/**
	 * Generic callback to display a field description.
	 * @param  string $args setting name used to identify description callback
	 * @return string       Description to explain a field.
	 */
	protected function do_description( $args ) {
		$function = $args . '_description';
		if ( ! method_exists( $this, $function ) ) {
			return;
		}
		$description = $this->$function();
		printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for description for email/image size.
	 * @since 3.0.0
	 */
	protected function image_size_description() {
		return __( 'Most users should <strong>should not</strong> need to change this number.', 'send-images-rss' );
	}

	/**
	 * Callback for description for featured image size.
	 * @return string|void
	 * since 3.1.0
	 */
	protected function thumbnail_size_description() {
		return __( 'The featured image will be added to the excerpt if your feed is set to summary, or if you enable the featured image under the full text settings.', 'send-images-rss' );
	}

	/**
	 * Callback for description for number of words in excerpt.
	 * @since 3.0.0
	 */
	protected function excerpt_length_description() {
		return __( 'Set the target number of words for the RSS summary to have. The final sentence will be complete.', 'send-images-rss' );
	}

	/**
	 * Callback for alternate feed setting description.
	 *
	 * @since 2.3.0
	 */
	protected function alternate_feed_description() {

		if ( ! $this->rss_setting['alternate_feed'] ) {
			return '';
		}
		$pretty_permalinks = get_option( 'permalink_structure' );
		$url               = $pretty_permalinks ? 'feed/email' : '?feed=email';
		$description       = sprintf(
			/* translators: the link to the new RSS feed */
			__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.', 'send-images-rss' ),
			esc_url( trailingslashit( home_url() ) . esc_attr( $url ) )
		);
		if ( $this->rss_setting['simplify_feed'] ) {
			$description = __( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' );
		}

		return $description;

	}

	/**
	 * Callback to add a description for the read more setting
	 * @return string text with placeholders for read more link on excerpt
	 *
	 * @since 3.0.0
	 */
	protected function read_more_description() {
		$description  = __( 'You can use the following variables: the post link will be added to the entire text.', 'send-images-rss' );
		$description .= '<ul>';
		$description .= sprintf( '<li><strong>%s</strong>: %s</li>', '%%POSTNAME%%', __( 'The name of your post.', 'send-images-rss' ) );
		$description .= sprintf( '<li><strong>%s</strong>: %s</li>', '%%BLOGNAME%%', __( 'The name of your site.', 'send-images-rss' ) );
		$description .= '</ul>';
		return $description;
	}

	/**
	 * Add a description to the new featured image checkbox.
	 * @return string|void
	 * since 3.1.0
	 */
	protected function featured_image_description() {
		$description = __( 'Note: adding the featured image to the full text RSS feed may result in duplicate images, depending on how your theme or another plugin is configured.', 'send-images-rss' );
		if ( class_exists( 'Display_Featured_Image_Genesis' ) ) {
			$setting = get_option( 'displayfeaturedimagegenesis', false );
			if ( isset( $setting['feed_image'] ) && $setting['feed_image'] && '0' === $this->rss_option && $this->rss_setting['featured_image'] ) {
				/* translators: link to the Display Featured Images for Genesis plugin settings page */
				$description .= ' <strong>' . sprintf( __( 'Hold on there, cowboy! You are attempting to add the featured image to your feed twice: once here and once over in <a href="%s">Display Featured Image for Genesis</a>. Please check the help tab on this screen for suggestions of how to fix this issue.', 'send-images-rss' ), esc_url( admin_url( 'themes.php?page=displayfeaturedimagegenesis' ) ) ) . '</strong>';
			}
		}
		return $description;
	}

	protected function process_both_description() {
		if ( '1' !== $this->rss_option ) {
			return;
		}
		return __( 'This setting will not take effect until your RSS feed settings are changed to show the full text, not summaries.', 'send-images-rss' );
	}

	/**
	 * Validate all settings.
	 * @param  array $new_value new values from settings page
	 * @return array            validated values
	 *
	 * @since 3.0.0
	 */
	public function do_validation_things( $new_value ) {

		if ( empty( $_POST['sendimagesrss_nonce'] ) ) {
			wp_die( esc_attr__( 'Something unexpected happened. Please try again.', 'send-images-rss' ) );
		}

		check_admin_referer( 'sendimagesrss_save-settings', 'sendimagesrss_nonce' );
		$new_value = array_merge( $this->rss_setting, $new_value );

		foreach ( $this->fields as $field ) {
			switch ( $field['callback'] ) {
				case 'do_checkbox':
					$new_value[ $field['id'] ] = $this->one_zero( $new_value[ $field['id'] ] );
					break;

				case 'do_select':
					$new_value[ $field['id'] ] = esc_attr( $new_value[ $field['id'] ] );
					break;

				case 'do_number':
					$new_value[ $field['id'] ] = (int) $new_value[ $field['id'] ];
					break;

				case 'do_text_field':
					$new_value[ $field['id'] ] = sanitize_text_field( $new_value[ $field['id'] ] );
					break;
			}
		}

		$new_value['image_size']     = $this->check_value( $new_value['image_size'], $this->rss_setting['image_size'], 200, 900 );
		$new_value['excerpt_length'] = $this->check_value( $new_value['excerpt_length'], $this->rss_setting['excerpt_length'], 1, 200 );

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
	 * @deprecated 3.1.2 and using check_value() instead.
	 */
	protected function media_value( $new_value ) {
		if ( ! $new_value || $new_value < 200 || $new_value > 900 ) {
			return $this->rss_setting['image_size'];
		}
		return (int) $new_value;
	}

	/**
	 * Check the numeric value against the allowed range. If it's within the range, return it; otherwise, return the old value.
	 * @param $new_value int new submitted value
	 * @param $old_value int old setting value
	 * @param $min int minimum value
	 * @param $max int maximum value
	 *
	 * @return int
	 */
	protected function check_value( $new_value, $old_value, $min, $max ) {
		if ( $new_value >= $min && $new_value <= $max ) {
			return (int) $new_value;
		}
		return (int) $old_value;
	}
}
