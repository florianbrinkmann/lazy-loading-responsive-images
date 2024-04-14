<?php
/**
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

use Masterminds\HTML5;

class Plugin {

	/**
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\Helpers
	 */
	private $helpers;

	/**
	 * @var \FlorianBrinkmann\LazyLoadResponsiveImages\Settings
	 */
	private $settings;

	/**
	 * @var array
	 */
	private $disabled_classes;

	/**
	 * @var string
	 */
	protected $basename;

	/**
	 * @var string
	 */
	protected $js_asset_url;

	/**
	 * @link https://stackoverflow.com/a/13139830
	 *
	 * @var string
	 */
	private $src_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	/**
	 * @var int
	 */
	private $background_image_number = 1;

	/**
	 * @var bool
	 */
	private $generate_noscript;

	public function init() {
		$this->settings = new Settings();

		$this->helpers = new Helpers();

		$this->disabled_classes = $this->settings->get_disabled_classes();

		add_action( 'init', function() {
			/**
			 * Filter to disable the generation of a noscript element.
			 * 
			 * @param bool $generate_noscript Whether to generate a noscript element or not.
			 */
			$this->generate_noscript = (bool) apply_filters( 'lazy_loader_generate_noscript', true );
		}, 5 );

		add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		add_filter( 'plugin_action_links', array(
			$this,
			'plugin_action_links',
		), 10, 2 );

		add_action( 'init', array( $this, 'init_content_processing' ) );
		
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_script',
		), 20 );

		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );

		register_uninstall_hook( $this->basename, 'FlorianBrinkmann\LazyLoadResponsiveImages\Plugin::uninstall' );
	}
	
	public function init_content_processing() {
		if ( '1' === $this->settings->get_process_complete_markup() ) {
			add_action( 'template_redirect', array( $this, 'process_complete_markup' ) );
		} else {
			add_filter( 'the_content', array( $this, 'filter_markup' ), 10001 );

			add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );

			add_filter( 'widget_text', array( $this, 'filter_markup' ) );

			add_filter( 'get_avatar', array( $this, 'filter_markup' ) );

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
	
	public function process_complete_markup() {
		if ( ! $this->helpers->is_post_to_process() ) {
			return;
		}

		ob_start( array( $this, 'filter_markup' ) );
	}

	/**
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
	 * @param string $content HTML.
	 *
	 * @return string Modified HTML.
	 */
	public function filter_markup( $content = '' ) {
		if ( ! $this->helpers->is_post_to_process() ) {
			return $content;
		}

		if ( empty( $content ) ) {
			return $content;
		}

		if ( has_shortcode( $content, 'caption' ) ) {
			return $content;
		}

		libxml_use_internal_errors( true );

		$html5 = new HTML5( array(
            'disable_html_ns' => true,
        ) );

		// Preserve html entities and conditional IE comments.
		// @link https://github.com/ivopetkov/html5-dom-document-php.
		$content = preg_replace( '/&([a-zA-Z]*);/', 'lazy-loading-responsive-images-entity1-$1-end', $content );
		$content = preg_replace( '/&#([0-9]*);/', 'lazy-loading-responsive-images-entity2-$1-end', $content );
		$content = preg_replace( '/<!--\[([\w ]*)\]>/', '<!--[$1]>-->', $content );
		$content = str_replace( '<![endif]-->', '<!--<![endif]-->', $content );

		$dom = $html5->loadHTML( $content );

		$xpath = new \DOMXPath( $dom );

		$nodes = $xpath->query( "//*[not(ancestor-or-self::noscript)][not(ancestor-or-self::*[contains(@class, 'disable-lazyload') or contains(@class, 'skip-lazy') or @data-skip-lazy])]" );

		$is_modified = false;

		foreach ( $nodes as $node ) {
			$node_classes = explode( ' ', $node->getAttribute( 'class' ) );

			$result = array_intersect( $this->disabled_classes, $node_classes );

			$result = array_filter( $result );
			
			if ( ! empty( $result ) || $node->hasAttribute( 'data-no-lazyload' ) || in_array( 'lazyload', $node_classes, true ) ) {
				continue;
			}

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

			if ( 'img' === $node->tagName && 'source' !== $node->parentNode->tagName && 'picture' !== $node->parentNode->tagName ) {
				$dom = $this->modify_img_markup( $node, $dom );
				$is_modified = true;
			}

			if ( 'picture' === $node->tagName ) {
				$dom = $this->modify_picture_markup( $node, $dom );
				$is_modified = true;
			}

			if (
				'input' === $node->tagName
				&& $node->hasAttribute( 'type' )
				&& $node->getAttribute( 'type' ) === 'image'
				&& $node->hasAttribute( 'src' )
			) {
				$dom = $this->modify_input_markup( $node, $dom );
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

		// Restore the entities and conditional comments.
		// @link https://github.com/ivopetkov/html5-dom-document-php/blob/9560a96f63a7cf236aa18b4f2fbd5aab4d756f68/src/HTML5DOMDocument.php#L343.
		if ( strpos( $content, 'lazy-loading-responsive-images-entity') !== false || strpos( $content, '<!--<script' ) !== false ) {
			$content = preg_replace('/lazy-loading-responsive-images-entity1-(.*?)-end/', '&$1;', $content );
			$content = preg_replace('/lazy-loading-responsive-images-entity2-(.*?)-end/', '&#$1;', $content );
			$content = preg_replace( '/<!--\[([\w ]*)\]>-->/', '<!--[$1]>', $content );
			$content = str_replace( '<!--<![endif]-->', '<![endif]-->', $content );
		}

		return $content;
	}

	/**
	 * @param \DOMNode     $node    The node with the inline background image.
	 * @param \DOMDocument $dom     \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_background_img_markup( $node, $dom ) {
		$original_css = $node->getAttribute( 'style' );
		$classes = $node->getAttribute( 'class' );

		if ( 0 !== preg_match_all( '/background(-[a-z]+)?:([^;])*;?/', $node->getAttribute( 'style' ), $matches ) ) {
			$modified_css = str_replace( $matches[0], '', $original_css );
			$node->setAttribute( 'style', $modified_css );

			$background_rules_string = implode( ' ', $matches[0] );

			$unique_class = "lazy-loader-background-element-$this->background_image_number";
			$classes .= " lazyload $unique_class ";
			$node->setAttribute( 'class', $classes );
			$this->background_image_number++;

			$background_style_elem = $dom->createElement( 'style', ".$unique_class.lazyloaded{ $background_rules_string }" );
			$node->parentNode->insertBefore( $background_style_elem, $node );

			if ( ! $this->generate_noscript ) {
				return $dom;
			}

			$noscript = $dom->createElement( 'noscript' );
			$noscript_node = $node->parentNode->insertBefore( $noscript, $node );
			$background_style_elem_noscript = $dom->createElement( 'style', ".$unique_class.lazyload{ $background_rules_string }" );
			$noscript_node->appendChild( $background_style_elem_noscript );
		}

		return $dom;
	}

	/**
	 * @param \DOMNode     $img             The img dom node.
	 * @param \DOMDocument $dom             \DOMDocument() object of the HTML.
	 * @param boolean      $create_noscript Whether to create a noscript element for the img or not.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_img_markup( $img, $dom, $create_noscript = true ) {
		if ( $img->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		if ( true === $create_noscript ) {
			$dom = $this->add_noscript_element( $dom, $img );
		}

		$sizes_attr = '';
		if ( $img->hasAttribute( 'sizes' ) ) {
			$sizes_attr = $img->getAttribute( 'sizes' );

			if ( 'auto' === $sizes_attr ) {
				$img->setAttribute( 'data-sizes', $sizes_attr );

				$img->removeAttribute( 'sizes' );
			}
		}

		if ( $img->hasAttribute( 'srcset' ) ) {
			$srcset = $img->getAttribute( 'srcset' );

			$img->setAttribute( 'data-srcset', $srcset );

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
				$img->removeAttribute( 'srcset' );
			}
		}

		$src = $img->getAttribute( 'src' );

		$img->setAttribute( 'data-src', $src );

		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			$img->setAttribute( 'loading', 'lazy' );
		}

		$classes = $img->getAttribute( 'class' );

		$classes .= ' lazyload';

		$img->setAttribute( 'class', $classes );

		$img_width  = $img->getAttribute( 'width' );
		$img_height = $img->getAttribute( 'height' );

		if ( '' !== $img_width && '' !== $img_height ) {
			$svg_placeholder = "data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20{$img_width}%20{$img_height}%22%3E%3C%2Fsvg%3E";
			$img->setAttribute( 'src', $svg_placeholder );
			if ( $img->hasAttribute( 'srcset' ) ) {
				$img->setAttribute( 'srcset', "$svg_placeholder {$img_width}w" );
			}

			return $dom;
		}
		$img->setAttribute( 'src', $this->src_placeholder );

		return $dom;
	}

	/**
	 * @param \DOMNode     $node            The input dom node.
	 * @param \DOMDocument $dom             \DOMDocument() object of the HTML.
	 * @param boolean      $create_noscript Whether to create a noscript element for the input or not.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_input_markup( $node, $dom, $create_noscript = true ) {
		if ( $node->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		if ( true === $create_noscript ) {
			$dom = $this->add_noscript_element( $dom, $node );
		}

		$src = $node->getAttribute( 'src' );

		$node->setAttribute( 'data-src', $src );

		$classes = $node->getAttribute( 'class' );

		$classes .= ' lazyload';

		$node->setAttribute( 'class', $classes );

		$node_width  = $node->getAttribute( 'width' );
		$node_height = $node->getAttribute( 'height' );

		if ( '' !== $node_width && '' !== $node_height ) {
			$svg_placeholder = "data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20{$node_width}%20{$node_height}%22%3E%3C%2Fsvg%3E";
			$node->setAttribute( 'src', $svg_placeholder );
			if ( $node->hasAttribute( 'srcset' ) ) {
				$node->setAttribute( 'srcset', "$svg_placeholder {$node_width}w" );
			}

			return $dom;
		}
		$node->setAttribute( 'src', $this->src_placeholder );

		return $dom;
	}

	/**
	 * @param \DOMNode     $picture The source dom node.
	 * @param \DOMDocument $dom     \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_picture_markup( $picture, $dom ) {
		$img_element = $picture->getElementsByTagName( 'img' );

		foreach ( $img_element as $img ) {
			if ( in_array( 'lazyload', explode( ' ', $img->getAttribute( 'class' ) ) ) ) {
				return $dom;
			}
		}
		
		$dom = $this->add_noscript_element( $dom, $picture );

		$source_elements = $picture->getElementsByTagName( 'source' );

		if ( 0 !== $source_elements->length ) {
			foreach ( $source_elements as $source_element ) {
				$sizes_attr = '';
				if ( $source_element->hasAttribute( 'sizes' ) ) {
					$sizes_attr = $source_element->getAttribute( 'sizes' );

					if ( 'auto' === $sizes_attr ) {
						$source_element->setAttribute( 'data-sizes', $sizes_attr );

						$source_element->removeAttribute( 'sizes' );
					}
				}

				if ( $source_element->hasAttribute( 'srcset' ) ) {
					$srcset = $source_element->getAttribute( 'srcset' );

					$source_element->setAttribute( 'data-srcset', $srcset );

					if ( '' !== $sizes_attr ) {
						$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
						if ( \is_numeric ( $width ) ) {
							$source_element->setAttribute( 'srcset', "$this->src_placeholder {$width}w" );
						} else {
							$source_element->removeAttribute( 'srcset' );
						}
					} else {
						$source_element->removeAttribute( 'srcset' );
					}
				}

				if ( $source_element->hasAttribute( 'src' ) ) {
					$src = $source_element->getAttribute( 'src' );

					$source_element->setAttribute( 'data-src', $src );

					$source_element->setAttribute( 'src', $this->src_placeholder );
				}
			}
		}

		foreach ( $img_element as $img ) {
			$this->modify_img_markup( $img, $dom, false );
		}

		return $dom;
	}

	/**
	 * @param \DOMNode     $iframe The iframe dom node.
	 * @param \DOMDocument $dom    \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_iframe_markup( $iframe, $dom ) {
		$dom = $this->add_noscript_element( $dom, $iframe );

		if ( $iframe->hasAttribute( 'src' ) ) {
			$src = $iframe->getAttribute( 'src' );

			$iframe->setAttribute( 'data-src', $src );
		} else {
			return $dom;
		}

		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			$iframe->setAttribute( 'loading', 'lazy' );
		}

		$classes = $iframe->getAttribute( 'class' );

		$classes .= ' lazyload';

		$iframe->setAttribute( 'class', $classes );

		$iframe->removeAttribute( 'src' );

		return $dom;
	}

	/**
	 * @param \DOMNode     $video The video dom node.
	 * @param \DOMDocument $dom   \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_video_markup( $video, $dom ) {
		$dom = $this->add_noscript_element( $dom, $video );

		if ( $video->hasAttribute( 'poster' ) ) {
			$poster = $video->getAttribute( 'poster' );

			$video->removeAttribute( 'poster' );

			$video->setAttribute( 'data-poster', $poster );
		}

		$video->setAttribute( 'preload', 'none' );

		if ( $video->hasAttribute( 'autoplay' ) ) {
			$video->removeAttribute( 'autoplay' );
			$video->setAttribute( 'data-autoplay', '' );
		}

		$classes = $video->getAttribute( 'class' );

		$classes .= ' lazyload';

		$video->setAttribute( 'class', $classes );

		return $dom;
	}

	/**
	 * @param \DOMNode     $audio The audio dom node.
	 * @param \DOMDocument $dom   \DOMDocument() object of the HTML.
	 *
	 * @return \DOMDocument The updated DOM.
	 */
	public function modify_audio_markup( $audio, $dom ) {
		$dom = $this->add_noscript_element( $dom, $audio );

		$audio->setAttribute( 'preload', 'none' );

		$classes = $audio->getAttribute( 'class' );

		$classes .= ' lazyload';

		$audio->setAttribute( 'class', $classes );

		return $dom;
	}

	/**
	 * @param \DOMDocument     $dom            \DOMDocument() object of the
	 *                                         HTML.
	 * @param \DOMNode         $elem           Single DOM node.
	 *
	 * @return \DOMDocument The updates DOM.
	 */
	public function add_noscript_element( $dom, $elem ) {
		if ( ! $this->generate_noscript ) {
			return $dom;
		}

		$noscript = $dom->createElement( 'noscript' );
		$noscript_node = $elem->parentNode->insertBefore( $noscript, $elem );

		$noscript_media_fallback_elem = $elem->cloneNode( true );

		/**
		 * Array of HTML attributes that should be stripped from the fallback element in noscript.
		 * 
		 * @param array Array of elements to strip from fallback.
		 */
		$attrs_to_strip_from_fallback = (array) apply_filters( 'lazy_loader_attrs_to_strip_from_fallback_elem', [] );

		foreach ( $attrs_to_strip_from_fallback as $attr_to_strip ) {
			$noscript_media_fallback_elem->removeAttribute( $attr_to_strip );
		}

		$noscript_node->appendChild( $noscript_media_fallback_elem );

		return $dom;
	}

	/**
	 * @param array  $allowedposttags Allowed post tags.
	 * @param string $context         Context.
	 *
	 * @return array
	 */
	public function wp_kses_allowed_html( $allowedposttags, $context ) {
		if ( 'post' !== $context ) {
			return $allowedposttags;
		}

		$allowedposttags['noscript'] = [];

		return $allowedposttags;
	}
	
	public function enqueue_script() {
		if ( $this->helpers->is_disabled_for_post() ) {
			return;
		}

		// Check if something (like Avada) already included a lazysizes script. If that is the case, deregister it.
		$lazysizes = wp_script_is( 'lazysizes' );

		if ( $lazysizes ) {
			wp_deregister_script( 'lazysizes' );
		}

		wp_enqueue_script( 'lazysizes', plugins_url( '/lazy-loading-responsive-images/js/lazysizes.min.js' ), array(), filemtime( plugin_dir_path( __FILE__ ) . '../js/lazysizes.min.js' ), true );

		if ( '1' === $this->settings->get_load_unveilhooks_plugin() || '1' === $this->settings->get_enable_for_audios() || '1' === $this->settings->get_enable_for_videos() || '1' === $this->settings->get_enable_for_background_images() ) {
			wp_enqueue_script( 'lazysizes-unveilhooks', plugins_url( '/lazy-loading-responsive-images/js/ls.unveilhooks.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.unveilhooks.min.js' ), true );
		}

		if ( '1' === $this->settings->get_load_native_loading_plugin() ) {
			wp_enqueue_script( 'lazysizes-native-loading', plugins_url( '/lazy-loading-responsive-images/js/ls.native-loading.min.js' ), array( 'lazysizes' ), filemtime( plugin_dir_path( __FILE__ ) . '../js/ls.native-loading.min.js' ), true );
		}

		if ( '' !== $this->settings->get_lazysizes_config() ) {
			wp_add_inline_script( 'lazysizes', $this->settings->get_lazysizes_config(), 'before' );
		}
	}
	
	public function add_inline_style() {
		if ( $this->helpers->is_disabled_for_post() ) {
			return;
		}

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

		echo '<noscript><style>.lazyload { display: none; } .lazyload[class*="lazy-loader-background-element-"] { display: block; opacity: 1; }</style></noscript>';
	}
	
	public function enqueue_block_editor_assets() {
		if ( isset( $_REQUEST['post'] ) && in_array( get_post_type( $_REQUEST['post'] ), $this->settings->get_disable_option_object_types() ) && post_type_supports( get_post_type( $_REQUEST['post'] ), 'custom-fields' ) ) {
			$script_asset_file = require( __DIR__ . '/../js/build/functions.asset.php' );
			wp_enqueue_script(
				'lazy-loading-responsive-images-functions',
				$this->js_asset_url,
				$script_asset_file['dependencies'],
				$script_asset_file['version']
			);
		}
	}
	
	public function load_translation() {
		load_plugin_textdomain( 'lazy-loading-responsive-images' );
	}

	/**
	 * @param string $basename The plugin basename.
	 */
	public function set_basename( $basename ) {
		$this->basename = $basename;
	}

	/**
	 * @param string $basename The plugin basename.
	 */
	public function set_js_asset_url( $js_asset_url ) {
		$this->js_asset_url = $js_asset_url;
	}
	
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

		foreach ( $options_array as $option ) {
			delete_option( $option );
		}
	}
}
