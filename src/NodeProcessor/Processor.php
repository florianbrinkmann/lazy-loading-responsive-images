<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Interface Processor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
interface Processor {
	/**
	 * @param DOMNode $node The DOM node to process.
	 * @param DOMDocument $dom The DOM document object the node is part of.
	 * @param array $config Config array.
	 *
	 * @return DOMDocument Modified document.
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument;
}