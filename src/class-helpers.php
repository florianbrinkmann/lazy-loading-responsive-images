<?php
/**
 * Helper methods.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Class Helpers
 *
 * Class with helper methods.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Helpers {
	/**
	 * Check if this is a request at the backend.
	 *
	 * @return bool true if is admin request, otherwise false.
	 */
	public function is_admin_request() {
		/**
		 * Get current URL.
		 *
		 * @link https://wordpress.stackexchange.com/a/126534
		 */
		$current_url = home_url( add_query_arg( null, null ) );

		/**
		 * Get admin URL and referrer.
		 *
		 * @link https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/pluggable.php#L1076
		 */
		$admin_url = strtolower( admin_url() );
		$referrer  = strtolower( wp_get_referer() );

		/**
		 * Check if this is a admin request. If true, it
		 * could also be a AJAX request.
		 */
		if ( 0 === strpos( $current_url, $admin_url ) ) {
			/**
			 * Check if the user comes from a admin page.
			 */
			if ( 0 === strpos( $referrer, $admin_url ) ) {
				return true;
			} else {
				/**
				 * Check for AJAX requests.
				 *
				 * @link https://gist.github.com/zitrusblau/58124d4b2c56d06b070573a99f33b9ed#file-lazy-load-responsive-images-php-L193
				 */
				if ( function_exists( 'wp_doing_ajax' ) ) {
					return ! wp_doing_ajax();
				} else {
					return ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * Sanitize comma separated list of class names
	 *
	 * @param string $class_names Comma separated list of HTML class names.
	 *
	 * @return string Sanitized comma separated list.
	 */
	public function sanitize_class_name_list( $class_names ) {
		/**
		 * Get array of the class names.
		 */
		$class_names_array = explode( ',', $class_names );

		/**
		 * Check if we have an array and not false.
		 */
		if ( false !== $class_names_array ) {
			$counter = 0;
			/**
			 * Loop through the class names.
			 */
			foreach ( $class_names_array as $class_name ) {
				/**
				 * Save the sanitized class name.
				 */
				$class_names_array[ $counter ] = sanitize_html_class( $class_name );
				$counter ++;
			}

			/**
			 * Implode the class names.
			 */
			$class_names = implode( ',', $class_names_array );

			return $class_names;
		} else {
			return '';
		}
	}
}
