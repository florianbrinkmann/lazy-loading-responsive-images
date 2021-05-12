<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Class AudioProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class AudioProcessor implements Processor {
	use AddNoscriptElementTrait;
	use AddLazyloadClassTrait;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Add noscript element.
		$dom = self::add_noscript_element( $dom, $node, $config );

		// Set preload to none.
		$node->setAttribute( 'preload', 'none' );

		$node = self::add_lazyload_class( $node );

		return $dom;
	}
}