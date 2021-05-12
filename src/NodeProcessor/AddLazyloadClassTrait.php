<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMNode;

/**
 * Trait AddLazyloadClassTrait
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
trait AddLazyloadClassTrait {
	/**
	 * Add lazyload class to DOMNode.
	 *
	 * @param DOMNode $node The DOMNode.
	 *
	 * @return DOMNode
	 */
	private static function add_lazyload_class( DOMNode $node ): DOMNode {
		// Get the classes.
		$classes = $node->getAttribute( 'class' );

		// Add lazyload class.
		$classes .= ' lazyload';

		// Set the class string.
		$node->setAttribute( 'class', $classes );

		return $node;
	}
}