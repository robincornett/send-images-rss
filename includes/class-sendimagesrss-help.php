<?php
/**
 * @copyright $year Robin Cornett
 */
 
class SendImagesRSS_Help {
	/**
	 * Help tab for settings screen
	 * @return help tab with verbose information for plugin
	 *
	 * @since 2.4.0
	 */
	public function help() {
		$screen    = get_current_screen();
		$help_tabs = $this->define_help_tabs();
		foreach ( $help_tabs as $tab ) {
			$screen->add_help_tab( $tab );
		}
	}

	/**
	 * Define the help tabs for the settings page.
	 * @return array
	 * @since 3.1.2
	 */
	protected function define_help_tabs() {
		return array(
			array(
				'id'      => 'sendimagesrss_general-help',
				'title'   => __( 'General Image Settings', 'send-images-rss' ),
				'content' => $this->general(),
			),
			array(
				'id'      => 'sendimagesrss_full_text-help',
				'title'   => __( 'Full Text Settings', 'send-images-rss' ),
				'content' => $this->full_text(),
			),
			array(
				'id'      => 'sendimagesrss_summary-help',
				'title'   => __( 'Summary Settings', 'send-images-rss' ),
				'content' => $this->summary(),
			),
		);
	}

	/**
	 * Help text for the general plugin settings.
	 * @return string
	 * @since 3.1.2
	 */
	protected function general() {
		$help  = '<h3>' . __( 'RSS/Email Image Width', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'If you have customized your emails to be a nonstandard width, or you are using a template with a sidebar, you will want to change your RSS/Email Image width. The default is 560 pixels, which is the content width of a standard single column email (600 pixels wide with 20 pixels padding on the content). Mad Mimi users should set this to 530.', 'send-images-rss' ) . '</p>';
		$help .= '<p class="description">' . __( 'Note: Changing the width here will not affect previously uploaded images, but it will affect the max-width applied to images\' style.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Featured Image Size', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Select which size image you would like to use in your excerpt/summary.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Featured Image Alignment', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Set the alignment for your post\'s featured image.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Process Both Feeds', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Some users like to allow subscribers who use Feedly or another RSS reader to read the full post, with images, but use the summary for email subscribers. To get images processed on both, set your feed settings to Full Text, and check this option.', 'send-images-rss' ) . '</p>';

		return $help;
	}

	/**
	 * Help text for the full text settings.
	 * @return string
	 * @since 3.1.2
	 */
	protected function full_text() {

		$help = '<h3>' . __( 'Simplify Feed', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'If you are not concerned about sending your feed out over email and want only your galleries changed from thumbnails to large images, select Simplify Feed.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Alternate Feed', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'By default, the Send Images to RSS plugin modifies every feed from your site. If you want to leave your main feed untouched and set up a totally separate feed for emails only, select this option.', 'send-images-rss' ) . '</p>';
		$help .= '<p>' . __( 'If you use custom post types with their own feeds, the alternate feed method will work even with them.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Featured Image', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Some themes and/or plugins add the featured image to the front end of your site, but not to the feed. If you are using a full text feed and want the featured image to be added to it, use this setting. I definitely recommend double checking your feed after enabling this, in case your theme or another plugin already adds the featured image to the feed, because you may end up with duplicate images.', 'send-images-rss' ) . '</p>';
		$help .= '<p>' . __( 'If you are using the Alternate Feed setting, the featured image will be added to both feeds, but the full size version will be used on your unprocessed feed.', 'send-images-rss' ) . '</p>';
		if ( class_exists( 'Display_Featured_Image_Genesis' ) ) {
			$help .= '<p class="description">' . sprintf( __( 'As a <a href="%s">Display Featured Image for Genesis</a> user, you already have the option to add featured images to your feed using that plugin. If you have both plugins set to add the featured image to your full text feed, this plugin will step aside and not output the featured image until you have deactivated that setting in the other. This plugin gives you more control over the featured image output in the feed.', 'send-images-rss' ), esc_url( admin_url( 'themes.php?page=displayfeaturedimagegenesis' ) ) ) . '</p>';
		}
		$help .= '<p>' . __( 'Note: the plugin will attempt to see if the image is already in your post content. If it is, the featured image will not be added to the feed as it would be considered a duplication.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Change Small Images', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'By default, the plugin always attempts to use the email sized image in the feed, even if a smaller version of the image is added to the post. If you want the plugin to always use the size in the content (when adding small/thumbnail/medium size images), even if a larger size exists, disable this setting.', 'send-images-rss' ) . '</p>';

		return $help;
	}

	/**
	 * Help text for the summary/excerpt settings.
	 * @return string
	 * @since 3.1.2
	 */
	protected function summary() {

		$help  = '<h3>' . __( 'Excerpt Length', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Set the target number of words you want your excerpt to generally have. The plugin will count that many words, and then add on as many as are required to ensure your summary ends in a complete sentence.', 'send-images-rss' ) . '</p>';

		$help .= '<h3>' . __( 'Read More Text', 'send-images-rss' ) . '</h3>';
		$help .= '<p>' . __( 'Enter the text you want your "read more" link in your feed to contain. You can use placeholders for the post title and blog name.', 'send-images-rss' ) . '</p>';
		$help .= '<p class="description">' . __( 'Hint: "Read More" is probably inadequate for your link\'s anchor text.', 'send-images-rss' ) . '</p>';

		return $help;
	}

	/**
	 * Set notices to display for settings incompatibilities/updates.
	 * @return admin notice
	 *
	 * @since 3.0.0
	 */
	public function do_admin_notice() {
		$screen = get_current_screen();
		if ( ! in_array( $screen->id, array( 'settings_page_sendimagesrss', 'options-reading' ),  true ) ) {
			return;
		}
		$setting = sendimagesrss_get_setting();

		if ( ! $setting['simplify_feed'] || ! $setting['alternate_feed'] ) {
			return;
		}
		$rss_option = get_option( 'rss_use_excerpt' );
		if ( '1' === $rss_option ) {
			return;
		}

		$class   = 'error';
		$message = __( 'Warning! You have the Simplify Feed option checked! Your Alternate Feed setting will be ignored.', 'send-images-rss' );
		printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}
}
