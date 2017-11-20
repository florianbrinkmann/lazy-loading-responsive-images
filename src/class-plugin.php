<?php
/**
 * Main plugin code.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

// Include helpers class.
require_once 'class-helpers.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

// Include Settings class.
require_once 'class-settings.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

// Include SmartDomDocument class.
require_once 'class-smart-dom-document.php';

use archon810\SmartDomDocument as SmartDomDocument;

/**
 * Class Plugin
 *
 * Class for adding lazy loading to responsive images.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class Plugin {
	/**
	 * Helpers object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\Helpers
	 */
	private $helpers;

	/**
	 * Array of classes which should not be lazy loaded.
	 *
	 * @var array
	 */
	private $disabled_classes;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		// Init customizer settings.
		new Settings();

		// Set helpers.
		$this->helpers = new Helpers();

		// Get the disabled classes and save in property.
		$this->disabled_classes = explode( ',', get_option( 'lazy_load_responsive_images_disabled_classes' ) );
	}

	/**
	 * Runs the filters and actions.
	 */
	public function init() {
		// Adds lazyload markup and noscript element to content images.
		add_filter( 'the_content', array( $this, 'modify_content_images' ), 200 );

		// Adds lazyload markup and noscript element to post thumbnail.
		add_filter( 'post_thumbnail_html', array( $this, 'modify_content_images' ), 10, 1 );

		// Enqueues scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ), 20 );

		// Adds inline style.
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		// Adds inline script.
		add_action( 'wp_footer', array( $this, 'add_inline_script' ) );

		// Load the language files
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );

		// Action on uninstall.
		register_uninstall_hook( __FILE__, array( 'FlorianBrinkmann\LazyLoadResponsiveImages\Plugin', 'uninstall' ) );
	}

	/**
	 * Modifies img elements inside the entry content.
	 *
	 * @param string $content Entry content.
	 *
	 * @return string Entry content.
	 */
	public function modify_content_images( $content ) {
		// Check if we have no content.
		if ( empty( $content ) ) {
			return $content;
		}

		// Check if we are on a feed page.
		if ( is_feed() ) {
			return $content;
		}

		// Check if this is a request in the backend.
		if ( $this->helpers->is_admin_request() ) {
			return $content;
		}

		// Check for AMP page.
		if ( true === $this->helpers->is_amp_page() ) {
			return $content;
		}

		// Create new SmartDomDocument object.
		$dom = new SmartDomDocument();

		// Load the HTML.
		$dom->loadHTML( $content );

		// Loop through the image elements.
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			// Get the image classes as an array.
			$img_classes = explode( ' ', $img->getAttribute( 'class' ) );

			// Check for intersection with array of classes, which should
			// not be lazy loaded.
			$result = array_intersect( $this->disabled_classes, $img_classes );

			// Filter empty values.
			$result = array_filter( $result );

			// Check if we have no result.
			if ( empty( $result ) ) {
				// Check if the image has the data-no-lazyload attr.
				if ( $img->hasAttribute( 'data-no-lazyload' ) ) {
					continue;
				} // End if().

				// Check if the img not already has the lazyload class.
				if ( strpos( $img->getAttribute( 'class' ), 'lazyload' ) === false ) {
					// Check if the image has sizes and srcset attribute.
					if ( $img->hasAttribute( 'sizes' ) && $img->hasAttribute( 'srcset' ) ) {
						// Get sizes and srcset value.
						$sizes_attr = $img->getAttribute( 'sizes' );
						$srcset     = $img->getAttribute( 'srcset' );

						// Set data-sizes and data-srcset attribute.
						$img->setAttribute( 'data-sizes', $sizes_attr );
						$img->setAttribute( 'data-srcset', $srcset );

						// Remove sizes and srcset attribute.
						$img->removeAttribute( 'sizes' );
						$img->removeAttribute( 'srcset' );

						// Get src value.
						$src = $img->getAttribute( 'src' );

						// Check if we have a src.
						if ( '' === $src ) {
							// Set the value from data-noscript as src.
							$src = $img->getAttribute( 'data-noscript' );
						}

						// Set data-src value.
						$img->setAttribute( 'data-src', $src );
					} else {
						// Get src attribute.
						$src = $img->getAttribute( 'src' );

						// Check if we do not have a value.
						if ( '' === $src ) {
							// Set the value from data-noscript as src.
							$src = $img->getAttribute( 'data-noscript' );
						}

						// Set data-src value.
						$img->setAttribute( 'data-src', $src );
					} // End if().

					// Get the classes.
					$classes = $img->getAttribute( 'class' );

					// Add lazyload class.
					$classes .= " lazyload";

					// Set the class string.
					$img->setAttribute( 'class', $classes );

					// Remove the src attribute.
					$img->removeAttribute( 'src' );

					/**
					 * Create noscript element.
					 */
					$noscript = $dom->createElement( 'noscript' );

					/**
					 * Insert it before the img node.
					 */
					$noscript_node = $img->parentNode->insertBefore( $noscript, $img );

					/**
					 * Create img element.
					 */
					$noscript_img = $dom->createElement( 'IMG' );

					/**
					 * Remove lazyload class from classes string for noscript element.
					 */
					$classes = str_replace( 'lazyload', '', $classes );

					/**
					 * Set class value.
					 */
					$noscript_img->setAttribute( 'class', $classes );

					/**
					 * Add img node to noscript node.
					 */
					$new_img = $noscript_node->appendChild( $noscript_img );

					/**
					 * Set src value.
					 */
					$new_img->setAttribute( 'src', $src );

					// Save the content.
					$content = $dom->saveHTMLExact();
				} // End if().
			} // End if().
		} // End foreach().

		// Get option for iframe lazyloading and check if it is enabled.
		$lazy_load_iframes = get_option( 'lazy_load_responsive_images_enable_for_iframes', 0 );
		if ( '1' === $lazy_load_iframes ) {
			// Loop through the iframe elements.
			foreach ( $dom->getElementsByTagName( 'iframe' ) as $iframe ) {
				// Get the iframe classes as an array.
				$iframe_classes = explode( ' ', $iframe->getAttribute( 'class' ) );

				// Check for intersection with array of classes, which should
				// not be lazy loaded.
				$result = array_intersect( $this->disabled_classes, $iframe_classes );

				// Filter empty values.
				$result = array_filter( $result );

				// Check if we have no result.
				if ( empty( $result ) ) {
					// Check if the iframe has the data-no-lazyload attr.
					if ( $iframe->hasAttribute( 'data-no-lazyload' ) ) {
						continue;
					} // End if().

					// Save the image original attributes.
					$iframe_attributes = $iframe->attributes;

					// Check if the img not already has the lazyload class.
					if ( strpos( $iframe->getAttribute( 'class' ), 'lazyload' ) === false ) {
						// Check if the iframe has a src attribute.
						if ( $iframe->hasAttribute( 'src' ) ) {
							// Get src attribute.
							$src = $iframe->getAttribute( 'src' );

							// Set data-src value.
							$iframe->setAttribute( 'data-src', $src );
						} else {
							continue;
						} // End if().

						// Get the classes.
						$classes = $iframe->getAttribute( 'class' );

						// Add lazyload class.
						$classes .= " lazyload";

						// Set the class string.
						$iframe->setAttribute( 'class', $classes );

						// Remove the src attribute.
						$iframe->removeAttribute( 'src' );

						// Add noscript element.
						$dom = $this->add_noscript_element( $iframe_attributes, $dom, $iframe, 'IFRAME', $classes,
							$src );

						// Save the content.
						$content = $dom->saveHTMLExact();
					} // End if().
				} // End if().
			} // End foreach().
		}

		return $content;
	}

	/**
	 * Adds noscript element before DOM node.
	 *
	 * @param array            $orig_elem_attr Array of attribute objects of the original element.
	 * @param SmartDomDocument $dom            SmartDomDocument() object of the HTML.
	 * @param DOMNodeList      $elem           Single DOM node.
	 * @param string           $tag_name       Tag name which needs to be created inside the noscript element.
	 * @param array            $classes        Array of the element’s classes.
	 * @param string           $src            Value of the src attribute.
	 *
	 * @return SmartDomDocument The updates DOM.
	 */
	public function add_noscript_element( $orig_elem_attr, $dom, $elem, $tag_name, $classes, $src ) {
		$noscript = $dom->createElement( 'noscript' );

		// Insert it before the img node.
		$noscript_node = $elem->parentNode->insertBefore( $noscript, $elem );

		// Create element.
		$noscript_iframe = $dom->createElement( $tag_name );

		// Remove lazyload class from classes string for noscript element.
		$classes = str_replace( 'lazyload', '', $classes );

		// Set class value.
		$noscript_iframe->setAttribute( 'class', $classes );

		// Add the other attributes of the original element.
		foreach ( $orig_elem_attr as $attr ) {
			// Save name and value.
			$name  = $attr->nodeName;
			$value = $attr->nodeValue;

			// Check if it is class attribute and continue.
			if ( 'class' === $name ) {
				continue;
			}

			// Set attribute to noscript image.
			$noscript_iframe->setAttribute( $name, $value );
		}

		// Add img node to noscript node.
		$new_iframe = $noscript_node->appendChild( $noscript_iframe );

		// Set src value.
		$new_iframe->setAttribute( 'src', $src );

		return $dom;
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_script() {
		// Enqueue lazysizes.
		wp_enqueue_script( 'lazysizes', plugins_url() . '/lazy-loading-responsive-images/js/lazysizes.min.js', '',
			false, true );
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 */
	public function add_inline_style() {
		echo '<style>.js img.lazyload,
 .js iframe.lazyload{
			display: block;
		}

img.lazyload,
iframe.lazyload {
			display: none;
		}</style>';
	}

	/**
	 * Adds inline script.
	 */
	public function add_inline_script() {
		wp_add_inline_script( 'lazysizes', "if (!document.documentElement.classList.contains('js')) {
			document.documentElement.classList.add('js');
		}
		" );
	}

	/**
	 * Loads the plugin translation.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'lazy-loading-responsive-images' );
	}

	/**
	 * Action on plugin uninstall.
	 */
	public function uninstall() {
		// Delete customizer option.
		delete_option( 'lazy_load_responsive_images_disabled_classes' );
		delete_option( 'lazy_load_responsive_images_enable_for_iframes' );
	}
}
