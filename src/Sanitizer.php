<?php

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

class Sanitizer {
	/**
	 * Sanitize comma separated list of class names.
	 *
	 * @param string $class_names Comma separated list of HTML class names.
	 *
	 * @return string Sanitized comma separated list.
	 */
	public static function sanitize_class_name_list( string $class_names ): string {
		// Get array of the class names.
		$class_names_array = explode( ',', $class_names );

		if ( false === $class_names_array ) {
			return '';
		}

		// Loop through the class names.
		foreach ( $class_names_array as $i => $class_name ) {
			// Save the sanitized class name.
			$class_names_array[ $i ] = sanitize_html_class( $class_name );
		}

		// Implode the class names.
		return implode( ',', $class_names_array );
	}

	/**
	 * Sanitize list of filter names.
	 *
	 * @param string $filters One or more WordPress filters, one per line.
	 *
	 * @return string Sanitized list.
	 */
	public static function sanitize_filter_name_list( string $filters ): string {
		// Get array of the filter names.
		$filters_array = explode( "\n", $filters );

		if ( false === $filters_array ) {
			return '';
		}

		// Loop through the filter names.
		foreach ( $filters_array as $i => $filter ) {
			$function_name_regex = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';
			
			$filters_array[$i] = trim( $filters_array[$i] );
			
			// Check if the filter is a valid PHP function name.
			if ( preg_match( $function_name_regex, $filters_array[$i] ) !== 1 ) {
				unset( $filters_array[$i] );
			}
		}

		// Implode the filter names.
		return implode( "\n", $filters_array );
	}

	/**
	 * Sanitize checkbox.
	 *
	 * @link https://github.com/WPTRT/code-examples/blob/master/customizer/sanitization-callbacks.php
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 *
	 * @return bool Whether the checkbox is checked.
	 */
	public static function sanitize_checkbox( bool $checked ): bool {
		return isset( $checked ) && true == $checked;
	}

	/**
	 * Sanitize textarea input.
	 *
	 * @param string $value Textarea input value.
	 *
	 * @return string Sanitized input.
	 */
	public static function sanitize_textarea( string $value ): string {
		return strip_tags( $value );
	}

	/**
	 * Sanitize hex color value.
	 *
	 * @param string $value The input from the color input.
	 *
	 * @return string The hex value.
	 */
	public static function sanitize_hex_color( string $value ): string {
		// Sanitize the input.
		$sanitized = sanitize_hex_color( $value );
		if ( null !== $sanitized && '' !== $sanitized ) {
			return $value;
		} 
		
		return LOADING_SPINNER_DEFAULT_COLOR;
	}
}