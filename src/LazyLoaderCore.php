<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages;


use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;
use Exception;
use FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor\NullProcessor;
use FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor\ProcessorFactory;

/**
 * Class LazyLoaderCore
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class LazyLoaderCore {
	use ConfigTrait;

	/**
	 * LazyLoaderCore constructor.
	 *
	 * @param ConfigInterface $config
	 */
	public function __construct( ConfigInterface $config ) {
		$this->processConfig( $config );
	}

	/**
	 * Run LazyLoaderCore.
	 *
	 * @param string $markup HTML markup string.
	 *
	 * @return string
	 */
	public function run( string $markup ): string {
		$markup_processor = new MarkupProcessor();

		$dom = $markup_processor->parse_markup( $markup );

		$nodes = $markup_processor->get_dom_nodes( $dom );

		foreach ( $nodes as $node ) {
			// Check if it is an element that should not be lazy loaded.
			// Get the classes as an array.
			$node_classes = explode( ' ', $node->getAttribute( 'class' ) );

			// Check for intersection with array of classes, which should
			// not be lazy loaded.
			$result = array_intersect( explode( ',', $this->getConfigKey( DISABLED_CLASSES ) ), $node_classes );

			// Filter empty values.
			$result = array_filter( $result );

			if ( ! empty( $result ) ) {
				// The element has a class that should be skipped.
				continue;
			}

			if ( $node->hasAttribute( 'data-no-lazyload' ) ) {
				continue;
			}

			if ( in_array( 'lazyload', $node_classes, true ) ) {
				// Element already has a lazyload class.
				continue;
			}

			try {
				$processor = ProcessorFactory::get_content_processor( $node, $this->getConfigArray() );
			} catch ( Exception $e ) {
				continue;
			}

			if ( $processor instanceof NullProcessor ) {
				continue;
			}

			$dom = $processor::process( $node, $dom, $this->getConfigArray() );

			$markup_processor->content_is_modified();
		}

		return $markup_processor->get_html_string( $dom );
	}
}