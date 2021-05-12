<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

use const FlorianBrinkmann\LazyLoadResponsiveImages\ATTRS_TO_STRIP_FROM_FALLBACK_ELEM;

/**
 * Trait ProcessorMisc
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
trait AddNoscriptElementTrait {
	/**
	 * Adds noscript element before DOM node.
	 *
	 * @param DOMDocument $dom \DOMDocument() object of the
	 *                                         HTML.
	 * @param DOMNode $elem Single DOM node.
	 * @param array $config Config array.
	 *
	 * @return DOMDocument The updates DOM.
	 */
	private static function add_noscript_element( DOMDocument $dom, DOMNode $elem, array $config ): DOMDocument {
		// Create noscript element and add it before the element that gets lazy loading.
		$noscript = $dom->createElement( 'noscript' );
		$noscript_node = $elem->parentNode->insertBefore( $noscript, $elem );

		// Create copy of media element.
		$noscript_media_fallback_elem = $elem->cloneNode( true );

		$attrs_to_strip_from_fallback = (array) $config[ATTRS_TO_STRIP_FROM_FALLBACK_ELEM] ?? [];

		foreach ( $attrs_to_strip_from_fallback as $attr_to_strip ) {
			$noscript_media_fallback_elem->removeAttribute( $attr_to_strip );
		}

		// Add a copy of the media element to the noscript.
		$noscript_node->appendChild( $noscript_media_fallback_elem );

		return $dom;
	}
}