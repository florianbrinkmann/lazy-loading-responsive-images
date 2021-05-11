<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Class InputProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class InputProcessor implements Processor {
	use AddLazyloadClass;
	use SetSrcPlaceholder;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Check if the element already has a data-src attribute (might be the case for
		// plugins that bring their own lazy load functionality) and skip it to prevent conflicts.
		if ( $node->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		// Get src value.
		$src = $node->getAttribute( 'src' );

		// Set data-src value.
		$node->setAttribute( 'data-src', $src );

		$node = self::add_lazyload_class( $node );

		$node = self::set_src_placeholder( $node );

		return $dom;
	}
}