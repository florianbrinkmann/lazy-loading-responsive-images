<?php
/**
 * Main plugin code.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

use Masterminds\HTML5;

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
	 * Settings object.
	 *
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\Settings
	 */
	private $settings;

	/**
	 * Array of classes which should not be lazy loaded.
	 *
	 * @var array
	 */
	private $disabled_classes;

	/**
	 * Basename of the plugin.
	 *
	 * @var string
	 */
	protected $basename;

	/**
	 * Placeholder data uri for img src attributes.
	 *
	 * @link https://stackoverflow.com/a/13139830
	 *
	 * @var string
	 */
	private $src_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	/**
	 * Counter for background image classes.
	 */
	private $background_image_number = 1;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

	}

	/**
	 * Runs the filters and actions.
	 */
	public function init() {
		// Init settings.
		$this->settings = new Settings();

		// Set helpers.
		$this->helpers = new Helpers();

		// Get the disabled classes and save in property.
		$this->disabled_classes = $this->settings->get_disabled_classes();

		// Add link to settings in the plugin list.
		add_filter( 'plugin_action_links', array(
			$this,
			'plugin_action_links',
		), 10, 2 );

		add_action( 'init', array( $this, 'init_content_processing' ) );
		
		// Enqueues scripts and styles.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_script',
		), 20 );

		// Adds inline style.
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		// Enqueue Gutenberg script.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Load the language files.
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );

		// Action on uninstall.
		register_uninstall_hook( $this->basename, 'FlorianBrinkmann\LazyLoadResponsiveImages\Plugin::uninstall' );
	}

	/**
	 * Run actions and filters to start content processing.
	 */
	public function init_content_processing() {
		// Check if the complete markup should be processed.
		if ( '1' === $this->settings->get_process_complete_markup() ) {
			add_action( 'template_redirect', array( $this, 'process_complete_markup' ) );
		} else {
			// Filter markup of the_content() calls to modify media markup for lazy loading.
			add_filter( 'the_content', array( $this, 'filter_markup' ), 10001 );

			// Filter markup of Text widget to modify media markup for lazy loading.
			add_filter( 'widget_text', array( $this, 'filter_markup' ) );

			// Filter markup of gravatars to modify markup for lazy loading.
			add_filter( 'get_avatar', array( $this, 'filter_markup' ) );

			// Adds lazyload markup and noscript element to post thumbnail.
			add_filter( 'post_thumbnail_html', array(
				$this,
				'filter_markup',
			), 10001, 1 );

			$additional_filters = $this->settings->get_additional_filters();

			if ( is_array( $additional_filters ) && ! empty( $additional_filters ) ) {
				foreach ( $additional_filters as $filter ) {
					add_filter( $filter, array( $this, 'filter_markup' ) );
				}
			}
		}
	}

	/**
	 * Run output buffering, to process the complete markup.
	 */
	public function process_complete_markup() {
		// If this is no content we should process, exit as early as possible.
		if ( ! $this->helpers->is_post_to_process() ) {
			return;
		}

		ob_start( array( $this, 'filter_markup' ) );
	}

	/**
	 * Add settings link to the plugin entry in the plugin list.
	 *
	 * @param array  $links Array of action links.
	 * @param string $file  Basename of the plugin.
	 *
	 * @return array The action links array.
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file === $this->basename ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				'options-media.php#lazy-loader-options',
				__( 'Settings', 'lazy-loading-responsive-images' )
			);
		}

		return $links;
	}

	/**
	 * Modifies elements to automatically enable lazy loading.
	 *
	 * @param string $content HTML.
	 *
	 * @return string Modified HTML.
	 */
	public function filter_markup( $content ) {
		// If this is no content we should process, exit as early as possible.
		if ( ! $this->helpers->is_post_to_process() ) {
			return $content;
		}

		// Check if we have no content.
		if ( empty( $content ) ) {
			return $content;
		}

		// Check if content contains caption shortcode.
		if ( has_shortcode( $content, 'caption' ) ) {
			return $content;
		}

		// Disable libxml errors.
		libxml_use_internal_errors( true );

		// Create new HTML5 object.
		$html5 = new HTML5( array(
            'disable_html_ns' => true,
        ) );

		// Preserve html entities, script tags and conditional IE comments.
		// @link https://github.com/ivopetkov/html5-dom-document-php.
		$content = preg_replace( '/&([a-zA-Z]*);/', 'lazy-loading-responsive-images-entity1-$1-end', $content );
		$content = preg_replace( '/&#([0-9]*);/', 'lazy-loading-responsive-images-entity2-$1-end', $content );
		$content = preg_replace( '/<!--\[([\w ]*)\]>/', '<!--[$1]>-->', $content );
		$content = str_replace( '<![endif]-->', '<!--<![endif]-->', $content );
		$content = str_replace( '<script>', '<!--<script>', $content );
		$content = str_replace( '<script ', '<!--<script ', $content );
		$content = str_replace( '</script>', '</script>-->', $content );

		// Load the HTML.
		$dom = $html5->loadHTML( $content );

		$xpath = new \DOMXPath( $dom );

		// Get all nodes except the ones that live inside a noscript element.
		// @link https://stackoverflow.com/a/19348287/7774451.
		$nodes = $xpath->query( "//*[not(ancestor-or-self::noscript)][not(ancestor-or-self::*[contains(@class, 'disable-lazyload') or contains(@class, 'skip-lazy') or @data-skip-lazy])]" );

		$is_modified = false;

		foreach ( $nodes as $node ) {
			// Check if it is an element that should not be lazy loaded.
			// Get the classes as an array.
			$node_classes = explode( ' ', $node->getAttribute( 'class' ) );

			// Check for intersection with array of classes, which should
			// not be lazy loaded.
			$result = array_intersect( $this->disabled_classes, $node_classes );

			// Filter empty values.
			$result = array_filter( $result );

			/*
			 * Check if:
			 * - we have no result from the array intersection.
			 * - the node does not have the data-no-lazyload attr.
			 * - the node does not already have the lazyload class.
			 */
			if ( ! empty( $result ) || $node->hasAttribute( 'data-no-lazyload' ) || in_array( 'lazyload', $node_classes, true ) ) {
				continue;
			}

			// Check if the element has a style attribute with a background image.
			if (
				'1' === $this->settings->get_enable_for_background_images()
				&& $node->hasAttribute( 'style' )
				&& 'img' !== $node->tagName
				&& 'picture' !== $node->tagName
				&& 'iframe' !== $node->tagName
				&& 'video' !== $node->tagName
				&& 'audio' !== $node->tagName
			) {
				if ( 1 === preg_match( '/background(-[a-z]+)?:(.)*url\(["\']?([^"\']*)["\']?\)([^;])*;?/', $node->getAttribute( 'style' ) ) ) {
					$dom = $this->modify_background_img_markup( $node, $dom );
					$is_modified = true;
				}
			}

			// Check if it is one of the supported elements and support for it is enabled.
			if ( 'img' === $node->tagName && 'source' !== $node->parentNode->tagName && 'picture' !== $node->parentNode->tagName ) {
				$dom = $this->modify_img_markup( $node, $dom );
				$is_modified = true;
			}

			if ( 'picture' === $node->tagName ) {
				$dom = $this->modify_picture_markup( $node, $dom );
				$is_modified = true;
			}

			if ( '1' === $this->settings->get_enable_for_iframes() && 'iframe' === $node->tagName ) {
				$dom = $this->modify_iframe_markup( $node, $dom );
				$is_modified = true;
			}

			if ( '1' === $this->settings->get_enable_for_videos() && 'video' === $node->tagName ) {
				$dom = $this->modify_video_markup( $node, $dom );
				$is_modified = true;
			}

			if ( '1' === $this->settings->get_enable_for_audios() && 'audio' === $node->tagName ) {
				$dom = $this->modify_audio_markup( $node, $dom );
				$is_modified = true;
			}
		}

		if ( true === $is_modified ) {
			if ( '1' === $this->settings->get_process_complete_markup() ) {
				// If someone directly passed markup to the plugin, no doctype will be present. So we need to check for a parse error first.
				$errors = $html5->getErrors();
				$no_doctype = false;
				if ( is_array( $errors ) && ! empty( $errors ) ) {
					foreach ( $errors as $error ) {
						if ( strpos( $error, 'No DOCTYPE specified.' ) !== false ) {
							$no_doctype = true;
							$content = $this->helpers->save_html( $dom, $html5 );
							break;
						}
					}
				}
				if ( $no_doctype === false ) {
					$content = $html5->saveHTML( $dom );
				}
			} else {
				$content = $this->helpers->save_html( $dom, $html5 );
			}
		}

		// Restore the entities and script tags.
		// @link https://github.com/ivopetkov/html5-dom-document-php/blob/9560a96f63a7cf236aa18b4f2fbd5aab4d756f68/src/HTML5DOMDocument.php#L343.
		if ( strpos( $content, 'lazy-loading-responsive-images-entity') !== false || strpos( $content, '<!--<script' ) !== false ) {
			$content = preg_replace('/lazy-loading-responsive-images-entity1-(.*?)-end/', '&$1;', $content );
			$content = preg_replace('/lazy-loading-responsive-images-entity2-(.*?)-end/', '&#$1;', $content );
			$content = preg_replace( '/<!--\[([\w ]*)\]>-->/', '<!--[$1]>', $content );
			$content = str_replace( '<!--<![endif]-->', '<![endif]-->', $content );
			$content = str_replace( '<!--<script>', '<script>', $content );
			$content = str_replace( '<!--<script ', '<script ', $content );
			$content = str_replace( '</script>-->', '</script>', $content );
		}

		return $content;
	}

	/**
	 * Modifies element markup for lazy loading inline background image.
	 *
	 * @param \DOMNode     $node    The node with the inline background image.
	 * @param \DOMDocument $dom     \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_background_img_markup( $node, $dom ) {
		$original_css = $node->getAttribute( 'style' );
		$classes = $node->getAttribute( 'class' );
		// It is possible that there are multiple background rules for a inline element (including ones for size, position, et cetera).
		// We will insert all of them to the inline style element, to not mess up their order.
		if ( 0 !== preg_match_all( '/background(-[a-z]+)?:([^;])*;?/', $node->getAttribute( 'style' ), $matches ) ) {
			// $matches[0] contains the full CSS background rules.
			// We remove the rules from the inline style.
			$modified_css = str_replace( $matches[0], '', $original_css );
			$node->setAttribute( 'style', $modified_css );

			// Build string of background rules.
			$background_rules_string = implode( ' ', $matches[0] );

			// Add unique class and lazyload class to element.
			$unique_class = "lazy-loader-background-element-$this->background_image_number";
			$classes .= " lazyload $unique_class ";
			$node->setAttribute( 'class', $classes );
			$this->background_image_number++;

			// Create style element with the background rule.
			$background_style_elem = $dom->createElement( 'style', ".$unique_class.lazyloaded{ $background_rules_string }" );
			$node->parentNode->insertBefore( $background_style_elem, $node );

			// Add the noscript element.
			$noscript = $dom->createElement( 'noscript' );

			// Insert it before the img node.
			$noscript_node = $node->parentNode->insertBefore( $noscript, $node );

			// Create element.
			$background_style_elem_noscript = $dom->createElement( 'style', ".$unique_class.lazyload{ $background_rules_string }" );

			// Add media node to noscript node.
			$noscript_node->appendChild( $background_style_elem_noscript );
		}
		return $dom;
	}

	/**
	 * Modifies img markup to enable lazy loading.
	 *
	 * @param \DOMNode     $img             The img dom node.
	 * @param \DOMDocument $dom             \DOMDocument() object of the HTML.
	 * @param boolean      $create_noscript Whether to create a noscript element for the img or not.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_img_markup( $img, $dom, $create_noscript = true ) {
		// Check if the element already has a data-src attribute (might be the case for
		// plugins that bring their own lazy load functionality) and skip it to prevent conflicts.
		if ( $img->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		// Add noscript element.
		if ( true === $create_noscript ) {
			$dom = $this->add_noscript_element( $dom, $img );
		}

		// Check if the image has sizes and srcset attribute.
		$sizes_attr = '';
		if ( $img->hasAttribute( 'sizes' ) ) {
			// Get sizes value.
			$sizes_attr = $img->getAttribute( 'sizes' );

			// Check if the value is auto. If so, we modify it to data-sizes.
			if ( 'auto' === $sizes_attr ) {
				// Set data-sizes attribute.
				$img->setAttribute( 'data-sizes', $sizes_attr );

				// Remove sizes attribute.
				$img->removeAttribute( 'sizes' );
			}
		}

		if ( $img->hasAttribute( 'srcset' ) ) {
			// Get srcset value.
			$srcset = $img->getAttribute( 'srcset' );

			// Set data-srcset attribute.
			$img->setAttribute( 'data-srcset', $srcset );

			// Set srcset attribute with src placeholder to produce valid markup.
			$img_width  = $img->getAttribute( 'width' );
			if ( '' !== $img_width ) {
				$img->setAttribute( 'srcset', "$this->src_placeholder {$img_width}w" );
			} elseif ( '' === $img_width && '' !== $sizes_attr ) {
				$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
				if ( \is_numeric ( $width ) ) {
					$img->setAttribute( 'srcset', "$this->src_placeholder {$width}w" );
				} else {
					$img->removeAttribute( 'srcset' );
				}
			} else {
				// Remove srcset attribute.
				$img->removeAttribute( 'srcset' );
			}
		}

		// Get src value.
		$src = $img->getAttribute( 'src' );

		// Set data-src value.
		$img->setAttribute( 'data-src', $src );

		if ( '1' === $this->settings->get_load_aspectratio_plugin() ) {
			// Get width and height.
			$img_width  = $img->getAttribute( 'width' );
			$img_height = $img->getAttribute( 'height' );

			if ( '' !== $img_width && '' !== $img_height ) {
				$img->setAttribute( 'data-aspectratio', "$img_width/$img_height" );
			}
		}

		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			$img->setAttribute( 'loading', 'lazy' );
		}

		// Get the classes.
		$classes = $img->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$img->setAttribute( 'class', $classes );

		// Set data URI for src attribute.
		$img->setAttribute( 'src', $this->src_placeholder );

		return $dom;
	}

	/**
	 * Modifies picture element markup to enable lazy loading.
	 *
	 * @param \DOMNode     $picture The source dom node.
	 * @param \DOMDocument $dom     \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_picture_markup( $picture, $dom ) {
		// Check if img element already has `layzload` class.
		$img_element = $picture->getElementsByTagName( 'img' );

		foreach ( $img_element as $img ) {
			if ( in_array( 'lazyload', explode( ' ', $img->getAttribute( 'class' ) ) ) ) {
				return $dom;
			}
		}
		
		// Add noscript element.
		$dom = $this->add_noscript_element( $dom, $picture );

		// Get source elements from picture.
		$source_elements = $picture->getElementsByTagName( 'source' );

		// Loop the source elements if there are some.
		if ( 0 !== $source_elements->length ) {
			foreach ( $source_elements as $source_element ) {
				// Check if we have a sizes attribute.
				$sizes_attr = '';
				if ( $source_element->hasAttribute( 'sizes' ) ) {
					// Get sizes value.
					$sizes_attr = $source_element->getAttribute( 'sizes' );

					// Check if the value is auto. If so, we modify it to data-sizes.
					if ( 'auto' === $sizes_attr ) {
						// Set data-sizes attribute.
						$source_element->setAttribute( 'data-sizes', $sizes_attr );

						// Remove sizes attribute.
						$source_element->removeAttribute( 'sizes' );
					}
				}

				// Check for srcset.
				if ( $source_element->hasAttribute( 'srcset' ) ) {
					// Get srcset value.
					$srcset = $source_element->getAttribute( 'srcset' );

					// Set data-srcset attribute.
					$source_element->setAttribute( 'data-srcset', $srcset );

					// Set srcset attribute with src placeholder to produce valid markup.
					if ( '' !== $sizes_attr ) {
						$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
						if ( \is_numeric ( $width ) ) {
							$source_element->setAttribute( 'srcset', "$this->src_placeholder {$width}w" );
						} else {
							$source_element->removeAttribute( 'srcset' );
						}
					} else {
						// Remove srcset attribute.
						$source_element->removeAttribute( 'srcset' );
					}
				}

				if ( $source_element->hasAttribute( 'src' ) ) {
					// Get src value.
					$src = $source_element->getAttribute( 'src' );

					// Set data-src value.
					$source_element->setAttribute( 'data-src', $src );

					// Set data URI for src attribute.
					$source_element->setAttribute( 'src', $this->src_placeholder );
				}
			}
		}

		// Loop the img element.
		foreach ( $img_element as $img ) {
			$this->modify_img_markup( $img, $dom, false );
		}

		return $dom;
	}

	/**
	 * Modifies iframe markup to enable lazy loading.
	 *
	 * @param \DOMNode     $iframe The iframe dom node.
	 * @param \DOMDocument $dom    \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_iframe_markup( $iframe, $dom ) {
		// Add noscript element.
		$dom = $this->add_noscript_element( $dom, $iframe );

		// Check if the iframe has a src attribute.
		if ( $iframe->hasAttribute( 'src' ) ) {
			// Get src attribute.
			$src = $iframe->getAttribute( 'src' );

			// Set data-src value.
			$iframe->setAttribute( 'data-src', $src );
		} else {
			return $dom;
		}

		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			$iframe->setAttribute( 'loading', 'lazy' );
		}

		// Get the classes.
		$classes = $iframe->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$iframe->setAttribute( 'class', $classes );

		// Remove the src attribute.
		$iframe->removeAttribute( 'src' );

		return $dom;
	}

	/**
	 * Modifies video markup to enable lazy loading.
	 *
	 * @param \DOMNode     $video The video dom node.
	 * @param \DOMDocument $dom   \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_video_markup( $video, $dom ) {
		// Add noscript element.
		$dom = $this->add_noscript_element( $dom, $video );

		// Check if the video has a poster attribute.
		if ( $video->hasAttribute( 'poster' ) ) {
			// Get poster attribute.
			$poster = $video->getAttribute( 'poster' );

			// Remove the poster attribute.
			$video->removeAttribute( 'poster' );

			// Set data-poster value.
			$video->setAttribute( 'data-poster', $poster );
		}

		// Set preload to none.
		$video->setAttribute( 'preload', 'none' );

		// Check for autoplay attribute and change it for lazy loading.
		if ( $video->hasAttribute( 'autoplay' ) ) {
			$video->removeAttribute( 'autoplay' );
			$video->setAttribute( 'data-autoplay', '' );
		}

		// Get the classes.
		$classes = $video->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$video->setAttribute( 'class', $classes );

		return $dom;
	}

	/**
	 * Modifies audio markup to enable lazy loading.
	 *
	 * @param \DOMNode     $audio The audio dom node.
	 * @param \DOMDocument $dom   \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_audio_markup( $audio, $dom ) {
		// Add noscript element.
		$dom = $this->add_noscript_element( $dom, $audio );

		// Set preload to none.
		$audio->setAttribute( 'preload', 'none' );

		// Get the classes.
		$classes = $audio->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$audio->setAttribute( 'class', $classes );

		return $dom;
	}

	/**
	 * Adds noscript element before DOM node.
	 *
	 * @param \DOMDocument     $dom            \DOMDocument() object of the
	 *                                         HTML.
	 * @param \DOMNode         $elem           Single DOM node.
	 *
	 * @return \DOMDocument The updates DOM.
	 */
	public function add_noscript_element( $dom, $elem ) {
		// Create noscript element and add it before the element that gets lazy loading.
		$noscript = $dom->createElement( 'noscript' );
		$noscript_node = $elem->parentNode->insertBefore( $noscript, $elem );

		// Add a copy of the media element to the noscript.
		$noscript_node->appendChild( $elem->cloneNode( true ) );

		return $dom;
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_script() {
		if ( $this->helpers->is_disabled_for_post() ) {
			return;
		}

		// Enqueue lazysizes.
		wp_enqueue_script( 'lazysizes', plugins_url( '/lazy-loading-responsive-images/js/lazysizes.min.js' ), array(), false, true );

		// Check if unveilhooks plugin should be loaded.
		if ( '1' === $this->settings->get_load_unveilhooks_plugin() || '1' === $this->settings->get_enable_for_audios() || '1' === $this->settings->get_enable_for_videos() || '1' === $this->settings->get_enable_for_background_images() ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-unveilhooks', plugins_url( '/lazy-loading-responsive-images/js/ls.unveilhooks.min.js' ), array( 'lazysizes' ), false, true );
		}

		// Check if unveilhooks plugin should be loaded.
		if ( '1' === $this->settings->get_load_aspectratio_plugin() ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-aspectratio', plugins_url( '/lazy-loading-responsive-images/js/ls.aspectratio.min.js' ), array( 'lazysizes' ), false, true );
		}

		// Check if native loading plugin should be loaded.
		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			wp_enqueue_script( 'lazysizes-native-loading', plugins_url( '/lazy-loading-responsive-images/js/ls.native-loading.min.js' ), array( 'lazysizes' ), false, true );
		}

		// Include custom lazysizes config if not empty.
		if ( '' !== $this->settings->get_lazysizes_config() ) {
			wp_add_inline_script( 'lazysizes', $this->settings->get_lazysizes_config(), 'before' );
		}
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 */
	public function add_inline_style() {
		if ( $this->helpers->is_disabled_for_post() ) {
			return;
		}

		// Create loading spinner style if needed.
		$spinner_styles = '';
		$spinner_color  = $this->settings->get_loading_spinner_color();
		$spinner_markup = sprintf(
			'<svg width="44" height="44" xmlns="http://www.w3.org/2000/svg" stroke="%s"><g fill="none" fill-rule="evenodd" stroke-width="2"><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="0s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="0s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="-0.9s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="-0.9s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle></g></svg>',
			$spinner_color
		);
		if ( '1' === $this->settings->get_loading_spinner() ) {
			$spinner_styles = sprintf(
				'.lazyloading {
	color: transparent;
	opacity: 1;
	transition: opacity 300ms;
	transition: opacity var(--lazy-loader-animation-duration);
	background: url("data:image/svg+xml,%s") no-repeat;
	background-size: 2em 2em;
	background-position: center center;
}

.lazyloaded {
	animation-name: loaded;
	animation-duration: 300ms;
	animation-duration: var(--lazy-loader-animation-duration);
	transition: none;
}

@keyframes loaded {
	from {
		opacity: 0;
	}

	to {
		opacity: 1;
	}
}',
				rawurlencode( $spinner_markup )
			);
		}

		// Display the default styles.
		$default_styles = "<style>:root {
			--lazy-loader-animation-duration: 300ms;
		}
		  
		.lazyload {
	display: block;
}

