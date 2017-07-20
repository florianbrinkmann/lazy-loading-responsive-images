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
		 * Get admin URL and referrer.
		 */
		$admin_url = strtolower( admin_url() );
		$referrer  = strtolower( wp_get_referer() );

		/**
		 * Check if the referrer does not begin with the admin URL.
		 */
		if ( 0 !== strpos( $referrer, $admin_url ) ) {
			return false;
		} else {
			return true;
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
