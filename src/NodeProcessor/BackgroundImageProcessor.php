<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Class BackgroundImageProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class BackgroundImageProcessor implements Processor {
	/**
	 * Counter for background image classes.
	 */
	private static $background_image_number = 1;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		$original_css = $node->getAttribute( 'style' );
		$classes = $node->getAttribute( 'class' );
		// It is possible that there are multiple background rules for a inline element (including ones for size, position, et cetera).
		// We will insert all of them to the inline style element, to not mess up their order.
		if ( 0 !== preg_match_all( '/background(-[a-z]+)?:([^;])*;?/', $node->getAttribute( 'style' ), $matches ) ) {
			// $matches[0] contains the full CSS background rules.
			// We remove the rules from the inline style.
			$modified_css = str_replace( $matches[0], '', $original_css );
			$node->setAttribute( 'style', $modified_css );

			// Build string of background rules.
			$background_rules_string = implode( ' ', $matches[0] );

			// Add unique class and lazyload class to element.
			$unique_class = 'lazy-loader-background-element-' . self::$background_image_number;
			$classes .= " lazyload $unique_class ";
			$node->setAttribute( 'class', $classes );
			self::$background_image_number++;

			// Create style element with the background rule.
			$background_style_elem = $dom->createElement( 'style', ".$unique_class.lazyloaded{ $background_rules_string }" );
			$node->parentNode->insertBefore( $background_style_elem, $node );

			// Add the noscript element.
			$noscript = $dom->createElement( 'noscript' );

			// Insert it before the node.
			$noscript_node = $node->parentNode->insertBefore( $noscript, $node );

			// Create element.
			$background_style_elem_noscript = $dom->createElement( 'style', ".$unique_class.lazyload{ $background_rules_string }" );

			// Add media node to noscript node.
			$noscript_node->appendChild( $background_style_elem_noscript );
		}
		return $dom;
	}
}