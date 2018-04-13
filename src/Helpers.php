<?php
/**
 * Helper methods.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

/**
 * Class Helpers
 *
 * Class with helper methods.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Helpers {

	/**
	 * Checks if this is a request at the backend.
	 *
	 * @return bool true if is admin request, otherwise false.
	 */
	public function is_admin_request() {
		/*
		 * Get current URL. From wp_admin_canonical_url().
		 *
		 * @link https://stackoverflow.com/a/29976742/7774451
		 */
		$current_url = set_url_scheme(
			sprintf(
				'http://%s%s',
				$_SERVER['HTTP_HOST'],
				$_SERVER['REQUEST_URI']
			)
		);

		/*
		 * Get admin URL and referrer.
		 *
		 * @link https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/pluggable.php#L1076
		 */
		$admin_url = strtolower( admin_url() );
		$referrer  = strtolower( wp_get_referer() );

		// Check if this is a admin request. If true, it
		// could also be a AJAX request.
		if ( 0 === strpos( $current_url, $admin_url ) ) {
			// Check if the user comes from a admin page.
			if ( 0 === strpos( $referrer, $admin_url ) ) {
				return true;
			} else {
				/*
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
	 * Checks if we are on an AMP page generated from the Automattic plugin.
	 *
	 * @return bool true if is amp page, false otherwise.
	 */
	public function is_amp_page() {
		// Check if Automattic’s AMP plugin is active and we are on an AMP endpoint.
		if ( function_exists( 'is_amp_endpoint' ) && true === is_amp_endpoint() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sanitize comma separated list of class names.
	 *
	 * @param string $class_names Comma separated list of HTML class names.
	 *
	 * @return string Sanitized comma separated list.
	 */
	public function sanitize_class_name_list( $class_names ) {
		// Get array of the class names.
		$class_names_array = explode( ',', $class_names );

		// Check if we have an array and not false.
		if ( false !== $class_names_array ) {
			$counter = 0;

			// Loop through the class names.
			foreach ( $class_names_array as $class_name ) {
				// Save the sanitized class name.
				$class_names_array[ $counter ] = sanitize_html_class( $class_name );
				$counter ++;
			}

			// Implode the class names.
			$class_names = implode( ',', $class_names_array );

			return $class_names;
		} else {
			return '';
		}
	}

	/**
	 * Sanitize comma separated list of class names.
	 *
	 * @link https://github.com/WPTRT/code-examples/blob/master/customizer/sanitization-callbacks.php
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 *
	 * @return bool Whether the checkbox is checked.
	 */
	public function sanitize_checkbox( $checked ) {
		return ( ( isset( $checked ) && true == $checked ) ? true : false );
	}

	/**
	 * Sanitize hex color value.
	 *
	 * @param string $value The input from the color input.
	 *
	 * @return string The hex value.
	 */
	public function sanitize_hex_color( $value ) {
		// Sanitize the input.
		$sanitized = sanitize_hex_color( $value );
		if ( null !== $sanitized && '' !== $sanitized ) {
			return $value;
		} else {
			return Settings::$loading_spinner_color_default;
		} // End if().
	}

	/**
	 * Enhanced variation of \DOMDocument->saveHTML().
	 *
	 * Fix for cyrillic from https://stackoverflow.com/a/47454019/7774451.
	 * Replacement of doctype, html, and body from archon810\SmartDOMDocument.
	 *
	 * @param \DOMDocument $dom DOMDocument object of the dom.
	 *
	 * @return string DOM or empty string.
	 */
	public function save_html( \DOMDocument $dom ) {
		$xpath      = new \DOMXPath( $dom );
		$first_item = $xpath->query( '/' )->item( 0 );

		return preg_replace(
			array(
				'/^\<\!DOCTYPE.*?<html><body>/si',
				'!</body></html>$!si',
			),
			'',
			$dom->saveHTML( $first_item )
		);
	}
}
