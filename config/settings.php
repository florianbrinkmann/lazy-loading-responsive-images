<?php
namespace FlorianBrinkmann\LazyLoadResponsiveImages;

const PLUGIN_PREFIX = 'FlorianBrinkmann\LazyLoadResponsiveImages';
const LAZY_LOADER_DISABLED_CLASSES = 'classes_to_exclude';
const LAZY_LOADER_DISABLED_CLASSES_WP_OPTION_NAME = 'lazy_load_responsive_images_disabled_classes';
const LAZY_LOADER_ADDITIONAL_FILTERS = 'additional_filters';
const LAZY_LOADER_ADDITIONAL_FILTERS_WP_OPTION_NAME = 'lazy_load_responsive_images_additional_filters';
const LAZY_LOADER_ENABLE_FOR_IFRAMES = 'enable_for_iframes';
const LAZY_LOADER_ENABLE_FOR_IFRAMES_WP_OPTION_NAME = 'lazy_load_responsive_images_enable_for_iframes';
const LAZY_LOADER_NATIVE_LAZY_LOAD = 'native_lazy_load';
const LAZY_LOADER_NATIVE_LAZY_LOAD_WP_OPTION_NAME = 'lazy_load_responsive_images_native_loading_plugin';
const LAZY_LOADER_UNVEILHOOKS_PLUGIN = 'unveilhooks_plugin';
const LAZY_LOADER_UNVEILHOOKS_PLUGIN_WP_OPTION_NAME = 'lazy_load_responsive_images_unveilhooks_plugin';
const LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES = 'enable_for_background_images';
const LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES_WP_OPTION_NAME = 'lazy_load_responsive_images_enable_for_background_images';
const LAZY_LOADER_ENABLE_FOR_VIDEOS = 'enable_for_videos';
const LAZY_LOADER_ENABLE_FOR_VIDEOS_WP_OPTION_NAME = 'lazy_load_responsive_images_enable_for_videos';
const LAZY_LOADER_ENABLE_FOR_AUDIOS = 'enable_for_audios';
const LAZY_LOADER_ENABLE_FOR_AUDIOS_WP_OPTION_NAME = 'lazy_load_responsive_images_enable_for_audios';
const LAZY_LOADER_LOADING_SPINNER = 'loading_spinner';
const LAZY_LOADER_LOADING_SPINNER_WP_OPTION_NAME = 'lazy_load_responsive_images_loading_spinner';
const LAZY_LOADER_LOADING_SPINNER_COLOR = 'loading_spinner_color';
const LAZY_LOADER_LOADING_SPINNER_COLOR_WP_OPTION_NAME = 'lazy_load_responsive_images_loading_spinner_color';
const LAZY_LOADER_LOADING_SPINNER_DEFAULT_COLOR = '#333333';
const LAZY_LOADER_GRANULAR_DISABLE_OPTION = 'granular_disable_option';
const LAZY_LOADER_GRANULAR_DISABLE_OPTION_WP_OPTION_NAME = 'lazy_load_responsive_images_granular_disable_option';
const LAZY_LOADER_PROCESS_COMPLETE_MARKUP = 'process_complete_markup';
const LAZY_LOADER_PROCESS_COMPLETE_MARKUP_WP_OPTION_NAME = 'lazy_load_responsive_images_process_complete_markup';
const LAZY_LOADER_LAZYSIZES_CONFIG = 'lazysizes_config';
const LAZY_LOADER_LAZYSIZES_CONFIG_WP_OPTION_NAME = 'lazy_load_responsive_images_lazysizes_config';
const LAZY_LOADER_ALLOWED_DESCRIPTION_HTML = array(
	'a' => array( 'href' => array() ),
	'br' => array(),
	'code' => array(),
	'strong' => array(),
);
const LAZY_LOADER_ATTRS_TO_STRIP_FROM_FALLBACK_ELEM = 'attrs_to_strip_from_fallback_elem';