.lazyload,
        .lazyloading {
			opacity: 0;
		}


		.lazyloaded {
			opacity: 1;
			transition: opacity 300ms;
			transition: opacity var(--lazy-loader-animation-duration);
		}$spinner_styles</style>";

		/**
		 * Filter for the default inline style element.
		 *
		 * @param string $default_styles The default styles (including <style> element).
		 */
		echo apply_filters( 'lazy_load_responsive_images_inline_styles', $default_styles );

		// Hide images if no JS.
		echo '<noscript><style>.lazyload { display: none; } .lazyload[class*="lazy-loader-background-element-"] { display: block; opacity: 1; }</style></noscript>';
	}

	/**
	 * Enqueue script to Gutenberg editor view.
	 */
	public function enqueue_block_editor_assets() {
		if ( isset( $_REQUEST['post'] ) && in_array( get_post_type( $_REQUEST['post'] ), $this->settings->get_disable_option_object_types() ) && post_type_supports( get_post_type( $_REQUEST['post'] ), 'custom-fields' ) ) {
			$file_data  = get_file_data( __FILE__, array( 'v' => 'Version' ) );
			$assets_url = trailingslashit( plugin_dir_url( __FILE__ ) );
			wp_enqueue_script( 'lazy-loading-responsive-images-functions', plugins_url( '/lazy-loading-responsive-images/js/functions.js' ), array( 'wp-blocks', 'wp-element', 'wp-edit-post' ), $file_data['v'] );
		}
	}

	/**
	 * Loads the plugin translation.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'lazy-loading-responsive-images' );
	}

	/**
	 * Sets plugin basename.
	 *
	 * @param string $basename The plugin basename.
	 */
	public function set_basename( $basename ) {
		$this->basename = $basename;
	}

	/**
	 * Action on plugin uninstall.
	 */
	public static function uninstall() {
		$options_array = array(
			'lazy_load_responsive_images_disabled_classes',
			'lazy_load_responsive_images_enable_for_iframes',
			'lazy_load_responsive_images_unveilhooks_plugin',
			'lazy_load_responsive_images_enable_for_videos',
			'lazy_load_responsive_images_enable_for_audios',
			'lazy_load_responsive_images_aspectratio_plugin',
			'lazy_load_responsive_images_loading_spinner',
			'lazy_load_responsive_images_loading_spinner_color',
			'lazy_load_responsive_images_granular_disable_option',
			'lazy_load_responsive_images_native_loading_plugin',
			'lazy_load_responsive_images_lazysizes_config',
			'lazy_load_responsive_images_enable_for_background_images',
			'lazy_load_responsive_images_process_complete_markup',
			'lazy_load_responsive_images_additional_filters',
		);

		// Delete options.
		foreach ( $options_array as $option ) {
			delete_option( $option );
		}
	}
}
