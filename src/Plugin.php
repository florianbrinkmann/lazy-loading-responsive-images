<?php

declare( strict_types=1 );

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
	public function __construct( ConfigInterface $config, string $basename ) {
		$this->processConfig( $config );
		$this->basename = $basename;
	}

	/**
	 * Runs the filters and actions.
	 *
	 * @return void
	 */
	public function init(): void {
		// Init settings.
		try {
			$lazy_loader_setting_controls = $this->config->getSubConfig( PLUGIN_PREFIX, 'setting_controls' );
		} catch ( KeyNotFoundException $e ) {
			wp_die( 'The passed config key for Lazy Loader setting controls does not exist.' );
		}

		// Init settings.
		( new Settings( $lazy_loader_setting_controls, $this->basename ) )->init();

		// Display checkbox to granular disable plugin.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', GRANULAR_DISABLE_OPTION ) ) {
			( new GranularDisableOption( $this->config ) )->init();
		}

		// Set helpers.
		$this->processing_needed_check = new ProcessingNeededCheck();

		// Disable core lazy loading.
		add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		// Load frontend assets.
		( new FrontendAssets( $this->config, $this->processing_needed_check ) )->init();

		// Init content processing.
		add_action( 'init', array( $this, 'init_content_processing' ) );

		// Load translations.
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );
	}

	/**
	 * Run actions and filters to start content processing.
	 *
	 * @return void
	 */
	public function init_content_processing(): void {
		// Check if the complete markup should be processed.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', PROCESS_COMPLETE_MARKUP ) ) {
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
		$additional_filters = explode( "\n", $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', ADDITIONAL_FILTERS ) );

		if ( is_array( $additional_filters ) && ! empty( $additional_filters ) ) {
			foreach ( $additional_filters as $filter ) {
				add_filter( $filter, array( $this, 'filter_markup' ) );
			}
		}
	}

	/**
	 * Run output buffering to process the complete markup.
	 *
	 * @return void
	 */
	public function process_complete_markup(): void {
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
	public function filter_markup( string $content = '' ): string {
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
		$config_array[ATTRS_TO_STRIP_FROM_FALLBACK_ELEM] = $attrs_to_strip;

		$lazy_loader_core_config = ConfigFactory::merge( $config_array );

		// Create LazyLoaderCore instance and run it.
		$lazy_loader_core = new LazyLoaderCore( $lazy_loader_core_config );

		return $lazy_loader_core->run( $content );
	}

	/**
	 * Filter allowed html for posts.
	 *
	 * @param array $allowedposttags Allowed post tags.
	 * @param string $context        Context.
	 *
	 * @return array
	 */
	public function wp_kses_allowed_html( array $allowedposttags, string $context ): array {
		if ( 'post' !== $context ) {
			return $allowedposttags;
		}

		$allowedposttags['noscript'] = [];

		return $allowedposttags;
	}

	/**
	 * Loads the plugin translation.
	 *
	 * @return void
	 */
	public function load_translation(): void {
		load_plugin_textdomain( 'lazy-loading-responsive-images' );
	}
}
