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
		if ( function_exists( 'wp_doing_ajax' ) ) {
			return is_admin() && ! wp_doing_ajax();
		} else {
			return is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
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
