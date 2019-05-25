<?php
/**
 * Class for adding customizer settings.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

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
	public $load_native_loading_plugin;

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
	 * Value of setting for loading the aspectratio plugin.
	 *
	 * @var string
	 */
	public $load_aspectratio_plugin;

	/**
	 * Value of setting for displaying a loading spinner.
	 *
	 * @var string
	 */
	public $loading_spinner;

	/**
	 * Default loading spinner color.
	 *
	 * @var string
	 */
	public static $loading_spinner_color_default = '#333333';

	/**
	 * Value of setting for loading spinner color.
	 *
	 * @var string
	 */
	public $loading_spinner_color;

	/**
	 * Value of setting for displaying the option to disable the plugin per page/post.
	 *
	 * @var string
	 */
	public $granular_disable_option;

	/**
	 * Array of object types that should show the checkbox to disable lazy loading.
	 * 
	 * @var array
	 */
	public $disable_option_object_types = array();

	/**
	 * String to modify lazysizes config.
	 * 
	 * @var string
	 */
	public $lazysizes_config = '';

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		// Set helpers.
		$this->helpers = new Helpers();

		$this->options = array(
			'lazy_load_responsive_images_disabled_classes'      => array(
				'value'             => get_option( 'lazy_load_responsive_images_disabled_classes', '' ),
				'label'             => __( 'CSS classes to exclude', 'lazy-loading-responsive-images' ),
				'description'       => __( 'Enter one or more CSS classes to exclude them from lazy loading (separated by comma).', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'text_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_class_name_list',
				),
			),
			'lazy_load_responsive_images_enable_for_iframes'    => array(
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_iframes', '0' ),
				'label'             => __( 'Enable lazy loading for iframes', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_native_loading_plugin'    => array(
				'value'             => get_option( 'lazy_load_responsive_images_native_loading_plugin', '0' ),
				'label'             => __( 'Include lazysizes native loading plugin' ),
				'description'       => __( 'The plugin transforms images and iframes to use native lazyloading in browsers that support it.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_unveilhooks_plugin'    => array(
				'value'             => get_option( 'lazy_load_responsive_images_unveilhooks_plugin', '0' ),
				'label'             => __( 'Include lazysizes unveilhooks plugin' ),
				'description'       => __( 'The plugin adds support for lazy loading of background images, scripts, styles, and videos. To use it with background images, scripts and styles, you will need to <a href="https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks">manually modify the markup</a>.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_enable_for_videos'     => array(
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_videos', '0' ),
				'label'             => __( 'Enable lazy loading for videos', 'lazy-loading-responsive-images' ),
				'description'       => __( 'This feature needs the unveilhooks plugin and will automatically load it, regardless of the option to load the unveilhooks plugin is enabled or not.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_enable_for_audios'     => array(
				'value'             => get_option( 'lazy_load_responsive_images_enable_for_audios', '0' ),
				'label'             => __( 'Enable lazy loading for audios', 'lazy-loading-responsive-images' ),
				'description'       => __( 'This feature needs the unveilhooks plugin and will automatically load it, regardless of the option to load the unveilhooks plugin is enabled or not.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_aspectratio_plugin'    => array(
				'value'             => get_option( 'lazy_load_responsive_images_aspectratio_plugin', '0' ),
				'label'             => __( 'Include lazysizes aspectratio plugin', 'lazy-loading-responsive-images' ),
				'description'       => __( 'The plugin helps to avoid content jumping when images are loaded and makes lazy loading work with masonry grids. Works only if width and height attribute are set for the img element. <a href="https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/aspectratio">More info on the plugin page</a>.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_loading_spinner'       => array(
				'value'             => get_option( 'lazy_load_responsive_images_loading_spinner', '0' ),
				'label'             => __( 'Display a loading spinner', 'lazy-loading-responsive-images' ),
				'description'       => __( 'To give the users a hint that there is something loading where they just see empty space. Works best with the aspectratio option. <a href="https://caniuse.com/#feat=svg-smil">Limited browser support.</a>', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_loading_spinner_color' => array(
				'value'             => get_option( 'lazy_load_responsive_images_loading_spinner_color', self::$loading_spinner_color_default ),
				'label'             => __( 'Color of the spinner', 'lazy-loading-responsive-images' ),
				'description'       => __( 'Spinner color in hex format. Default: #333333', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'color_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_hex_color',
				),
			),
			'lazy_load_responsive_images_granular_disable_option' => array(
				'value'             => get_option( 'lazy_load_responsive_images_granular_disable_option', '0' ),
				'label'             => __( 'Enable option to disable plugin per page/post', 'lazy-loading-responsive-images' ),
				'description'       => __( 'Displays a checkbox in the publish area of all post types (pages/posts/CPTs) that lets you disable the plugin on that specific post.', 'lazy-loading-responsive-images' ),
				'field_callback'    => array( $this, 'checkbox_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_checkbox',
				),
			),
			'lazy_load_responsive_images_lazysizes_config' => array(
				'value'             => get_option( 'lazy_load_responsive_images_lazysizes_config', '' ),
				'label'             => __( 'Modify the default config', 'lazy-loading-responsive-images' ),
				'description'       => sprintf( /* translators: s=code example. */
					__( 'Here you can add custom values for the config settings of the <a href="https://github.com/aFarkas/lazysizes/#js-api---options">lazysizes script</a>. An example could look like this, modifying the value for the expand option:%s', 'lazy-loading-responsive-images' ),
					'<br><br><code>window.lazySizesConfig = window.lazySizesConfig || {};</code><br><code>lazySizesConfig.expand = 300;</code>'
				),
				'field_callback'    => array( $this, 'textarea_field_cb' ),
				'sanitize_callback' => array(
					$this->helpers,
					'sanitize_textarea',
				),
			),
		);

		// Fill properties with setting values.
		$this->disabled_classes        = explode( ',', $this->options['lazy_load_responsive_images_disabled_classes']['value'] );
		$this->enable_for_iframes      = $this->options['lazy_load_responsive_images_enable_for_iframes']['value'];
		$this->load_native_loading_plugin = $this->options['lazy_load_responsive_images_native_loading_plugin']['value'];
		$this->load_unveilhooks_plugin = $this->options['lazy_load_responsive_images_unveilhooks_plugin']['value'];
		$this->enable_for_videos       = $this->options['lazy_load_responsive_images_enable_for_videos']['value'];
		$this->enable_for_audios       = $this->options['lazy_load_responsive_images_enable_for_audios']['value'];
		$this->load_aspectratio_plugin = $this->options['lazy_load_responsive_images_aspectratio_plugin']['value'];
		$this->loading_spinner         = $this->options['lazy_load_responsive_images_loading_spinner']['value'];
		$this->loading_spinner_color   = $this->options['lazy_load_responsive_images_loading_spinner_color']['value'];
		$this->granular_disable_option = $this->options['lazy_load_responsive_images_granular_disable_option']['value'];
		$this->lazysizes_config = $this->options['lazy_load_responsive_images_lazysizes_config']['value'];

		// Register settings on media options page.
		add_action( 'admin_init', array( $this, 'settings_init' ), 12 );

		// Include color picker JS.
		add_action( 'admin_enqueue_scripts', array(
			$this,
			'add_color_picker',
		) );

		if ( '1' === $this->granular_disable_option ) {
			add_action( 'init', array( $this, 'disable_option_object_types_filter' ), 11 );

			// Register meta for disabling per page.
			add_action( 'init', array( $this, 'register_post_meta' ), 11 );

			// Publish post actions.
			add_action( 'post_submitbox_misc_actions', array( $this, 'add_checkbox' ), 9 );
			add_action( 'save_post', array( $this, 'save_checkbox' ) );
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
	 * @type string $label_for          (Required) The label for the checkbox.
	 * @type string $value              (Required) The value.
	 * @type string $description        (Required) Description.
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
	 * @type string $label_for          (Required) The label for the text field.
	 * @type string $value              (Required) The value.
	 * @type string $description        (Required) Description.
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

	/**
	 * Textarea field callback.
	 *
	 * @param array $args               {
	 *                                  Argument array.
	 *
	 * @type string $label_for          (Required) The label for the textarea.
	 * @type string $value              (Required) The value.
	 * @type string $description        (Required) Description.
	 * }
	 */
	public function textarea_field_cb( $args ) {
		// Get option value.
		$option_value = $args['value'];

		// Get label for.
		$label_for = esc_attr( $args['label_for'] ); ?>
		<textarea id="<?php echo $label_for; ?>" name="<?php echo $label_for; ?>" style="width: 100%;"><?php echo $option_value; ?></textarea>
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
	 * Color field callback.
	 *
	 * @param array $args               {
	 *                                  Argument array.
	 *
	 * @type string $label_for          (Required) The label for the color
	 *                                  field.
	 * @type string $value              (Required) The value.
	 * @type string $description        (Required) Description.
	 * }
	 */
	public function color_field_cb( $args ) {
		// Get option value.
		$option_value = $args['value'];

		// Get label for.
		$label_for = esc_attr( $args['label_for'] ); ?>
		<input id="<?php echo $label_for; ?>" name="<?php echo $label_for; ?>"
		       type="text" value="<?php echo $option_value; ?>"
		       data-default-color="<?php echo self::$loading_spinner_color_default; ?>"
		       class="lazy-load-responsive-images-color-field">
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
	 * Set array of post types that support granular disabling of Lazy Loader features.
	 */
	public function disable_option_object_types_filter() {
		$public_post_types = get_post_types( array(
			'public' => true,
		), 'names' );

		// Remove attachment post type.
		if ( is_array( $public_post_types ) && isset( $public_post_types['attachment'] ) ) {
			unset( $public_post_types['attachment'] );
		}

		/**
		 * Filter for the object types that should show the checkbox
		 * for disabling the lazy loading functionality. By default, all
		 * public post types (except attachment) are included.
		 * 
		 * @param array $public_post_types An array of post types that should have the option
		 *                                 for disabling.
		 */
		$this->disable_option_object_types = apply_filters( 'lazy_loader_disable_option_object_types', $public_post_types );
	}

	/**
	 * Register post meta for disabling plugin per
	 */
	public function register_post_meta() {
		if ( is_array( $this->disable_option_object_types ) ) {
			foreach ( $this->disable_option_object_types as $object_type ) {
				\register_post_meta(
					$object_type,
					'lazy_load_responsive_images_disabled',
					array(
						'type' => 'boolean',
						'description' => __( 'If the Lazy Loader plugin should be disabled for this page/post/CPT entry', 'lazy-loading-responsive-images' ),
						'single' => true,
						'show_in_rest' => true,
					)
				);
			}
		}
	}

	/**
	 * Add checkbox to Publish Post meta box.
	 *
	 * @link https://github.com/deworg/dewp-planet-feed/
	 */
	public function add_checkbox() {
		global $post;

		if ( ! in_array( $post->post_type, $this->disable_option_object_types ) ) {
			return;
		}

		// Check user capability. Not bailing, though, on purpose.
		$maybe_enabled = current_user_can( 'publish_posts' );
		// This actually defines whether post will be listed in our feed.
		$value = absint( get_post_meta( $post->ID, 'lazy_load_responsive_images_disabled', true ) );
		printf(
			'<div class="misc-pub-section dewp-planet">
				<label for="disable-lazy-loader">
					<input type="checkbox" id="disable-lazy-loader" name="disable-lazy-loader" class="disable-lazy-loader" %s %s />
					<span class="dewp-planet__label-text">%s</span>
				</label>
			</div>',
			$maybe_enabled ? '' : 'disabled',
			$value === 1 ? 'checked' : '',
			__( 'Disable Lazy Loader', 'lazy-loading-responsive-images' )
		);
	}

	/**
	 * Save option value to post meta.
	 *
	 * @link https://github.com/deworg/dewp-planet-feed/
	 *
	 * @param  int $post_id ID of current post.
	 *
	 * @return int          ID of current post.
	 */
	public function save_checkbox( $post_id ) {
		if ( empty( $post_id ) || empty( $_POST['post_ID'] ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( absint( $_POST['post_ID'] ) !== $post_id ) {
			return $post_id;
		}
		if ( ! in_array( $_POST['post_type'], $this->disable_option_object_types ) ) {
			return $post_id;
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return $post_id;
		}
		if ( empty( $_POST['disable-lazy-loader'] ) ) {
			\delete_post_meta( $post_id, 'lazy_load_responsive_images_disabled' );
		} else {
			\add_post_meta( $post_id, 'lazy_load_responsive_images_disabled', true, true );
		}
		return $post_id;
	}
}
