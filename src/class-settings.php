<?php
/**
 * Class for adding customizer settings.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Include helpers class.
 */
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
	 * Settings constructor.
	 */
	public function __construct() {
		/**
		 * Set helpers.
		 */
		$this->helpers = new Helpers();

		/**
		 * Register customizer control and setting for the theme update URL.
		 */
		add_action( 'customize_register', array( $this, 'customize_register' ), 12 );
	}

	/**
	 * Create customizer setting, control, and section.
	 *
	 * @param object $wp_customize WP_Customze object.
	 */
	public function customize_register( $wp_customize ) {
		/**
		 * Add section.
		 */
		$wp_customize->add_section( 'lazy_load_responsive_images_options', array(
			'title' => __( 'Lazy loading options', 'lazy-loading-responsive-images' ),
		) );

		/**
		 * Add setting for URL.
		 */
		$wp_customize->add_setting( 'lazy_load_responsive_images_disabled_classes', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => array( $this->helpers, 'sanitize_class_name_list' ),
		) );

		/**
		 * Add control for update URL.
		 */
		$wp_customize->add_control( 'lazy_load_responsive_images_disabled_classes', array(
			'priority' => 1,
			'type'     => 'text',
			'section'  => 'lazy_load_responsive_images_options',
			'label'    => __( 'Exclude images with the following class names (separate multiple class names with comma).', 'lazy-loading-responsive-images' ),
		) );
	}
}
