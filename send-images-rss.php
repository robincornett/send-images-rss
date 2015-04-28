<?php
/**
 * Send Images to RSS
 *
 * @package           SendImagesRSS
 * @author            Robin Cornett
 * @author            Gary Jones <gary@garyjones.co.uk>
 * @link              https://github.com/robincornett/send-images-rss
 * @copyright         2014 Robin Cornett
 * @license           GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Send Images to RSS
 * Plugin URI:        https://github.com/robincornett/send-images-rss
 * Description:       Makes your RSS emails look more like your website by converting overly large images and galleries to an email friendly format. Built with MailChimp in mind.
 * Version:           2.6.0
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

// Include classes
require plugin_dir_path( __FILE__ ) . 'includes/class-sendimagesrss.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-sendimagesrss-feed-fixer.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-sendimagesrss-strip-gallery.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-sendimagesrss-settings.php';

// Instantiate dependent classes
$sendimagesrss_strip_gallery = new SendImagesRSS_Strip_Gallery;
$sendimagesrss_feed_fixer = new SendImagesRSS_Feed_Fixer;
$sendimagesrss_settings = new SendImagesRSS_Settings;

// Instantiate main class and pass in dependencies
$sendimagesrss = new SendImagesRSS(
	$sendimagesrss_strip_gallery,
	$sendimagesrss_feed_fixer,
	$sendimagesrss_settings
);

// Run the plugin
$sendimagesrss->run();
