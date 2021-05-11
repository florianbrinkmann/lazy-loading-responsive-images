<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Class VideoProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class VideoProcessor implements Processor {
	use AddNoscriptElement;
	use AddLazyloadClass;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Add noscript element.
		$dom = self::add_noscript_element( $dom, $node, $config );

		// Check if the video has a poster attribute.
		if ( $node->hasAttribute( 'poster' ) ) {
			// Get poster attribute.
			$poster = $node->getAttribute( 'poster' );

			// Remove the poster attribute.
			$node->removeAttribute( 'poster' );

			// Set data-poster value.
			$node->setAttribute( 'data-poster', $poster );
		}

		// Set preload to none.
		$node->setAttribute( 'preload', 'none' );

		// Check for autoplay attribute and change it for lazy loading.
		if ( $node->hasAttribute( 'autoplay' ) ) {
			$node->removeAttribute( 'autoplay' );
			$node->setAttribute( 'data-autoplay', '' );
		}

		$node = self::add_lazyload_class( $node );

		return $dom;
	}
}