$settings = [
	LAZY_LOADER_DISABLED_CLASSES => [
		'wp_option_name' => LAZY_LOADER_DISABLED_CLASSES_WP_OPTION_NAME,
		'value' => get_option( LAZY_LOADER_DISABLED_CLASSES_WP_OPTION_NAME, '' ),
		'label' => __( 'CSS classes to exclude', 'lazy-loading-responsive-images' ),
		'description' => __( 'Enter one or more CSS classes to exclude them from lazy loading (separated by comma). This works only if the element that would get lazy loaded has the class, not on wrapper elements. To exclude an element and its children, use the <code>skip-lazy</code> class or the <code>data-skip-lazy</code> attribute.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/text.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_class_name_list' ],
	],
	LAZY_LOADER_ADDITIONAL_FILTERS => [
		'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_ADDITIONAL_FILTERS_WP_OPTION_NAME,
		'value' => get_option( LAZY_LOADER_ADDITIONAL_FILTERS_WP_OPTION_NAME, '' ),
		'label' => __( 'Additional filters', 'lazy-loading-responsive-images' ),
		'description' => __( 'Enter one or more additional WordPress filters that should be processed (one per line), for example, <code>wp_get_attachment_image</code>. Anything that does not match the regular expression for PHP function names will be removed.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/textarea.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_filter_name_list' ],
	],
	LAZY_LOADER_ENABLE_FOR_IFRAMES => [
		'wp_option_name' => LAZY_LOADER_ENABLE_FOR_IFRAMES_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_ENABLE_FOR_IFRAMES_WP_OPTION_NAME, '0' ),
		'label' => __( 'Enable lazy loading for iframes', 'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_NATIVE_LAZY_LOAD => [
		'wp_option_name' => LAZY_LOADER_NATIVE_LAZY_LOAD_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_NATIVE_LAZY_LOAD_WP_OPTION_NAME, '0' ),
		'label' => __( 'Include lazysizes native loading plugin' ),
		'description' => __( 'The plugin transforms images and iframes to use native lazyloading in browsers that support it. <strong>Important:</strong> Supporting browsers will use their threshold to decide if media needs to be loaded. That might lead to media being loaded even if it is far away from the visible area.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_UNVEILHOOKS_PLUGIN => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_UNVEILHOOKS_PLUGIN_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_UNVEILHOOKS_PLUGIN_WP_OPTION_NAME, '0' ),
		'label' => __( 'Include lazysizes unveilhooks plugin' ),
		'description' => __( 'The plugin adds support for lazy loading of background images, scripts, styles, and videos. To use it with background images, scripts and styles, you will need to <a href="https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks">manually modify the markup</a>.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES => [
		'wp_option_name' => LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES_WP_OPTION_NAME, '0' ),
		'label' => __( 'Enable lazy loading for inline background images',
		                           'lazy-loading-responsive-images' ),
		'description' => __( 'This feature needs the unveilhooks plugin and will automatically load it, regardless of the option to load the unveilhooks plugin is enabled or not. 
        <strong>It is possible that this setting causes issues, because:</strong> To also support multiple background images and to provide a 
        fallback for disabled JavaScript, the plugin removes the background rules from the element and adds a style element instead.
        The CSS selector is <code>.unique-class.lazyloaded</code> for the JS case, respective <code>.unique-class.lazyload</code> for the case that JS is disabled.
        If you have CSS background rules with a higher specifity that match the element, they will overwrite the rules
        that were extracted by Lazy Loader.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_ENABLE_FOR_VIDEOS => [
		'wp_option_name' => LAZY_LOADER_ENABLE_FOR_VIDEOS_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_ENABLE_FOR_VIDEOS_WP_OPTION_NAME, '0' ),
		'label' => __( 'Enable lazy loading for videos', 'lazy-loading-responsive-images' ),
		'description' => __( 'This feature needs the unveilhooks plugin and will automatically load it, regardless of the option to load the unveilhooks plugin is enabled or not.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_ENABLE_FOR_AUDIOS => [
		'wp_option_name' => LAZY_LOADER_ENABLE_FOR_AUDIOS_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_ENABLE_FOR_AUDIOS_WP_OPTION_NAME, '0' ),
		'label' => __( 'Enable lazy loading for audios', 'lazy-loading-responsive-images' ),
		'description' => __( 'This feature needs the unveilhooks plugin and will automatically load it, regardless of the option to load the unveilhooks plugin is enabled or not.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_LOADING_SPINNER => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_LOADING_SPINNER_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_LOADING_SPINNER_WP_OPTION_NAME, '0' ),
		'label' => __( 'Display a loading spinner', 'lazy-loading-responsive-images' ),
		'description' => __( 'To give the users a hint that there is something loading where they just see empty space. Works best with the aspectratio option. <a href="https://caniuse.com/#feat=svg-smil">Limited browser support.</a>',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_LOADING_SPINNER_COLOR => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_LOADING_SPINNER_COLOR_WP_OPTION_NAME,
		'value' => get_option( LAZY_LOADER_LOADING_SPINNER_COLOR_WP_OPTION_NAME, LAZY_LOADER_LOADING_SPINNER_DEFAULT_COLOR ),
		'label' => __( 'Color of the spinner', 'lazy-loading-responsive-images' ),
		'description' => __( 'Spinner color in hex format. Default: #333333', 'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/color.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_hex_color' ],
	],
	LAZY_LOADER_GRANULAR_DISABLE_OPTION => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_GRANULAR_DISABLE_OPTION_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_GRANULAR_DISABLE_OPTION_WP_OPTION_NAME, '0' ),
		'label' => __( 'Enable option to disable plugin per page/post', 'lazy-loading-responsive-images' ),
		'description' => __( 'Displays a checkbox in the publish area of all post types (pages/posts/CPTs) that lets you disable the plugin on that specific post. To make it work for CPTs, they must support <code>custom-fields</code>.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_PROCESS_COMPLETE_MARKUP => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_PROCESS_COMPLETE_MARKUP_WP_OPTION_NAME,
		'value' => (bool) get_option( LAZY_LOADER_PROCESS_COMPLETE_MARKUP_WP_OPTION_NAME, '0' ),
		'label' => __( 'Process the complete markup', 'lazy-loading-responsive-images' ),
		'description' => __( 'Instead of just modifying specific parts of the page (for example, the post content, post thumbnail), the complete generated markup is processed. With that, all images (and other media, if you enabled it) will be lazy loaded. Because the plugin needs to process more markup with that option enabled, it might slow down the page generation time a bit. If your page contains HTML errors, like unclosed tags, this might lead to unwanted behavior, because the DOM parser used by Lazy Loader tries to correct that.',
		                           'lazy-loading-responsive-images' ),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/checkbox.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_checkbox' ],
	],
	LAZY_LOADER_LAZYSIZES_CONFIG => [
        'wp_setting' => true,
		'wp_option_name' => LAZY_LOADER_LAZYSIZES_CONFIG_WP_OPTION_NAME,
		'value' => get_option( LAZY_LOADER_LAZYSIZES_CONFIG_WP_OPTION_NAME, '' ),
		'label' => __( 'Modify the default config', 'lazy-loading-responsive-images' ),
		'description' => sprintf( /* translators: s=code example. */
			__( 'Here you can add custom values for the config settings of the <a href="https://github.com/aFarkas/lazysizes/#js-api---options">lazysizes script</a>. An example could look like this, modifying the value for the expand option:%s',
			    'lazy-loading-responsive-images' ),
			'<br><br><code>window.lazySizesConfig = window.lazySizesConfig || {};</code><br><code>lazySizesConfig.expand = 300;</code>'
		),
		'field_callback' => function( $args ) {
			include __DIR__ . '/../views/fields/textarea.php';
		},
		'sanitize_callback' => [ '\FlorianBrinkmann\LazyLoadResponsiveImages\Sanitizer', 'sanitize_textarea' ],
	]
];

$setting_values = [];

// Build subconfig arrays.
foreach ( $settings as $key => $setting ) {
    $wp_setting = $setting['wp_setting'] ?? false;
    $setting_group = $wp_setting === true ? 'wp' : 'core';
    $setting_values[$setting_group][$key] = $setting['value'];
}

return [
    'FlorianBrinkmann' => [
        'LazyLoadResponsiveImages' => [
            'wp_setting_values' => $setting_values['wp'] ?? [],
            'core_setting_values' => $setting_values['core'] ?? [],
            'setting_controls' => $settings,
	        'gutenberg_script_url' => plugins_url( 'js/build/functions.js', __DIR__ . '/..' ),
	        'gutenberg_script_path' => __DIR__ . '/../js/build/functions.asset.php',
        ]
    ]
];