<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

use const FlorianBrinkmann\LazyLoadResponsiveImages\NATIVE_LAZY_LOAD;

/**
 * Class ImgProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class ImgProcessor implements Processor {
	use AddNoscriptElementTrait;
	use AddLazyloadClassTrait;
	use SetSrcPlaceholderTrait;
	use AddDataSizesAttributeTrait;
	use AddDataSrcsetAttributeTrait;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Check if the element already has a data-src attribute (might be the case for
		// plugins that bring their own lazy load functionality) and skip it to prevent conflicts.
		if ( $node->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		// Add noscript element when not child of picture element.
		if ( $node->parentNode === null || $node->parentNode->nodeName !== 'picture' ) {
			$dom = self::add_noscript_element( $dom, $node, $config );
		}

		$sizes_attr = $node->getAttribute( 'sizes' );

		$node = self::add_data_sizes_attr( $node, $sizes_attr );

		$node = self::add_data_srcset_attr( $node, $sizes_attr );

		// Get src value.
		$src = $node->getAttribute( 'src' );

		// Set data-src value.
		$node->setAttribute( 'data-src', $src );

		if ( isset( $config[NATIVE_LAZY_LOAD] ) && true === $config[NATIVE_LAZY_LOAD] ) {
			$node->setAttribute( 'loading', 'lazy' );
		}

		$node = self::add_lazyload_class( $node );

		$node = self::set_src_placeholder( $node );

		return $dom;
	}
}