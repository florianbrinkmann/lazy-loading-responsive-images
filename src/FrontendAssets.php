<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages;


use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;

class FrontendAssets {
	use ConfigTrait;

	/**
	 * ProcessingNeeded object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\ProcessingNeededCheck
	 */
	private $processing_needed_check;

	public function __construct( ConfigInterface $config, ProcessingNeededCheck $processing_needed_check ) {
		$this->processConfig( $config );
		$this->processing_needed_check = $processing_needed_check;
	}

	/**
	 * Runs filters and actions.
	 *
	 * @return void
	 */
	public function init(): void {
		// Enqueues scripts and styles.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_script',
		), 20 );

		// Adds inline style.
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_script(): void {
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
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', UNVEILHOOKS_PLUGIN ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', ENABLE_FOR_AUDIOS ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', ENABLE_FOR_VIDEOS ) || true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', ENABLE_FOR_BACKGROUND_IMAGES ) ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-unveilhooks', plugins_url( '/lazy-loading-responsive-images/js/ls.unveilhooks.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.unveilhooks.min.js' ), true );
		}

		// Check if native loading plugin should be loaded.
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'core_setting_values', NATIVE_LAZY_LOAD ) ) {
			wp_enqueue_script( 'lazysizes-native-loading', plugins_url( '/lazy-loading-responsive-images/js/ls.native-loading.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.native-loading.min.js' ), true );
		}

		// Include custom lazysizes config if not empty.
		if ( '' !== $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZYSIZES_CONFIG ) ) {
			wp_add_inline_script( 'lazysizes', $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LAZYSIZES_CONFIG ), 'before' );
		}
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 *
	 * @return void
	 */
	public function add_inline_style(): void {
		if ( $this->processing_needed_check->run() === false ) {
			return;
		}

		// Create loading spinner style if needed.
		$spinner_styles = '';
		$spinner_color  = $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LOADING_SPINNER_COLOR );
		$spinner_markup = sprintf(
			'<svg width="44" height="44" xmlns="http://www.w3.org/2000/svg" stroke="%s"><g fill="none" fill-rule="evenodd" stroke-width="2"><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="0s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="0s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="-0.9s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="-0.9s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle></g></svg>',
			$spinner_color
		);
		if ( true === $this->getConfigKey( PLUGIN_PREFIX, 'wp_setting_values', LOADING_SPINNER ) ) {
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
}