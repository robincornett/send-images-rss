<?php

/**
 * Class for adding a new field to the options-media.php page
 */
class Add_MailChimp_Settings {

	/**
	 * Class constructor
	 */
	function __construct() {
		add_filter( 'admin_init' , array( &$this , 'register_fields' ) );
	}

	/**
	 * Add new fields to wp-admin/options-media.php page
	 */
	public function register_fields() {
		register_setting( 'media', 'mailchimp_image_size', 'esc_attr' );
		register_setting( 'media', 'mailchimp_alternate_feed', 'esc_attr' );
		add_settings_section(
			'send_rss_section',
			__( 'RSS Feeds', 'send-images-rss' ),
			array( &$this, 'send_rss_section_callback'),
			'media'
		);
		add_settings_field(
			'mailchimp_image_size_setting',
			'<label for="mailchimp_image_size">' . __( 'RSS image size:' , 'send-images-rss' ) . '</label>',
			array( &$this, 'send_rss_field' ),
			'media',
			'send_rss_section'
		);
		add_settings_field(
			'mailchimp_alternate_rss_feed',
			'<label for="mailchimp_alternate_feed">' . __( 'Alternate feed?' , 'send-images-rss' ) . '</label>',
			array( &$this, 'send_rss_alternate' ),
			'media',
			'send_rss_section'
		);
	}

	public function send_rss_section_callback() {
		echo '<p>' . __( 'The <i>Send Images to RSS</i> plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.', 'send-images-rss' ) . '</p>';
	}
	/**
	 * HTML for extra settings
	 */
	public function send_rss_field() {
		$value = get_option( 'mailchimp_image_size', '' );
		if ( !$value ) {
			$value = 560;
		}

		echo '<label for="mailchimp_image_size">' . __( 'Max Width', 'send-images-rss' ) . '</label>';
		echo '<input type="number" step="1" min="200" id="mailchimp_image_size" name="mailchimp_image_size" value="' . esc_attr( $value ) . '" class="small-text" />';
		echo '<p class="description">' . __( 'Most users should <strong>should not</strong> need to change this number, but if you have customized your emails to be a different width, or you are using a template with a sidebar, you will want to change the width here. The default width is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content).', 'send-images-rss' ) . '</p>';
		echo '<p class="description">' . __( '<strong>Note:</strong> Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.', 'send-images-rss' ) . '</p>';

	}

	public function send_rss_alternate() {
		$value = get_option( 'mailchimp_alternate_feed' );

		echo '<input type="checkbox" name="mailchimp_alternate_feed" id="mailchimp_alternate_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="mailchimp_alternate_feed">' . __( 'Apply sizes only to new custom feed', 'send-images-rss' ) . '</label>';
		echo '<p class="description">' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, check this box.', 'send-images-rss' ) . '</p>';

		if ( $value === '1' ) {
			echo '<p>' . sprintf(
				__( 'Hey! Your new feed is at <a href="%1$s" target="_blank">%1$s</a>.' ),
				esc_url( trailingslashit( home_url() ) . 'feed/?custom=email' )
			) . '</p>';
		}
		elseif ( $value === '' ) {
			echo '<p>' . __( 'The checkbox is NOT checked.', 'send-images-rss' ) . '</p>'; // this line could be tossed but have it in my working code for my own referece.
		}
	}

}
new Add_MailChimp_Settings();
