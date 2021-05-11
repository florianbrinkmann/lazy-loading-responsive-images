<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMDocument;
use DOMNode;

use const FlorianBrinkmann\LazyLoadResponsiveImages\LAZY_LOADER_NATIVE_LAZY_LOAD;

/**
 * Class ImgProcessor
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
class ImgProcessor implements Processor {
	use AddNoscriptElement;
	use AddLazyloadClass;
	use SetSrcPlaceholder;

	/**
	 * @inheritDoc
	 */
	public static function process( DOMNode $node, DOMDocument $dom, array $config ): DOMDocument {
		// Check if the element already has a data-src attribute (might be the case for
		// plugins that bring their own lazy load functionality) and skip it to prevent conflicts.
		if ( $node->hasAttribute( 'data-src' ) ) {
			return $dom;
		}

		// Add noscript element when not child of picture element.
		if ( $node->parentNode === null || $node->parentNode->nodeName !== 'picture' ) {
			$dom = self::add_noscript_element( $dom, $node, $config );
		}

		// Check if the image has sizes and srcset attribute.
		$sizes_attr = '';
		if ( $node->hasAttribute( 'sizes' ) ) {
			// Get sizes value.
			$sizes_attr = $node->getAttribute( 'sizes' );

			// Check if the value is auto. If so, we modify it to data-sizes.
			if ( 'auto' === $sizes_attr ) {
				// Set data-sizes attribute.
				$node->setAttribute( 'data-sizes', $sizes_attr );

				// Remove sizes attribute.
				$node->removeAttribute( 'sizes' );
			}
		}

		if ( $node->hasAttribute( 'srcset' ) ) {
			// Get srcset value.
			$srcset = $node->getAttribute( 'srcset' );

			// Set data-srcset attribute.
			$node->setAttribute( 'data-srcset', $srcset );

			// Set srcset attribute with src placeholder to produce valid markup.
			$node_width  = $node->getAttribute( 'width' );
			if ( '' !== $node_width ) {
				$node->setAttribute( 'srcset', self::$src_placeholder . " {$node_width}w" );
			} elseif ( '' === $node_width && '' !== $sizes_attr ) {
				$width = preg_replace( '/.+ (\d+)px$/', '$1', $sizes_attr );
				if ( \is_numeric ( $width ) ) {
					$node->setAttribute( 'srcset', self::$src_placeholder . " {$width}w" );
				} else {
					$node->removeAttribute( 'srcset' );
				}
			} else {
				// Remove srcset attribute.
				$node->removeAttribute( 'srcset' );
			}
		}

		// Get src value.
		$src = $node->getAttribute( 'src' );

		// Set data-src value.
		$node->setAttribute( 'data-src', $src );

		if ( isset( $config[LAZY_LOADER_NATIVE_LAZY_LOAD] ) && true === $config[LAZY_LOADER_NATIVE_LAZY_LOAD] ) {
			$node->setAttribute( 'loading', 'lazy' );
		}

		$node = self::add_lazyload_class( $node );

		$node = self::set_src_placeholder( $node );

		return $dom;
	}
}