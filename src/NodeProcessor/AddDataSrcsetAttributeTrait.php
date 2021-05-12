<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMNode;

/**
 * Trait AddDataSrcsetAttributeTrait
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor
 */
trait AddDataSrcsetAttributeTrait {
	/**
	 * Add data sizes attr to DOMNode.
	 *
	 * @param DOMNode $node The DOMNode.
	 * @param string $sizes_attr Content of sizes attribute.
	 *
	 * @return DOMNode
	 */
	private static function add_data_srcset_attr( DOMNode $node, string $sizes_attr ): DOMNode {
		if ( ! $node->hasAttribute( 'srcset' ) ) {
			return $node;
		}

		// Get srcset value.
		$srcset = $node->getAttribute( 'srcset' );

		// Set data-srcset attribute.
		$node->setAttribute( 'data-srcset', $srcset );

		// Set srcset attribute with src placeholder to produce valid markup.
		$node = self::maybe_set_srcset_attr( $node, $sizes_attr );

		return $node;
	}

	/**
	 * Set srcset attribute for node to get valid markup.
	 *
	 * @param DOMNode $node
	 * @param string $sizes_attr
	 *
	 * @return DOMNode
	 */
	private static function maybe_set_srcset_attr( DOMNode $node, string $sizes_attr ): DOMNode {
		$node_width = $node->getAttribute( 'width' );
		if ( '' !== $node_width ) {
			$node->setAttribute( 'srcset', self::$src_placeholder . " {$node_width}w" );
			return $node;
		}

		if ( '' !== $sizes_attr ) {
			$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
			if ( \is_numeric( $width ) ) {
				$node->setAttribute( 'srcset', self::$src_placeholder . " {$width}w" );

				return $node;
			}

			$node->removeAttribute( 'srcset' );
			return $node;
		}

		// Remove srcset attribute.
		$node->removeAttribute( 'srcset' );
		return $node;
	}
}