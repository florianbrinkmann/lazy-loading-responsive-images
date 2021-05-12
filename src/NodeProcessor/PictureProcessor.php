<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

/**
 * Class PictureProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class PictureProcessor implements Processor {
	use AddNoscriptElementTrait;
	use SetSrcPlaceholderTrait;
	use AddDataSizesAttributeTrait;
	use AddDataSrcsetAttributeTrait;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Check if img element already has `layzload` class.
		$img_element = $node->getElementsByTagName( 'img' );

		foreach ( $img_element as $img ) {
			if ( in_array( 'lazyload', explode( ' ', $img->getAttribute( 'class' ) ) ) ) {
				return $dom;
			}
		}

		// Add noscript element.
		$dom = self::add_noscript_element( $dom, $node, $config );

		// Get source elements from picture.
		$source_elements = $node->getElementsByTagName( 'source' );

		// Loop the source elements if there are some.
		if ( 0 !== $source_elements->length ) {
			foreach ( $source_elements as $source_element ) {
				$sizes_attr = $source_element->getAttribute( 'sizes' );
				$source_element = self::add_data_sizes_attr( $source_element, $sizes_attr );

				$source_element = self::add_data_srcset_attr( $source_element, $sizes_attr );

				if ( $source_element->hasAttribute( 'src' ) ) {
					// Get src value.
					$src = $source_element->getAttribute( 'src' );

					// Set data-src value.
					$source_element->setAttribute( 'data-src', $src );

					$source_element = self::set_src_placeholder( $source_element );
				}
			}
		}

		return $dom;
	}
}