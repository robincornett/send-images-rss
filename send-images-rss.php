<?php

/*
 * Plugin Name:       RSS Full Size Image Swap
 * Description:       This plugin makes your RSS emails look more like your website by converting overly large images and galleries to an email friendly format. Built with MailChimp in mind.
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           2.3.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Send_Images_RSS {

	public function __construct() {

		// add an email/feed specific image size.
		$mailchimp_width = esc_attr( get_option( 'mailchimp_image_size' ) );
		add_image_size( 'mailchimp', $mailchimp_width );

		// Gary: initally these both hooked to init, but do_feeds could be later--just has to be before pre_get_posts, right? wp_loaded seems to work.
		add_action( 'init', array( $this, 'require_files' ) );
		add_action( 'wp_loaded', array( $this, 'do_feeds' ) );
	}

	public function require_files() {
		require plugin_dir_path( __FILE__ ) . 'includes/class-feed-converter.php';
		require plugin_dir_path( __FILE__ ) . 'includes/class-alternate-feed.php';
		require plugin_dir_path( __FILE__ ) . 'includes/class-strip-gallery.php';
		require plugin_dir_path( __FILE__ ) . 'includes/admin.php';
	}

	public function do_feeds() {

		new Strip_Gallery(); // always convert galleries to larger images.

		$alt_feed = esc_attr( get_option( 'mailchimp_alternate_feed' ) );
		if ( $alt_feed === '1' ) { // if user wants the main feed images not downsized for email
			new Alternate_Feed();
		}
		else { // all feeds will be converted
			new Feed_Converter();
		}
	}

}

new Send_Images_RSS();
