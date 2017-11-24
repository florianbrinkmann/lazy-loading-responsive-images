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
	 * Array of options data.
	 *
	 * @var array
	 */
	public $options;

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
	 * Value of settings for enabling lazy loading for audios.
	 *
	 * @var string
	 */
	public $enable_for_audios;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		// Set helpers.
		$this->helpers = new Helpers();

		$this->options = array(
			'lazy_load_responsive_images_disabled_classes'   => array(
				'type'              => 'text',
				'value'             => get_option( 'lazy_load_responsive_images_disabled_classes', '' ),
				'label'             => __( 'CSS classes to exclude', 'lazy-loading-responsive-images' ),
				'description'       => __( 'Enter one or more CSS classes to exclude them from lazy loading (separated by comma).',
					'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'text_field_cb' ),
				'sanitize_callback' => array( $this->helpers, 'sanitize_class_name_list' ),
			),
			'lazy_load_responsive_images_enable_for_iframes' => array(
				'type'              => 'checkbox',
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_iframes', '0' ),
				'label'             => __( 'Enable lazy loading for iframes', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
			),
			'lazy_load_responsive_images_unveilhooks_plugin' => array(
				'type'              => 'checkbox',
				'value'             => get_option( 'lazy_load_responsive_images_unveilhooks_plugin', '0' ),
				'label'             => __( 'Include lazyload unveilhooks extension' ),
				'description'       => __( 'The extension adds support for lazy loading of background images, scripts, styles, and videos. To use it with background images, scripts and styles, you will need to <a href="https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks">manually modify the markup</a>.',
					'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
			),
			'lazy_load_responsive_images_enable_for_videos'  => array(
				'type'              => 'checkbox',
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_videos', '0' ),
				'label'             => __( 'Enable lazy loading for videos',
					'lazy-loading-responsive-images' ),
				'description'       => __( 'This feature needs the unveilhooks plugin.',
					'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
			),
			'lazy_load_responsive_images_enable_for_audios'  => array(
				'type'              => 'checkbox',
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_audios', '0' ),
				'label'             => __( 'Enable lazy loading for audios',
					'lazy-loading-responsive-images' ),
				'description'       => __( 'This feature needs the unveilhooks plugin.',
					'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array( $this->helpers, 'sanitize_checkbox' ),
			),
		);

		// Fill properties with setting values.
		$this->disabled_classes        = explode( ',',
			$this->options['lazy_load_responsive_images_disabled_classes']['value'] );
		$this->enable_for_iframes      = $this->options['lazy_load_responsive_images_enable_for_iframes']['value'];
		$this->load_unveilhooks_plugin = $this->options['lazy_load_responsive_images_unveilhooks_plugin']['value'];
		$this->enable_for_videos       = $this->options['lazy_load_responsive_images_enable_for_videos']['value'];
		$this->enable_for_audios       = $this->options['lazy_load_responsive_images_enable_for_audios']['value'];

		// Register settings on media options page.
		add_action( 'admin_init', array( $this, 'settings_init' ), 12 );
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
			array( $this, 'section_cb' ),
			'media'
		);

		// Loop the options.
		foreach ( $this->options as $option_id => $option ) {
			// Register setting.
			register_setting( 'media', $option_id, array(
				'sanitize_callback' => $option['sanitize_callback'],
			) );

			// Create field.
			add_settings_field(
				$option_id,
				$option['label'],
				$option['field_callback'],
				'media',
				'lazy-load-responsive-images-section',
				array(
					'label_for'   => $option_id,
					'value'       => $option['value'],
					'description' => ( isset( $option['description'] ) ? $option['description'] : '' ),
				)
			);
		} // End foreach().
	}

	/**
	 * Section callback.
	 *
	 * @param array $args
	 */
	public function section_cb( $args ) {
	}

	/**
	 * Checkbox callback.
	 *
	 * @param array $args               {
	 *                                  Argument array.
	 *
	 * @type string $type               (Required) »themes« or »plugins«.
	 * @type string $label_for          (Required) Value for the for attribute.
	 * @type string $settings           (Required) Theme slug or plugin basename.
	 * @type string $value_array_key    (Required) array key for the value.
	 * }
	 */
	public function checkbox_field_cb( $args ) {
		// Get option value.
		$option_value = $args['value'];

		// Get label for.
		$label_for = esc_attr( $args['label_for'] ); ?>
		<input id="<?php echo $label_for; ?>" name="<?php echo $label_for; ?>"
		       type="checkbox" <?php echo ( $option_value == '1' || $option_value == 'on' ) ? 'checked="checked"' : ''; ?>>
		<?php
		// Check for description.
		if ( '' !== $args['description'] ) { ?>
			<p class="description">
				<?php echo $args['description']; ?>
			</p>
			<?php
		}
	}

	/**
	 * Text field callback.
	 *
	 * @param array $args               {
	 *                                  Argument array.
	 *
	 * @type string $type               (Required) »themes« or »plugins«.
	 * @type string $label_for          (Required) Value for the for attribute.
	 * @type string $settings           (Required) Theme slug or plugin basename.
	 * @type string $value_array_key    (Required) array key for the value.
	 * }
	 */
	public function text_field_cb( $args ) {
		// Get option value.
		$option_value = $args['value'];

		// Get label for.
		$label_for = esc_attr( $args['label_for'] ); ?>
		<input id="<?php echo $label_for; ?>" name="<?php echo $label_for; ?>"
		       type="text" value="<?php echo $option_value; ?>">
		<?php
		// Check for description.
		if ( '' !== $args['description'] ) { ?>
			<p class="description">
				<?php echo $args['description']; ?>
			</p>
			<?php
		}
	}
}
