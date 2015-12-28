<?php
/**
 * Send Images to RSS
 *
 * @package           SendImagesRSS
 * @author            Robin Cornett
 * @author            Gary Jones <gary@garyjones.co.uk>
 * @link              https://github.com/robincornett/send-images-rss
 * @copyright         2015 Robin Cornett
 * @license           GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Send Images to RSS
 * Plugin URI:        https://wordpress.org/plugins/send-images-rss
 * Description:       Makes your RSS emails look more like your website by converting overly large images and galleries to an email friendly format. Built with MailChimp in mind.
 * Version:           3.0.1
 * Author:            Robin Cornett
 * Author URI:        http://robincornett.com
 * Text Domain:       send-images-rss
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/robincornett/send-images-rss
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'SENDIMAGESRSS_BASENAME' ) ) {
	define( 'SENDIMAGESRSS_BASENAME', plugin_basename( __FILE__ ) );
}

// Include classes
function send_images_rss_require() {
	$files = array(
		'class-sendimagesrss',
		'class-sendimagesrss-excerpt-fixer',
		'class-sendimagesrss-feed-fixer',
		'class-sendimagesrss-strip-gallery',
		'class-sendimagesrss-settings',
	);

	foreach ( $files as $file ) {
		require plugin_dir_path( __FILE__ ) . 'includes/' . $file . '.php';
	}
}
send_images_rss_require();

// Instantiate dependent classes
$sendimagesrss_strip_gallery = new SendImagesRSS_Strip_Gallery;
$sendimagesrss_excerpt_fixer = new SendImagesRSS_Excerpt_Fixer;
$sendimagesrss_feed_fixer    = new SendImagesRSS_Feed_Fixer;
$sendimagesrss_settings      = new SendImagesRSS_Settings;

// Instantiate main class and pass in dependencies
$sendimagesrss = new SendImagesRSS(
	$sendimagesrss_strip_gallery,
	$sendimagesrss_excerpt_fixer,
	$sendimagesrss_feed_fixer,
	$sendimagesrss_settings
);

// Run the plugin
$sendimagesrss->run();

/**
 * Helper function to get the RSS setting.
 * @since x.y.z
 */
function sendimagesrss_get_setting() {
	return apply_filters( 'sendimagesrss_get_setting', false );
}
