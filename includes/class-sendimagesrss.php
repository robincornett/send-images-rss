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
	 * @since 2.4.0
	 */
	public function __construct( $gallery_stripper, $feed_fixer, $settings ) {
		$this->gallery_stripper = $gallery_stripper;
		$this->feed_fixer       = $feed_fixer;
		$this->settings         = $settings;
	}

	/**
	 * Set up hooks.
	 *
	 * @since 2.4.0
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
		add_action( 'load-options-media.php', array( $this->settings, 'help' ) );
		add_action( 'admin_notices', array( $this->settings, 'error_message' ) );
		add_action( 'template_redirect', array( $this, 'fix_feed' ) );
	}

	/**
	 * Set up text domain for translations
	 *
	 * @since 2.4.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'send-images-rss', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Add a feed-specific image size, and custom feed.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		$simplify    = get_option( 'sendimagesrss_simplify_feed' );
		$image_width = esc_attr( get_option( 'sendimagesrss_image_size', 560 ) );

		if ( ! $simplify ) {
			add_image_size( 'mailchimp', absint( $image_width ), floor( $image_width * 2 ) );
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
	 * @since 2.3.0
	 *
	 * @return null Return early if not a feed, or if the feed is set to send excerpts.
	 */
	public function fix_feed() {

		$rss_option = get_option( 'rss_use_excerpt' );
		if ( ! is_feed() || '1' === $rss_option ) {
			return;
		}

		//* because Photon refuses to use our new image size.
		add_filter( 'jetpack_photon_skip_image', '__return_true' );

		add_filter( 'the_content', array( $this->gallery_stripper, 'strip' ), 19 );

		//* have to remove the photon filter twice as it's really aggressive
		$photon_removed = '';
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$photon_removed = remove_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ) );
		}

		$simplify = get_option( 'sendimagesrss_simplify_feed' );
		$alt_feed = get_option( 'sendimagesrss_alternate_feed' );

		if ( ! $simplify && ( ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ) ) {
			add_filter( 'the_content', array( $this->feed_fixer, 'fix' ), 20 );
		}

		//* re-enable photon, although since we're in the feed, not sure it's relevant
		if ( $photon_removed ) {
			add_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ), 10, 3 );
		}
	}
}
