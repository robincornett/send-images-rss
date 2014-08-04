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
			'MailChimp Media Settings',
			array( &$this, 'send_rss_section_callback'),
			'media'
		);
		add_settings_field(
			'mailchimp_image_size_setting',
			'<label for="mailchimp_image_size">' . __( 'MailChimp/RSS feed image size:' , 'send_images_rss' ) . '</label>',
			array( &$this, 'send_rss_field' ),
			'media',
			'send_rss_section'
		);
		add_settings_field(
			'mailchimp_alternate_rss_feed',
			'<label for="mailchimp_alternate_feed">' . __( 'Alternate Feed Method?' , 'send_images_rss' ) . '</label>',
			array( &$this, 'send_rss_alternate' ),
			'media',
			'send_rss_section'
		);
	}

	public function send_rss_section_callback() {
		echo '<p>The Send Images to RSS plugin works out of the box without changing any settings. However, if you want to customize your image size and do not want to change the default feed, change those items here.</p>';
	}
	/**
	 * HTML for extra settings
	 */
	public function send_rss_field() {
		$value = get_option( 'mailchimp_image_size', '' );
		if ( !$value ) {
			$value = 560;
		}

		echo '<p> Most users <strong>should not</strong> need to change this number, but if you have customized your emails to be a different width, or you are using a template with a sidebar, you will want to change the width here. The default width is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content).</p><br />';
		echo '<input type="number" step="1" min="200" id="mailchimp_image_size" name="mailchimp_image_size" value="' . esc_attr( $value ) . '" class="small-text" />';
		echo '<label for="mailchimp_image_size"> <strong>Note:</strong> Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images&rsquo; style.</label>';

	}

	public function send_rss_alternate() {
		$value = get_option( 'mailchimp_alternate_feed' );

		echo '<p>By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails/MailChimp only, check this box.</p><br />';
		echo '<input type="checkbox" name="mailchimp_alternate_feed" id="mailchimp_alternate_feed" value="1"' . checked( 1, $value, false ) . ' class="code" /> <label for="mailchimp_alternate_feed">If checked, this will <em>disable</em> the plugin for all feeds except for a custom feed, which will be for the main blog feed.</label>';

		if ( $value === '1' ) {
			echo '<br /><p>Hey! Your new feed is at <a href="' . home_url() . '/feed/?custom=email" target="_blank">' . home_url() . '/feed/?custom=email</a>.</p>';
		}
		elseif ( $value === '' ) {
			echo '<p>The checkbox is NOT checked.</p>'; // this line could be tossed but have it in my working code for my own referece.
		}
	}

}
new Add_MailChimp_Settings();
