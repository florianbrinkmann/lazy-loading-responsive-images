<?php
/**
 * Main plugin code.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use BrightNucleus\Config\ConfigFactory;
use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;
use BrightNucleus\Config\Exception\KeyNotFoundException;

/**
 * Class Plugin
 *
 * Class for adding lazy loading to responsive images.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Plugin {
	use ConfigTrait;

	/**
	 * ProcessingNeeded object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\ProcessingNeededCheck
	 */
	private $processing_needed_check;

	/**
	 * Basename of the plugin.
	 *
	 * @var string
	 */
	protected $basename;
	
	/**
	 * Plugin constructor.
	 */
	public function __construct( ConfigInterface $config, $basename ) {
		$this->processConfig( $config );
		$this->basename = $basename;
	}

	/**
	 * Runs the filters and actions.
	 */
	public function init() {
		// Init settings.
		try {
			$lazy_loader_setting_controls = $this->config->getSubConfig( PLUGIN_PREFIX, 'setting_controls' );
		} catch ( KeyNotFoundException $e ) {
			wp_die( 'The passed config key for Lazy Loader setting controls does not exist.' );
		}

		( new Settings( $lazy_loader_setting_controls, $this->basename ) )->init();

		// Set helpers.
		$this->processing_needed_check = new ProcessingNeededCheck();

		// Disable core lazy loading.
		add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		add_action( 'init', array( $this, 'init_content_processing' ) );
		
		// Enqueues scripts and styles.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_script',
		), 20 );

		// Adds inline style.
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		// Load the language files.
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );
	}

	/**
	 * Run actions and filters to start content processing.
	 */
	public function init_content_processing() {
		// Check if the complete markup should be processed.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_PROCESS_COMPLETE_MARKUP ) ) {
			add_action( 'template_redirect', array( $this, 'process_complete_markup' ) );
			return;
		}

		// Filter markup of the_content() calls to modify media markup for lazy loading.
		add_filter( 'the_content', array( $this, 'filter_markup' ), 10001 );

		// Filter allowed html for posts to allow <noscript> tag.
		add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );

		// Filter markup of Text widget to modify media markup for lazy loading.
		add_filter( 'widget_text', array( $this, 'filter_markup' ) );

		// Filter markup of gravatars to modify markup for lazy loading.
		add_filter( 'get_avatar', array( $this, 'filter_markup' ) );

		// Adds lazyload markup and noscript element to post thumbnail.
		add_filter( 'post_thumbnail_html', array(
			$this,
			'filter_markup',
		), 10001, 1 );

		// Run lazy loader on additional filters if set in the settings.
		$additional_filters = explode( "\n", $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_ADDITIONAL_FILTERS ) );

		if ( is_array( $additional_filters ) && ! empty( $additional_filters ) ) {
			foreach ( $additional_filters as $filter ) {
				add_filter( $filter, array( $this, 'filter_markup' ) );
			}
		}
	}

	/**
	 * Run output buffering to process the complete markup.
	 */
	public function process_complete_markup() {
		// If this is no content we should process, exit as early as possible.
		if ( $this->processing_needed_check->run() === false ) {
			return;
		}

		ob_start( array( $this, 'filter_markup' ) );
	}

	/**
	 * Modifies elements to automatically enable lazy loading.
	 *
	 * @param string $content HTML.
	 *
	 * @return string Modified HTML.
	 */
	public function filter_markup( $content = '' ) {
		// If this is no content we should process, exit as early as possible.
		if ( $this->processing_needed_check->run() === false ) {
			return $content;
		}

		// Check if we have no content.
		if ( empty( $content ) ) {
			return $content;
		}

		// Check if content contains caption shortcode.
		if ( has_shortcode( $content, 'caption' ) ) {
			return $content;
		}

		try {
			$lazy_loader_core_config = $this->config->getSubConfig( PLUGIN_PREFIX, 'core_setting_values' );
		} catch ( KeyNotFoundException $e ) {
			error_log( 'The passed config key for Lazy Loader core does not exist.' );
			return $content;
		}

		/**
		 * Array of HTML attributes that should be stripped from the fallback element in noscript.
		 *
		 * @param array Array of elements to strip from fallback.
		 */
		$attrs_to_strip = (array) apply_filters( 'lazy_loader_attrs_to_strip_from_fallback_elem', [] );

		$config_array = $lazy_loader_core_config->getArrayCopy();
		
		// Add filerable value to core config.
		$config_array[LAZY_LOADER_ATTRS_TO_STRIP_FROM_FALLBACK_ELEM] = $attrs_to_strip;

		$lazy_loader_core_config = ConfigFactory::merge( $config_array );

		$lazy_loader_core = new LazyLoaderCore( $lazy_loader_core_config );

		return $lazy_loader_core->run( $content );
	}

	/**
	 * Filter allowed html for posts.
	 *
	 * @param array  $allowedposttags Allowed post tags.
	 * @param string $context         Context.
	 *
	 * @return array
	 */
	public function wp_kses_allowed_html( $allowedposttags, $context ) {
		if ( 'post' !== $context ) {
			return $allowedposttags;
		}

		$allowedposttags['noscript'] = [];

		return $allowedposttags;
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_script() {
		if ( $this->processing_needed_check->run() === false ) {
			return;
		}

		// Check if something (like Avada) already included a lazysizes script. If that is the case, deregister it.
		$lazysizes = wp_script_is( 'lazysizes' );

		if ( $lazysizes ) {
			wp_deregister_script( 'lazysizes' );
		}

		// Enqueue lazysizes.
		wp_enqueue_script( 'lazysizes', plugins_url( '/lazy-loading-responsive-images/js/lazysizes.min.js' ), array(), filemtime( plugin_dir_path( __FILE__ ) . '../js/lazysizes.min.js' ), true );

		// Check if unveilhooks plugin should be loaded.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_UNVEILHOOKS_PLUGIN ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', LAZY_LOADER_ENABLE_FOR_AUDIOS ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', LAZY_LOADER_ENABLE_FOR_VIDEOS ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES ) ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-unveilhooks', plugins_url( '/lazy-loading-responsive-images/js/ls.unveilhooks.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.unveilhooks.min.js' ), true );
		}

		// Check if native loading plugin should be loaded.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', LAZY_LOADER_NATIVE_LAZY_LOAD ) ) {
			wp_enqueue_script( 'lazysizes-native-loading', plugins_url( '/lazy-loading-responsive-images/js/ls.native-loading.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.native-loading.min.js' ), true );
		}

		// Include custom lazysizes config if not empty.
		if ( '' !== $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_LAZYSIZES_CONFIG ) ) {
			wp_add_inline_script( 'lazysizes', $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_LAZYSIZES_CONFIG ), 'before' );
		}
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 */
	public function add_inline_style() {
		if ( $this->processing_needed_check->run() === false ) {
			return;
		}

		// Create loading spinner style if needed.
		$spinner_styles = '';
		$spinner_color  = $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_LOADING_SPINNER_COLOR );
		$spinner_markup = sprintf(
			'<svg width="44" height="44" xmlns="http://www.w3.org/2000/svg" stroke="%s"><g fill="none" fill-rule="evenodd" stroke-width="2"><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="0s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="0s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="-0.9s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="-0.9s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle></g></svg>',
			$spinner_color
		);
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZY_LOADER_LOADING_SPINNER ) ) {
			$spinner_styles = sprintf(
				'.lazyloading {
	color: transparent;
	opacity: 1;
	transition: opacity 300ms;
	transition: opacity var(--lazy-loader-animation-duration);
	background: url("data:image/svg+xml,%s") no-repeat;
	background-size: 2em 2em;
	background-position: center center;
}

.lazyloaded {
	animation-name: loaded;
	animation-duration: 300ms;
	animation-duration: var(--lazy-loader-animation-duration);
	transition: none;
}

@keyframes loaded {
	from {
		opacity: 0;
	}

	to {
		opacity: 1;
	}
}',
				rawurlencode( $spinner_markup )
			);
		}

		// Display the default styles.
		$default_styles = "<style>:root {
			--lazy-loader-animation-duration: 300ms;
		}
		  
		.lazyload {
	display: block;
}

.lazyload,
        .lazyloading {
			opacity: 0;
		}


		.lazyloaded {
			opacity: 1;
			transition: opacity 300ms;
			transition: opacity var(--lazy-loader-animation-duration);
		}$spinner_styles</style>";

		/**
		 * Filter for the default inline style element.
		 *
		 * @param string $default_styles The default styles (including <style> element).
		 */
		echo apply_filters( 'lazy_load_responsive_images_inline_styles', $default_styles );

		// Hide images if no JS.
		echo '<noscript><style>.lazyload { display: none; } .lazyload[class*="lazy-loader-background-element-"] { display: block; opacity: 1; }</style></noscript>';
	}

	/**
	 * Loads the plugin translation.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'lazy-loading-responsive-images' );
	}
}
