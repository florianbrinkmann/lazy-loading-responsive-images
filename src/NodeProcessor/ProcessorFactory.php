<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages\NodeProcessor;


use DOMNode;
use InvalidArgumentException;

use const FlorianBrinkmann\LazyLoadResponsiveImages\LAZY_LOADER_ENABLE_FOR_AUDIOS;
use const FlorianBrinkmann\LazyLoadResponsiveImages\LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES;
use const FlorianBrinkmann\LazyLoadResponsiveImages\LAZY_LOADER_ENABLE_FOR_IFRAMES;
use const FlorianBrinkmann\LazyLoadResponsiveImages\LAZY_LOADER_ENABLE_FOR_VIDEOS;

/**
 * Class ProcessFactory
 * @package FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor
 */
final class ProcessorFactory {
	/**
	 * @var Processor[]
	 */
	private static $processors = [];

	/**
	 * Returns instance of correct processor for the passed node.
	 *
	 * @param DOMNode $node
	 * @param array $config Config as array.
	 *
	 * @return Processor
	 */
	public static function get_content_processor( DOMNode $node, array $config ): Processor {
		// Check if the element has a style attribute with a background image.
		if (
			isset( $config[LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES] )
			&& true === $config[LAZY_LOADER_ENABLE_FOR_BACKGROUND_IMAGES]
			&& $node->hasAttribute( 'style' )
			&& 'img' !== $node->tagName
			&& 'picture' !== $node->tagName
			&& 'iframe' !== $node->tagName
			&& 'video' !== $node->tagName
			&& 'audio' !== $node->tagName
		) {
			if ( 1 === preg_match( '/background(-[a-z]+)?:(.)*url\(["\']?([^"\']*)["\']?\)([^;])*;?/', $node->getAttribute( 'style' ) ) ) {
				return self::get_processor_instance( 'BackgroundImageProcessor' );
			}
		}

		// Check if it is one of the supported elements and support for it is enabled.
		if ( 'img' === $node->tagName && 'source' !== $node->parentNode->tagName && 'picture' !== $node->parentNode->tagName ) {
			return self::get_processor_instance( 'ImgProcessor' );
		}

		if ( 'picture' === $node->tagName ) {
			return self::get_processor_instance( 'PictureProcessor' );
		}

		if (
			'input' === $node->tagName
			&& $node->hasAttribute( 'type' )
			&& $node->getAttribute( 'type' ) === 'image'
			&& $node->hasAttribute( 'src' )
		) {
			return self::get_processor_instance( 'InputProcessor' );
		}

		if ( isset( $config[LAZY_LOADER_ENABLE_FOR_IFRAMES] ) && true === $config[LAZY_LOADER_ENABLE_FOR_IFRAMES] && 'iframe' === $node->tagName ) {
			return self::get_processor_instance( 'IframeProcessor' );
		}

		if ( isset( $config[LAZY_LOADER_ENABLE_FOR_VIDEOS] ) && true === $config[LAZY_LOADER_ENABLE_FOR_VIDEOS] && 'video' === $node->tagName ) {
			return self::get_processor_instance( 'VideoProcessor' );
		}

		if ( isset( $config[LAZY_LOADER_ENABLE_FOR_AUDIOS] ) && true === $config[LAZY_LOADER_ENABLE_FOR_AUDIOS] && 'audio' === $node->tagName ) {
			return self::get_processor_instance( 'AudioProcessor' );
		}

		// No specific processor, so we return a NullProcessor.
		return self::get_processor_instance( 'NullProcessor' );
	}

	/**
	 * Returns shared instance of Processor.
	 *
	 * @param string $processor_name Name of the Processor to get the instance of.
	 *
	 * @return Processor
	 */
	private static function get_processor_instance( string $processor_name ): Processor {
		$processor_name = __NAMESPACE__ . '\\' . $processor_name;
		// Check if the processor name is a class.
		if ( ! class_exists( $processor_name ) ) {
			throw new InvalidArgumentException( 'Passed processor name is not a class' );
		}

		// Check if class implements Processor interface.
		$interfaces = class_implements( $processor_name );
		if ( ! isset( $interfaces[__NAMESPACE__ . '\\Processor'] ) ) {
			throw new InvalidArgumentException( 'Passed processor needs to implement the FlorianBrinkmann\LazyLoadResponsiveImages\ContentProcessor\Processor interface' );
		}

		if ( ! isset( self::$processors[$processor_name] ) ) {
			self::$processors[$processor_name] = new $processor_name();
		}

		return self::$processors[$processor_name];
	}
}