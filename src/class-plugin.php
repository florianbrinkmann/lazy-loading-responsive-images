<?php
/**
 * Main plugin code.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Include helpers class.
 */
require_once 'class-helpers.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

/**
 * Include Settings class
 */
require_once 'class-settings.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

/**
 * Include SmartDomDocument class.
 */
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
		/**
		 * Init customizer settings.
		 */
		new Settings();

		/**
		 * Set helpers.
		 */
		$this->helpers = new Helpers();

		/**
		 * Get the disabled classes and save in property.
		 */
		$this->disabled_classes = explode( ',', get_option( 'lazy_load_responsive_images_disabled_classes' ) );

		/**
		 * Adds lazyload class to content images and adds noscript element.
		 */
		add_filter( 'the_content', array( $this, 'modify_content_images' ), 20 );

		/**
		 * Adds lazyload class to post thumbnails and gallery images.
		 */
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'filter_attachment_image_attributes' ), 20, 3 );

		/**
		 * Adds noscript element to post thumbnail.
		 */
		add_filter( 'post_thumbnail_html', array( $this, 'add_noscript_element_to_post_thumbnail_markup' ), 10, 1 );

		/**
		 * Enqueues scripts and styles.
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ), 20 );

		/**
		 * Adds inline style.
		 */
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		/**
		 * Adds inline script.
		 */
		add_action( 'wp_footer', array( $this, 'add_inline_script' ) );

		/**
		 * Load the language files
		 */
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );

		/**
		 * Action on uninstall.
		 */
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
		/**
		 * Check if we have no content.
		 */
		if ( empty( $content ) ) {
			return $content;
		}

		/**
		 * Check if we are on a feed page.
		 */
		if ( is_feed() ) {
			return $content;
		}

		/**
		 * Check if this is a request in the backend.
		 */
		if ( $this->helpers->is_admin_request() ) {
			return $content;
		}

		/**
		 * Create new SmartDomDocument object.
		 */
		$dom = new SmartDomDocument();

		/**
		 * Load the HTML.
		 */
		$dom->loadHTML( $content );

		/**
		 * Loop through the image elements.
		 */
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			/**
			 * Get the image classes as an array.
			 */
			$img_classes = explode( ' ', $img->getAttribute( 'class' ) );

			/**
			 * Check for intersection with array of classes, which should
			 * not be lazy loaded.
			 */
			$result = array_intersect( $this->disabled_classes, $img_classes );

			/**
			 * Filter empty values.
			 */
			$result = array_filter( $result );

			/**
			 * Check if we have no result.
			 */
			if ( empty( $result ) ) {
				/**
				 * Check if the img not already has the lazyload class.
				 */
				if ( strpos( $img->getAttribute( 'class' ), 'lazyload' ) === false ) {
					/**
					 * Check if the image has sizes and srcset attribute.
					 */
					if ( $img->hasAttribute( 'sizes' ) && $img->hasAttribute( 'srcset' ) ) {
						/**
						 * Get sizes and srcset value.
						 */
						$sizes_attr = $img->getAttribute( 'sizes' );
						$srcset     = $img->getAttribute( 'srcset' );

						/**
						 * Set data-sizes and data-srcset attribute.
						 */
						$img->setAttribute( 'data-sizes', $sizes_attr );
						$img->setAttribute( 'data-srcset', $srcset );

						/**
						 * Remove sizes and srcset attribute.
						 */
						$img->removeAttribute( 'sizes' );
						$img->removeAttribute( 'srcset' );

						/**
						 * Get src value.
						 */
						$src = $img->getAttribute( 'src' );

						/**
						 * Check if we have a src.
						 */
						if ( '' === $src ) {
							/**
							 * Set the value from data-noscript as src.
							 */
							$src = $img->getAttribute( 'data-noscript' );
						}

						/**
						 * Set data-src value.
						 */
						$img->setAttribute( 'data-src', $src );
					} else {
						/**
						 * Get src attribute.
						 */
						$src = $img->getAttribute( 'src' );

						/**
						 * Check if we do not have a value.
						 */
						if ( '' === $src ) {
							/**
							 * Set the value from data-noscript as src.
							 */
							$src = $img->getAttribute( 'data-noscript' );
						}

						/**
						 * Set data-src value.
						 */
						$img->setAttribute( 'data-src', $src );
					} // End if().

					/**
					 * Get the classes.
					 */
					$classes = $img->getAttribute( 'class' );

					/**
					 * Add lazyload class.
					 */
					$classes .= " lazyload";

					/**
					 * Set the class string.
					 */
					$img->setAttribute( 'class', $classes );

					/**
					 * Remove the src attribute.
					 */
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

					/**
					 * Save the content.
					 */
					$content = $dom->saveHTML();
				} // End if().
			} // End if().
		} // End foreach().

		return $content;
	}

	/**
	 * Add lazyload class to post thumbnails and gallery images.
	 *
	 * @param array $attr Array with image attributes.
	 *
	 * @return array Attribute array.
	 */
	public function filter_attachment_image_attributes( $attr ) {
		/**
		 * Check if is feed site.
		 */
		if ( is_feed() ) {
			return $attr;
		}

		/**
		 * Check if is backend site request.
		 */
		if ( true === $this->helpers->is_admin_request() ) {
			return $attr;
		}

		/**
		 * Get the image classes as an array.
		 */
		$img_classes = explode( ' ', $attr['class'] );

		/**
		 * Check for intersection with array of classes, which should
		 * not be lazy loaded.
		 */
		$result = array_intersect( $this->disabled_classes, $img_classes );

		/**
		 * Remove empty values from array.
		 */
		$result = array_filter( $result );

		/**
		 * Check if we have no result.
		 */
		if ( empty( $result ) ) {
			/**
			 * Check for sizes attribute.
			 */
			if ( isset( $attr['sizes'] ) ) {
				/**
				 * Get the sizes value.
				 */
				$data_sizes = $attr['sizes'];

				/**
				 * Unset the sizes attribute.
				 */
				unset( $attr['sizes'] );

				/**
				 * Add data-sizes attribute.
				 */
				$attr['data-sizes'] = $data_sizes;
			} // End if().

			/**
			 * Check for srcset attribute.
			 */
			if ( isset( $attr['srcset'] ) ) {
				/**
				 * Get srcset value.
				 */
				$data_srcset = $attr['srcset'];

				/**
				 * Unset srcset attribute.
				 */
				unset( $attr['srcset'] );

				/**
				 * Set data-srcset attribute.
				 */
				$attr['data-srcset'] = $data_srcset;

				/**
				 * Set data-noscript and data-src attribute.
				 */
				$attr['data-noscript'] = $attr['src'];
				$attr['data-src']      = $attr['src'];

				/**
				 * Unset src attribute.
				 */
				unset( $attr['src'] );
			} // End if().

			$attr['class'] .= ' lazyload';
		} // End if().

		return $attr;
	}

	/**
	 * Add the noscript element to post thumbnail markup.
	 *
	 * @param string $html Post thumbnail HTML.
	 *
	 * @return string Post thumbnail HTML.
	 */
	public function add_noscript_element_to_post_thumbnail_markup( $html ) {
		/**
		 * Check if we have no HTML.
		 */
		if ( empty( $html ) ) {
			return $html;
		}

		/**
		 * Check if we are on a feed page.
		 */
		if ( is_feed() ) {
			return $html;
		}

		/**
		 * Check if this is a request at the backend.
		 */
		if ( $this->helpers->is_admin_request() ) {
			return $html;
		}

		/**
		 * New SmartDomDocument object.
		 */
		$dom = new SmartDomDocument();

		/**
		 * Load HTML.
		 */
		$dom->loadHTML( $html );

		/**
		 * Loop the image.
		 */
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			/**
			 * Save src from data-noscript.
			 */
			$src = $img->getAttribute( 'data-noscript' );

			/**
			 * Save classes string.
			 */
			$classes = $img->getAttribute( 'class' );
		}

		/**
		 * Remove lazyload class.
		 */
		$classes = str_replace( 'lazyload', '', $classes );

		/**
		 * Check for intersection with array of classes, which should
		 * not be lazy loaded.
		 */
		$result = array_intersect( $this->disabled_classes, explode( ' ', $classes ) );

		/**
		 * Remove empty values from array.
		 */
		$result = array_filter( $result );

		/**
		 * Check if we have no result.
		 */
		if ( empty( $result ) ) {
			/**
			 * Add the noscript element.
			 */
			$noscript_element = "<noscript><img src='" . $src . "' class='" . $classes . "'></noscript>";

			/**
			 * Save it inside HTML-
			 */
			$html .= $noscript_element;
		}

		return $html;
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_script() {
		/**
		 * Enqueue lazysizes.
		 */
		wp_enqueue_script( 'lazysizes', plugins_url() . '/lazy-loading-responsive-images/js/lazysizes.min.js', '', false, true );
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 */
	public function add_inline_style() {
		echo '<style>.js img.lazyload {
			display: block;
		}

img.lazyload {
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
		/**
		 * Delete customizer option.
		 */
		delete_option( 'lazy_load_responsive_images_disabled_classes' );
	}
}
