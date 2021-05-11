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
	use AddNoscriptElement;
	use SetSrcPlaceholder;

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
				// Check if we have a sizes attribute.
				$sizes_attr = '';
				if ( $source_element->hasAttribute( 'sizes' ) ) {
					// Get sizes value.
					$sizes_attr = $source_element->getAttribute( 'sizes' );

					// Check if the value is auto. If so, we modify it to data-sizes.
					if ( 'auto' === $sizes_attr ) {
						// Set data-sizes attribute.
						$source_element->setAttribute( 'data-sizes', $sizes_attr );

						// Remove sizes attribute.
						$source_element->removeAttribute( 'sizes' );
					}
				}

				// Check for srcset.
				if ( $source_element->hasAttribute( 'srcset' ) ) {
					// Get srcset value.
					$srcset = $source_element->getAttribute( 'srcset' );

					// Set data-srcset attribute.
					$source_element->setAttribute( 'data-srcset', $srcset );

					// Set srcset attribute with src placeholder to produce valid markup.
					if ( '' !== $sizes_attr ) {
						$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
						if ( \is_numeric ( $width ) ) {
							$source_element->setAttribute( 'srcset', self::$src_placeholder . " {$width}w" );
						} else {
							$source_element->removeAttribute( 'srcset' );
						}
					} else {
						// Remove srcset attribute.
						$source_element->removeAttribute( 'srcset' );
					}
				}

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