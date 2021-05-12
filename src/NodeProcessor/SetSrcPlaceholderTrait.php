<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMNode;

/**
 * Trait SetSrcPlaceholderTrait
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
trait SetSrcPlaceholderTrait {
	/**
	 * Placeholder data uri for img src attributes.
	 *
	 * @link https://stackoverflow.com/a/13139830
	 *
	 * @var string
	 */
	protected static $src_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	protected static function set_src_placeholder( DOMNode $node ): DOMNode {
		// Get width and height.
		$node_width  = $node->getAttribute( 'width' );
		$node_height = $node->getAttribute( 'height' );

		// Set data URI for src attribute.
		if ( '' !== $node_width && '' !== $node_height ) {
			// We have image width and height, we can set a inline SVG to prevent content jumps.
			$svg_placeholder = "data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20{$node_width}%20{$node_height}%22%3E%3C%2Fsvg%3E";
			$node->setAttribute( 'src', $svg_placeholder );
			if ( $node->hasAttribute( 'srcset' ) ) {
				$node->setAttribute( 'srcset', "$svg_placeholder {$node_width}w" );
			}

			return $node;
		}

		$node->setAttribute( 'src', self::$src_placeholder );

		return $node;
	}
}