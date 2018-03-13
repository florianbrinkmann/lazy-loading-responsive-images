<?php
/**
 * Main plugin code.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use FlorianBrinkmann\LazyLoadResponsiveImages\Helpers as Helpers;

use FlorianBrinkmann\LazyLoadResponsiveImages\Settings as Settings;

use archon810\SmartDOMDocument as SmartDOMDocument;

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
	public $basename;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		// Init customizer settings.
		$this->settings = new Settings();

		// Set helpers.
		$this->helpers = new Helpers();

		// Get the disabled classes and save in property.
		$this->disabled_classes = $this->settings->disabled_classes;
	}

	/**
	 * Runs the filters and actions.
	 */
	public function init() {
		// Add link to settings in the plugin list.
		add_filter( 'plugin_action_links', array(
			$this,
			'plugin_action_links',
		), 10, 2 );

		// Filter markup of the_content() calls to modify media markup for lazy loading.
		add_filter( 'the_content', array( $this, 'filter_markup' ), 500 );

		// Filter markup of Text widget to modify media markup for lazy loading.
		add_filter( 'widget_text', array( $this, 'filter_markup' ) );

		// Filter markup of gravatars to modify markup for lazy loading.
		add_filter( 'get_avatar', array( $this, 'filter_markup' ) );

		// Adds lazyload markup and noscript element to post thumbnail.
		add_filter( 'post_thumbnail_html', array(
			$this,
			'filter_markup',
		), 10, 1 );

		// Enqueues scripts and styles.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_script',
		), 20 );

		// Adds inline style.
		add_action( 'wp_head', array( $this, 'add_inline_style' ) );

		// Load the language files.
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );

		// Action on uninstall.
		register_uninstall_hook( __FILE__, array(
			'FlorianBrinkmann\LazyLoadResponsiveImages\Plugin',
			'uninstall',
		) );
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
		// Check if we have no content.
		if ( empty( $content ) ) {
			return $content;
		} // End if().

		// Check if we are on a feed page.
		if ( is_feed() ) {
			return $content;
		} // End if().

		// Check if this is a request in the backend.
		if ( $this->helpers->is_admin_request() ) {
			return $content;
		} // End if().

		// Check for AMP page.
		if ( true === $this->helpers->is_amp_page() ) {
			return $content;
		} // End if().

		// Create new SmartDomDocument object.
		$dom = new SmartDOMDocument();

		// Load the HTML.
		$dom->loadHTML( $content );

		$xpath = new \DOMXPath( $dom );

		// Get all nodes except the ones that live inside a noscript element.
		// @link https://stackoverflow.com/a/19348287/7774451.
		$nodes = $xpath->query( '//*[not(ancestor-or-self::noscript)]' );

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
			} // End if().

			// Check if it is one of the supported elements and support for it is enabled.
			if ( 'img' === $node->tagName ) {
				$dom = $this->modify_img_markup( $node, $dom );
			} // End if().

			if ( '1' === $this->settings->enable_for_iframes && 'iframe' === $node->tagName ) {
				$dom = $this->modify_iframe_markup( $node, $dom );
			} // End if().

			if ( '1' === $this->settings->enable_for_videos && 'video' === $node->tagName ) {
				$dom = $this->modify_video_markup( $node, $dom );
			} // End if().

			if ( '1' === $this->settings->enable_for_audios && 'audio' === $node->tagName ) {
				$dom = $this->modify_audio_markup( $node, $dom );
			} // End if().
		} // End foreach().

		$content = $dom->saveHTMLExact();

		return $content;
	}

	/**
	 * Modifies img markup to enable lazy loading.
	 *
	 * @param \DOMNode         $img The img dom node.
	 * @param SmartDomDocument $dom SmartDomDocument() object of the HTML.
	 *
	 * @return SmartDomDocument The updated DOM.
	 */
	public function modify_img_markup( $img, $dom ) {
		// Save the image original attributes.
		$img_attributes = $img->attributes;

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
			} // End if().

			// Set data-src value.
			$img->setAttribute( 'data-src', $src );
		} else {
			// Get src attribute.
			$src = $img->getAttribute( 'src' );

			// Check if we do not have a value.
			if ( '' === $src ) {
				// Set the value from data-noscript as src.
				$src = $img->getAttribute( 'data-noscript' );
			} // End if().

			// Set data-src value.
			$img->setAttribute( 'data-src', $src );
		} // End if().

		if ( '1' === $this->settings->load_aspectratio_plugin ) {
			// Get width and height.
			$img_width  = $img->getAttribute( 'width' );
			$img_height = $img->getAttribute( 'height' );

			if ( '' !== $img_width && '' !== $img_height ) {
				$img->setAttribute( 'data-aspectratio', "$img_width/$img_height" );
			} // End if().
		} // End if().

		// Get the classes.
		$classes = $img->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$img->setAttribute( 'class', $classes );

		// Remove the src attribute.
		$img->removeAttribute( 'src' );

		// Add noscript element.
		$dom = $this->add_noscript_element( $img_attributes, $dom, $img, 'IMG', $classes, $src );

		// Save the content.
		$dom->saveHTMLExact();

		return $dom;
	}

	/**
	 * Modifies iframe markup to enable lazy loading.
	 *
	 * @param \DOMNode         $iframe The iframe dom node.
	 * @param SmartDomDocument $dom    SmartDomDocument() object of the HTML.
	 *
	 * @return SmartDomDocument The updated DOM.
	 */
	public function modify_iframe_markup( $iframe, $dom ) {
		// Save the iframe original attributes.
		$iframe_attributes = $iframe->attributes;

		// Check if the iframe has a src attribute.
		if ( $iframe->hasAttribute( 'src' ) ) {
			// Get src attribute.
			$src = $iframe->getAttribute( 'src' );

			// Set data-src value.
			$iframe->setAttribute( 'data-src', $src );
		} else {
			return $dom;
		} // End if().

		// Get the classes.
		$classes = $iframe->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$iframe->setAttribute( 'class', $classes );

		// Remove the src attribute.
		$iframe->removeAttribute( 'src' );

		// Add noscript element.
		$dom = $this->add_noscript_element( $iframe_attributes, $dom, $iframe, 'IFRAME', $classes, $src );

		// Save the content.
		$dom->saveHTMLExact();

		return $dom;
	}

	/**
	 * Modifies video markup to enable lazy loading.
	 *
	 * @param \DOMNode         $video The video dom node.
	 * @param SmartDomDocument $dom   SmartDomDocument() object of the HTML.
	 *
	 * @return SmartDomDocument The updated DOM.
	 */
	public function modify_video_markup( $video, $dom ) {
		// Save the original attributes.
		$video_attributes = $video->attributes;

		// Check if the video has a poster attribute.
		if ( $video->hasAttribute( 'poster' ) ) {
			// Get poster attribute.
			$poster = $video->getAttribute( 'poster' );

			// Remove the poster attribute.
			$video->removeAttribute( 'poster' );

			// Set data-poster value.
			$video->setAttribute( 'data-poster', $poster );
		} // End if().

		// Check if the video has a src attribute.
		if ( $video->hasAttribute( 'src' ) ) {
			// Get src attribute.
			$src = $video->getAttribute( 'src' );

			// Remove the src attribute.
			$video->removeAttribute( 'src' );

			// Set data-src value.
			$video->setAttribute( 'data-src', $src );
		} // End if().

		// Set preload to none.
		$video->setAttribute( 'preload', 'none' );

		// Get the classes.
		$classes = $video->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$video->setAttribute( 'class', $classes );

		// Add noscript element.
		$dom = $this->add_noscript_element( $video_attributes, $dom, $video, 'VIDEO', $classes, $src );

		// Save the content.
		$dom->saveHTMLExact();

		return $dom;
	}

	/**
	 * Modifies audio markup to enable lazy loading.
	 *
	 * @param \DOMNode         $audio The audio dom node.
	 * @param SmartDomDocument $dom   SmartDomDocument() object of the HTML.
	 *
	 * @return SmartDomDocument The updated DOM.
	 */
	public function modify_audio_markup( $audio, $dom ) {
		// Save the original attributes.
		$audio_attributes = $audio->attributes;

		// Check if the audio has a src attribute.
		if ( $audio->hasAttribute( 'src' ) ) {
			// Get src attribute.
			$src = $audio->getAttribute( 'src' );

			// Remove the src attribute.
			$audio->removeAttribute( 'src' );

			// Set data-src value.
			$audio->setAttribute( 'data-src', $src );
		} // End if().

		// Set preload to none.
		$audio->setAttribute( 'preload', 'none' );

		// Get the classes.
		$classes = $audio->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$audio->setAttribute( 'class', $classes );

		// Add noscript element.
		$dom = $this->add_noscript_element( $audio_attributes, $dom, $audio, 'AUDIO', $classes, $src );

		// Save the content.
		$dom->saveHTMLExact();

		return $dom;
	}

	/**
	 * Adds noscript element before DOM node.
	 *
	 * @param \DOMNamedNodeMap $orig_elem_attr Object of the original element’s
	 *                                         attributes.
	 * @param SmartDomDocument $dom            SmartDomDocument() object of the
	 *                                         HTML.
	 * @param \DOMNode         $elem           Single DOM node.
	 * @param string           $tag_name       Tag name which needs to be
	 *                                         created inside the noscript
	 *                                         element.
	 * @param string           $classes        String of the element’s classes.
	 * @param string           $src            Value of the src attribute.
	 *
	 * @return SmartDomDocument The updates DOM.
	 */
	public function add_noscript_element( $orig_elem_attr, $dom, $elem, $tag_name, $classes, $src ) {
		$noscript = $dom->createElement( 'noscript' );

		// Insert it before the img node.
		$noscript_node = $elem->parentNode->insertBefore( $noscript, $elem );

		// Create element.
		$media_element = $dom->createElement( $tag_name );

		// Switch lazyload class against noscript-image.
		$classes = str_replace( 'lazyload', '', $classes );

		// Set class value.
		$media_element->setAttribute( 'class', $classes );

		// Add the other attributes of the original element.
		foreach ( $orig_elem_attr as $attr ) {
			// Save name and value.
			$name  = $attr->nodeName;
			$value = $attr->nodeValue;

			// Check if it is class attribute and continue.
			if ( 'class' === $name ) {
				continue;
			} // End if().

			// Set attribute to noscript image.
			$media_element->setAttribute( $name, $value );
		} // End foreach().

		// Add img node to noscript node.
		$new_iframe = $noscript_node->appendChild( $media_element );

		// Set src value.
		$new_iframe->setAttribute( 'src', $src );

		return $dom;
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_script() {
		// Enqueue lazysizes.
		wp_enqueue_script( 'lazysizes', plugins_url() . '/lazy-loading-responsive-images/js/lazysizes.min.js', '', false, true );

		// Check if unveilhooks plugin should be loaded.
		if ( '1' === $this->settings->load_unveilhooks_plugin || '1' === $this->settings->enable_for_audios || '1' === $this->settings->enable_for_videos ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-unveilhooks', plugins_url() . '/lazy-loading-responsive-images/js/ls.unveilhooks.min.js', 'lazysizes', false, true );
		} // End if().

		// Check if unveilhooks plugin should be loaded.
		if ( '1' === $this->settings->load_aspectratio_plugin ) {
			// Enqueue unveilhooks plugin.
			wp_enqueue_script( 'lazysizes-aspectratio', plugins_url() . '/lazy-loading-responsive-images/js/ls.aspectratio.min.js', 'lazysizes', false, true );
		} // End if().
	}

	/**
	 * Adds inline style.
	 *
	 * We do not enqueue a new CSS file for two rules, but cannot use
	 * wp_add_inline_style() because we have no handle. So we need to
	 * echo it.
	 */
	public function add_inline_style() {
		// Create loading spinner style if needed.
		$spinner_styles = '';
		$spinner_color  = $this->settings->loading_spinner_color;
		$spinner_markup = sprintf(
			'<svg width="44" height="44" xmlns="http://www.w3.org/2000/svg" stroke="%s"><g fill="none" fill-rule="evenodd" stroke-width="2"><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="0s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="0s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle><circle cx="22" cy="22" r="1"><animate attributeName="r" begin="-0.9s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/><animate attributeName="stroke-opacity" begin="-0.9s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/></circle></g></svg>',
			$spinner_color
		);
		if ( '1' === $this->settings->loading_spinner ) {
			$spinner_styles = sprintf(
				'.lazyloading {
  color: transparent;
  opacity: 1;
  transition: opacity 300ms;
  background: url("data:image/svg+xml,%s") no-repeat;
  background-size: 2em 2em;
  background-position: center center;
}

.lazyloaded {
  transition: none;
}',
				rawurlencode( $spinner_markup )
			);
		} // End if().

		// Display the default styles.
		$default_styles = "<style>.lazyload {
	display: block;
}

.lazyload,
        .lazyloading {
			opacity: 0;
		}
		
		
		.lazyloaded {
			opacity: 1;
			transition: opacity 300ms;
		}$spinner_styles</style>";

		/**
		 * Filter for the default inline style element.
		 *
		 * @param string $default_styles The default styles (including <style> element).
		 */
		echo apply_filters( 'lazy_load_responsive_images_inline_styles', $default_styles );

		// Hide images if no JS.
		echo '<noscript><style>.lazyload { display: none; }</style></noscript>';
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
		// Delete options.
		foreach ( $this->settings->options as $option_id => $option ) {
			delete_option( $option_id );
		}
	}
}
