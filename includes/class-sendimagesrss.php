<?php
/**
 * Send Images to RSS
 *
 * @package   SendImagesRSS
 * @author    Robin Cornett <hello@robincornett.com>
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @link      https://github.com/robincornett/send-images-rss
 * @copyright 2014-2016 Robin Cornett
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
		add_filter( 'sendimagesrss_get_setting', array( $this->settings, 'get_rss_setting' ) );
		add_filter( 'sendimagesrss_can_process', array( $this, 'can_process' ) );
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

		$this->rss_setting  = sendimagesrss_get_setting();
		$simplify    = $this->rss_setting['simplify_feed'];
		$image_width = $this->rss_setting['image_size'];

		if ( ! $simplify ) {
			add_image_size( 'mailchimp', (int) $image_width, (int) ( $image_width * 2 ) );
		}

		// Add a new feed, but tell WP to treat it as a standard RSS2 feed
		// We do this so the output is the same by default, but we can use
		// the different querystring value to conditionally apply the fixes.
		$alt_feed   = $this->rss_setting['alternate_feed'];
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

		// disable srcset on images in feeds
		add_filter( 'wp_calculate_image_srcset', '__return_false' );

		// because Photon refuses to use our new image size. Or behave.
		add_filter( 'jetpack_photon_skip_image', '__return_true' );

		$rss_option = get_option( 'rss_use_excerpt' );

		// have to remove the photon filter twice as it's really aggressive
		$photon_removed = '';
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$photon_removed = remove_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ) );
		}

		$damn_consequences = $this->damn_consequences();
		if ( '1' === $rss_option || $damn_consequences ) {
			$this->fix_excerpts();
		}

		if ( '0' === $rss_option ) {
			$this->fix_full_text();
		}

		// re-enable photon, although since we're in the feed, not sure it's relevant
		if ( $photon_removed ) {
			add_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ), 10, 3 );
		}

	}

	/**
	 * Fix feeds for summaries feeds
	 *
	 * @since 3.0.0
	 */
	protected function fix_excerpts() {

		// remove the Yoast RSS footer
		add_filter( 'wpseo_include_rss_footer', '__return_false' );

		// remove the weird Woo filter (Canvas theme)
		remove_filter( 'get_the_excerpt', 'woo_remove_dropcap_from_excerpts' );

		// remove WP default trim since we're doing that ourselves
		remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
		add_filter( 'the_excerpt_rss', array( $this->excerpt_fixer, 'do_excerpt' ), 50 );

	}

	/**
	 * Fix feeds for full text feeds
	 *
	 * @since 3.0.0
	 */
	protected function fix_full_text() {

		add_filter( 'the_content', array( $this->gallery_stripper, 'strip' ), 19 );
		$featured_image = isset( $this->rss_setting['featured_image'] ) ? $this->rss_setting['featured_image'] : 0;
		if ( $featured_image ) {
			add_filter( 'the_content_feed', array( $this, 'add_featured_image' ), 100 );
		}

		$simplify    = $this->rss_setting['simplify_feed'];
		$can_process = $this->can_process();
		if ( ! $simplify && $can_process ) {
			add_filter( 'the_content', array( $this->feed_fixer, 'fix' ), 20 );
		}
	}

	/**
	 * @param $content
	 * @uses set_featured_image() from excerpt fixer class.
	 * @return string
	 * @since 3.1.0
	 */
	public function add_featured_image( $content ) {
		if ( class_exists( 'Display_Featured_Image_Genesis' ) ) {
			$displaysetting = get_option( 'displayfeaturedimagegenesis' );
			if ( isset( $displaysetting['feed_image'] ) && $displaysetting['feed_image'] ) {
				return $content;
			}
		}
		$can_process            = $this->can_process();
		$unprocessed_image_size = apply_filters( 'send_images_rss_unprocessed_featured_image_size', 'large' );
		$thumbnail_size         = $can_process && isset( $this->rss_setting['thumbnail_size'] ) ? $this->rss_setting['thumbnail_size'] : $unprocessed_image_size;
		$image                  = $this->excerpt_fixer->set_featured_image( $thumbnail_size, $content );
		return wp_kses_post( $image . $content );
	}

	/**
	 * Add link to plugin settings page in plugin table
	 * @param $links array link to settings page
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=sendimagesrss' ) ), esc_attr__( 'Settings', 'send-images-rss' ) );
		return $links;
	}

	/**
	 * add a filter to work on the excerpt even if the feed is set to full text
	 * @return bool
	 *
	 * @since 3.0.0
	 */
	protected function damn_consequences( $damn_consequences = false ) {
		$damn_consequences = apply_filters( 'send_images_rss_process_excerpt_anyway', false );
		return true === $damn_consequences ? true : false;
	}

	/**
	 * Function to check whether the feed/image should be processed or not
	 * @param bool $can_process
	 *
	 * @return bool
	 * @since 3.1.0
	 */
	public function can_process( $can_process = false ) {
		$alt_feed = $this->rss_setting['alternate_feed'];
		return $alt_feed && is_feed( 'email' ) || ! $alt_feed ? true : false;
	}
}
