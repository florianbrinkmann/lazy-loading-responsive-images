<?php

declare( strict_types=1 );

/**
 * Class for handling the granular disable option.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;

use function add_post_meta;
use function delete_post_meta;
use function register_post_meta;

/**
 * Class Settings
 *
 * Adds options to the customizer.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class GranularDisableOption {
	use ConfigTrait;
	/**
	 * Array of object types that should show the checkbox to disable lazy loading.
	 *
	 * @var array
	 */
	private $object_types = array();

	/**
	 * GranularDisableOption constructor.
	 *
	 * @param ConfigInterface $config
	 */
	public function __construct( ConfigInterface $config ) {
		$this->processConfig( $config );
	}

	public function init() {
		add_action( 'init', array( $this, 'object_types_filter' ), 11 );

		// Register meta for disabling per page.
		add_action( 'init', array( $this, 'register_post_meta' ), 11 );

		// Publish post actions.
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_checkbox' ), 9 );
		add_action( 'save_post', array( $this, 'save_checkbox' ) );

		// Enqueue Gutenberg script.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Set array of post types that support granular disabling of Lazy Loader features.
	 *
	 * @return void
	 */
	public function object_types_filter(): void {
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
		$this->object_types = (array) apply_filters( 'lazy_loader_disable_option_object_types', $public_post_types );
	}

	/**
	 * Register post meta for disabling plugin per post.
	 *
	 * @return void
	 */
	public function register_post_meta(): void {
		if ( ! is_array( $this->object_types ) ) {
			return;
		}

		foreach ( $this->object_types as $object_type ) {
			register_post_meta(
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

	/**
	 * Add checkbox to Publish Post meta box.
	 *
	 * @link https://github.com/deworg/dewp-planet-feed/
	 *
	 * @return void
	 */
	public function add_checkbox(): void {
		global $post;

		if ( ! in_array( $post->post_type, $this->object_types ) ) {
			return;
		}

		// Check user capability. Not bailing, though, on purpose.
		$maybe_enabled = current_user_can( 'publish_posts' );

		// Get current value of checkbox.
		$value = absint( get_post_meta( $post->ID, 'lazy_load_responsive_images_disabled', true ) );
		include __DIR__ . '/../views/granular-disable-checkbox.php';
	}

	/**
	 * Enqueue script to Gutenberg editor view.
	 */
	public function enqueue_block_editor_assets() {
		if ( isset( $_REQUEST['post'] ) && in_array( get_post_type( $_REQUEST['post'] ), $this->object_types ) && post_type_supports( get_post_type( $_REQUEST['post'] ), 'custom-fields' ) ) {
			$script_asset_file = require( $this->getConfigKey( PLUGIN_PREFIX, 'gutenberg_script_path' ) );
			wp_enqueue_script(
				'lazy-loading-responsive-images-functions',
				$this->getConfigKey( PLUGIN_PREFIX, 'gutenberg_script_url' ),
				$script_asset_file['dependencies'],
				$script_asset_file['version']
			);
		}
	}

	/**
	 * Save option value to post meta.
	 *
	 * @link https://github.com/deworg/dewp-planet-feed/
	 *
	 * @param  int $post_id ID of current post.
	 *
	 * @return void
	 */
	public function save_checkbox( int $post_id ) {
		if ( empty( $post_id ) || empty( $_POST['post_ID'] ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}
		if ( ! in_array( $_POST['post_type'], $this->object_types ) ) {
			return;
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}
		if ( empty( $_POST['disable-lazy-loader'] ) ) {
			delete_post_meta( $post_id, 'lazy_load_responsive_images_disabled' );
			return;
		}

		update_post_meta( $post_id, 'lazy_load_responsive_images_disabled', true );
	}
}
