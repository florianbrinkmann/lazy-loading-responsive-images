<?php

declare( strict_types=1 );

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Class ProcessingNeededCheck
 *
 * Class with medthods to detect if a WP post should be processed.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class ProcessingNeededCheck {

	/**
	 * Hint if the plugin is disabled for this post.
	 *
	 * @var null|int
	 */
	private $disabled_for_current_post = null;

	/**
	 * Checks if this is a request at the backend.
	 *
	 * @return bool true if is admin request, otherwise false.
	 */
	protected function is_admin_request(): bool {
		// Get current URL. From wp_admin_canonical_url().
		// @link https://stackoverflow.com/a/29976742/7774451
		$current_url = set_url_scheme(
			sprintf(
				'http://%s%s',
				$_SERVER['HTTP_HOST'],
				$_SERVER['REQUEST_URI']
			)
		);

		// Get admin URL and referrer.
		// @link https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/pluggable.php#L1076
		$admin_url = strtolower( admin_url() );
		$referrer  = wp_get_referer() !== false ? strtolower( wp_get_referer() ) : '';

		// Check if this is a admin request. If true, it
		// could also be a AJAX request.
		if ( 0 === strpos( $current_url, $admin_url ) ) {
			// Check if the user comes from a admin page.
			if ( 0 === strpos( $referrer, $admin_url ) ) {
				return true;
			}

			// Check for AJAX requests.
			// @link https://gist.github.com/zitrusblau/58124d4b2c56d06b070573a99f33b9ed#file-lazy-load-responsive-images-php-L193
			if ( function_exists( 'wp_doing_ajax' ) ) {
				return ! wp_doing_ajax();
			}

			return ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		} else {
			if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
				return false;
			}
			return ( isset( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'] );
		}
	}

	/**
	 * Checks if we are on an AMP page generated from the Automattic plugin.
	 *
	 * @return bool true if is amp page, false otherwise.
	 */
	protected function is_amp_page(): bool {
		// Check if Automattic’s AMP plugin is active and we are on an AMP endpoint.
		if ( function_exists( 'is_amp_endpoint' ) && true === is_amp_endpoint() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if plugin is disabled for current post.
	 *
	 * @return bool true if disabled, false otherwise.
	 */
	protected function is_disabled_for_post(): bool {
		// Check if the plugin is disabled.
		if ( null === $this->disabled_for_current_post ) {
			$this->disabled_for_current_post = absint( get_post_meta( get_the_ID(), 'lazy_load_responsive_images_disabled', true ) );
		}

		/**
		 * Filter for disabling Lazy Loader on specific pages/posts/….
		 *
		 * @param boolean True if lazy loader should be disabled, false if not.
		 */
		if ( 1 === $this->disabled_for_current_post || true === apply_filters( 'lazy_loader_disabled', false ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the displayed content is something that the plugin should process.
	 * 
	 * @return bool
	 */
	public function run(): bool {
		if ( $this->is_disabled_for_post() ) {
			return false;
		}

		// Check if we are on a feed page.
		if ( is_feed() ) {
			return false;
		}

		// Check if this content is embedded.
		if ( is_embed() ) {
			return false;
		}

		// Check if this is a request in the backend.
		if ( $this->is_admin_request() ) {
			return false;
		}

		// Check for AMP page.
		if ( $this->is_amp_page() ) {
			return false;
		}

		// Check for Oxygen Builder mode.
		if ( defined( 'SHOW_CT_BUILDER' ) ) {
			return false;
		}

		return true;
	}
}
