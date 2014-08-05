<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014 Robin Cornett
 * @license   GPL-2.0+
 */

/**
 * Main plugin class.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS {

	public $gallery_stripper;
	public $feed_fixer;
	public $settings;

	/**
	 * Inject dependencies.
	 *
	 * @since x.y.z
	 */
	public function __construct( $gallery_stripper, $feed_fixer, $settings ) {
		$this->gallery_stripper = $gallery_stripper;
		$this->feed_fixer       = $feed_fixer;
		$this->settings         = $settings;
	}

	/**
	 * Set up hooks.
	 *
	 * @since x.y.z
	 */
	public function run() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
		add_action( 'template_redirect', array( $this, 'fix_feed' ) );
		add_filter( 'the_content', array( $this->gallery_stripper, 'strip' ), 19 );
	}

	/**
	 * Add a feed-specific image size, and custom feed.
	 *
	 * @since x.y.z
	 */
	public function init() {
		$simplify = get_option( 'sendimagesrss_simplify_feed' );
		$image_width = esc_attr( get_option( 'sendimagesrss_image_size' ) );

		if ( ! $simplify ) {
			add_image_size( 'mailchimp', $image_width );
		}

		// Add a new feed, but tell WP to treat it as a standard RSS2 feed
		// We do this so the output is the same by default, but we can use
		// the different querystring value to conditionally apply the fixes.
		$alt_feed = get_option( 'sendimagesrss_alternate_feed' );
		if ( $alt_feed ) {
			add_feed( 'email', 'do_feed_rss2' );
		}
	}

	/**
	 * Choose which feeds to fix.
	 *
	 * @since x.y.z
	 *
	 * @return null Return early if not a feed.
	 */
	public function fix_feed() {
		if ( ! is_feed() ) {
			return;
		}

		$simplify = get_option( 'sendimagesrss_simplify_feed' );
		$alt_feed = get_option( 'mailchimp_alternate_feed' );
		if ( ! $simplify && ( ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ) ) {
			add_filter( 'the_content', array( $this->feed_fixer, 'fix' ), 20 );
		}
	}
}
