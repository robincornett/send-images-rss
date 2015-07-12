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
 * Main plugin class.
 *
 * @package SendImagesRSS
 */
class SendImagesRSS {

	public $gallery_stripper;
	public $excerpt_fixer;
	public $feed_fixer;
	public $settings;
	protected $rss_setting;

	/**
	 * Inject dependencies.
	 *
	 * @since 2.4.0
	 */
	public function __construct( $gallery_stripper, $excerpt_fixer, $feed_fixer, $settings ) {
		$this->gallery_stripper = $gallery_stripper;
		$this->excerpt_fixer    = $excerpt_fixer;
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
		add_action( 'admin_menu', array( $this->settings, 'do_submenu_page' ) );
		add_action( 'template_redirect', array( $this, 'fix_feed' ) );
		add_filter( 'plugin_action_links_' . SENDIMAGESRSS_BASENAME, array( $this, 'add_settings_link' ) );
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

		$simplify    = $this->settings->rss_setting['simplify_feed'];
		$image_width = $this->settings->rss_setting['image_size'];

		if ( ! $simplify ) {
			add_image_size( 'mailchimp', (int) $image_width, (int) ( $image_width * 2 ) );
		}

		// Add a new feed, but tell WP to treat it as a standard RSS2 feed
		// We do this so the output is the same by default, but we can use
		// the different querystring value to conditionally apply the fixes.
		$alt_feed   = $this->settings->rss_setting['alternate_feed'];
		$rss_option = get_option( 'rss_use_excerpt' );
		if ( $alt_feed && '0' === $rss_option ) {
			add_feed( 'email', 'do_feed_rss2' );
		}
	}

	/**
	 * Choose which feeds to fix.
	 *
	 * @since 2.3.0
	 *
	 * @return null Return early if not a feed.
	 */
	public function fix_feed() {

		if ( ! is_feed() ) {
			return;
		}

		$rss_option = get_option( 'rss_use_excerpt' );
		/**
		 * add a filter to work on the excerpt even if the feed is set to full text
		 * @var boolean
		 *
		 * @since 2.7.0
		 */
		$damn_the_consequences = apply_filters( 'send_images_rss_process_excerpt_anyway', false );
		$damn_the_consequences = true === $damn_the_consequences ? $damn_the_consequences : false;

		if ( '1' === $rss_option || true === $damn_the_consequences ) {
			$this->fix_excerpts();
			if ( '1' === $rss_option ) {
				return;
			}
		}

		$this->fix_full_text();

	}

	public function fix_excerpts() {

		remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
		add_filter( 'the_excerpt_rss', array( $this->excerpt_fixer, 'do_excerpt' ), 50 );

	}

	public function fix_full_text() {

		// because Photon refuses to use our new image size.
		add_filter( 'jetpack_photon_skip_image', '__return_true' );

		add_filter( 'the_content', array( $this->gallery_stripper, 'strip' ), 19 );

		// have to remove the photon filter twice as it's really aggressive
		$photon_removed = '';
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$photon_removed = remove_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ) );
		}

		$simplify = $this->settings->rss_setting['simplify_feed'];
		$alt_feed = $this->settings->rss_setting['alternate_feed'];

		if ( ! $simplify && ( ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ) ) {
			add_filter( 'the_content', array( $this->feed_fixer, 'fix' ), 20 );
		}

		// re-enable photon, although since we're in the feed, not sure it's relevant
		if ( $photon_removed ) {
			add_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ), 10, 3 );
		}

	}

	/**
	 * Add link to plugin settings page in plugin table
	 * @param $links link to settings page
	 *
	 * @since 2.7.0
	 */
	public function add_settings_link( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=sendimagesrss' ) ), esc_attr__( 'Settings', 'send-images-rss' ) );
		return $links;
	}

}
