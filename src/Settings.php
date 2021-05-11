<?php
/**
 * Class for adding customizer settings.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;

/**
 * Class Settings
 *
 * Adds options to the customizer.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Settings {
	use ConfigTrait;

	/**
	 * Helpers object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\ProcessingNeededCheck
	 */
	protected $helpers;

	/**
	 * Array of options data.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Array of object types that should show the checkbox to disable lazy loading.
	 *
	 * @var array
	 */
	protected $disable_option_object_types = array();

	/**
	 * Basename of the plugin.
	 *
	 * @var string
	 */
	protected $basename;

	/**
	 * Settings constructor.
	 */
	public function __construct( ConfigInterface $config, $basename ) {
		$this->processConfig( $config );
		$this->basename = $basename;
	}

	public function init() {
		// Register settings on media options page.
		add_action( 'admin_init', array( $this, 'settings_init' ), 12 );

		// Include color picker JS.
		add_action( 'admin_enqueue_scripts', array(
			$this,
			'add_color_picker',
		) );

		// Add link to settings in the plugin list.
		add_filter( 'plugin_action_links', array(
			$this,
			'plugin_action_links',
		), 10, 2 );

		if ( true === $this->getConfigKey( LAZY_LOADER_GRANULAR_DISABLE_OPTION ) ) {
			( new GranularDisableOption( $this->config ) )->init();
		}
	}

	/**
	 * Init settings on media options page.
	 */
	public function settings_init() {
		// Add section.
		add_settings_section(
			"lazy-load-responsive-images-section",
			sprintf(
				'<span id="lazy-loader-options">%s</span>',
				__( 'Lazy Loader options', 'lazy-loading-responsive-images' )
			),
			function() {},
			'media'
		);

		// Loop the options.
		foreach ( $this->getConfigArray() as $option ) {
			// Register setting.
			register_setting( 'media', $option['wp_option_name'], array(
				'sanitize_callback' => $option['sanitize_callback'],
			) );

			// Create field.
			add_settings_field(
				$option['wp_option_name'],
				$option['label'],
				$option['field_callback'],
				'media',
				'lazy-load-responsive-images-section',
				array(
					'label_for'   => $option['wp_option_name'],
					'value'       => $option['value'],
					'description' => ( isset( $option['description'] ) ? $option['description'] : '' ),
				)
			);
		} // End foreach().
	}

	/**
	 * Add color picker to media settings page and init it.
	 *
	 * @param string $hook_suffix PHP file of the admin screen.
	 */
	public function add_color_picker( $hook_suffix ) {
		// Check if we are not on the media backend screen.
		if ( 'options-media.php' !== $hook_suffix ) {
			return;
		} // End if().

		// Add color picker script and style and init it.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', "jQuery(document).ready(function($){
    $('.lazy-load-responsive-images-color-field').wpColorPicker();
});" );
	}

	/**
	 * Add settings link to the plugin entry in the plugin list.
	 *
	 * @param array  $links Array of action links.
	 * @param string $file  Basename of the plugin.
	 *
	 * @return array The action links array.
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file === $this->basename ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				'options-media.php#lazy-loader-options',
				__( 'Settings', 'lazy-loading-responsive-images' )
			);
		}

		return $links;
	}
}
