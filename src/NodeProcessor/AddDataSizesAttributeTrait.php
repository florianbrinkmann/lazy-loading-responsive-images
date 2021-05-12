<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMNode;

trait AddDataSizesAttributeTrait {
	/**
	 * Add data sizes attr to DOMNode.
	 *
	 * @param DOMNode $node The DOMNode.
	 * @param string $sizes_attr Content of sizes attribute.
	 *
	 * @return DOMNode
	 */
	private static function add_data_sizes_attr( DOMNode $node, string $sizes_attr ): DOMNode {
		if ( $sizes_attr === '' ) {
			return $node;
		}

		// Check if the value is auto. If so, we modify it to data-sizes.
		if ( 'auto' === $sizes_attr ) {
			// Set data-sizes attribute.
			$node->setAttribute( 'data-sizes', $sizes_attr );

			// Remove sizes attribute.
			$node->removeAttribute( 'sizes' );
		}

		return $node;
	}
}