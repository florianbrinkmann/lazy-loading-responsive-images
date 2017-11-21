<?php
/**
 * Class for adding customizer settings.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

// Include helpers class.
require_once 'class-helpers.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

/**
 * Class Settings
 *
 * Adds options to the customizer.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Settings {
	/**
	 * Helpers object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\Helpers
	 */
	private $helpers;

	/**
	 * Classes which should not be lazy loaded.
	 *
	 * @var array
	 */
	public $disabled_classes;

	/**
	 * Value of settings for enabling lazy loading for iFrames.
	 *
	 * @var string
	 */
	public $enable_for_iframes;

	/**
	 * Value of setting for loading the unveilhooks plugin.
	 *
	 * @var string
	 */
	public $load_unveilhooks_plugin;

	/**
	 * Value of settings for enabling lazy loading for videos.
	 *
	 * @var string
	 */
	public $enable_for_videos;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		// Set helpers.
		$this->helpers = new Helpers();

		// Fill properties with setting values.
		$this->disabled_classes        = explode( ',', get_option( 'lazy_load_responsive_images_disabled_classes' ) );
		$this->enable_for_iframes      = get_option( 'lazy_load_responsive_images_enable_for_iframes', '0' );
		$this->load_unveilhooks_plugin = get_option( 'lazy_load_responsive_images_unveilhooks_plugin', '0' );
		$this->enable_for_videos       = get_option( 'lazy_load_responsive_images_enable_for_videos', '0' );

		// Register customizer control and setting for the theme update URL.
		add_action( 'customize_register', array( $this, 'customize_register' ), 12 );
	}

	/**
	 * Create customizer setting, control, and section.
	 *
	 * @param object $wp_customize WP_Customze object.
	 */
	public function customize_register( $wp_customize ) {
		// Add section.
		$wp_customize->add_section( 'lazy_load_responsive_images_options', array(
			'title' => __( 'Lazy loading options', 'lazy-loading-responsive-images' ),
		) );

		// Add setting for classes to disable lazy loading for.
		$wp_customize->add_setting( 'lazy_load_responsive_images_disabled_classes', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => array( $this->helpers, 'sanitize_class_name_list' ),
		) );

		// Add control for classes to disable lazy loading for.
		$wp_customize->add_control( 'lazy_load_responsive_images_disabled_classes', array(
			'type'    => 'text',
			'section' => 'lazy_load_responsive_images_options',
			'label'   => __( 'Exclude elements with the following class names (separate multiple class names with comma).',
				'lazy-loading-responsive-images' ),
		) );

		// Add setting to enable lazy loading for iframes.
		$wp_customize->add_setting( 'lazy_load_responsive_images_enable_for_iframes', array(
			'type'              => 'option',
			'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
		) );

		// Add control to enable lazy loading for iframes.
		$wp_customize->add_control( 'lazy_load_responsive_images_enable_for_iframes', array(
			'type'    => 'checkbox',
			'section' => 'lazy_load_responsive_images_options',
			'label'   => __( 'Enable lazy loading for iframes.',
				'lazy-loading-responsive-images' ),
		) );

		// Add setting to load lazysizes unveilhooks plugin.
		$wp_customize->add_setting( 'lazy_load_responsive_images_unveilhooks_plugin', array(
			'type'              => 'option',
			'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
		) );

		// Add control to load lazysizes unveilhooks plugin.
		$wp_customize->add_control( 'lazy_load_responsive_images_unveilhooks_plugin', array(
			'type'    => 'checkbox',
			'section' => 'lazy_load_responsive_images_options',
			'label'   => __( 'Load the lazyload unveilhooks extension to enable lazy loading of background images, scripts, styles, and videos. To use it with background images, scripts and styles, you will need to manually modify the markup: https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks',
				'lazy-loading-responsive-images' ),
		) );

		// Add setting to enable lazy loading for videos.
		$wp_customize->add_setting( 'lazy_load_responsive_images_enable_for_videos', array(
			'type'              => 'option',
			'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
		) );

		// Add control to enable lazy loading for videos.
		$wp_customize->add_control( 'lazy_load_responsive_images_enable_for_videos', array(
			'type'            => 'checkbox',
			'section'         => 'lazy_load_responsive_images_options',
			'label'           => __( 'Enable lazy loading for videos.',
				'lazy-loading-responsive-images' ),
			'active_callback' => array( $this->helpers, 'display_video_option' ),
		) );
	}
}
