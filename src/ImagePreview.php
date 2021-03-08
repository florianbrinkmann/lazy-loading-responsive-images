<?php
/**
 * Image Preview Class.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use kornrunner\Blurhash\Blurhash;

/**
 * Class ImagePreview
 *
 * Class handling the image preview option.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class ImagePreview {

	private static $previews_to_generate = [];
	
	/**
	 * Ger preview data for image.
	 *
	 * @param string $src The image URL.
	 * @return array
	 */
	public static function get_preview_data( string $src ) : array {
		$attachment_id = attachment_url_to_postid( $src );
		if ( $attachment_id === 0 ) {
			// Maybe it is a thumbnail, so try to remove the widthxheight from the end.
			$src = preg_replace( '/(-\d+x\d+)(\.[a-zA-Z]{2,4})$/', '$2', $src, 1, $count );
			if ( $count === 1 ) {
				$attachment_id = attachment_url_to_postid( $src );
			}

			if ( $attachment_id === 0 ) {
				// Maybe it has a `-scaled` string at the end.
				$src = preg_replace( '/(-scaled)\.[a-zA-Z]{2,4}$$/', '$2', $src, 1, $count );
			}

			if ( $count === 1 ) {
				$attachment_id = attachment_url_to_postid( $src );
			}

			if ( $attachment_id === 0 ) {
				return [];
			}
		}

		$preview_data = get_post_meta( $attachment_id, 'lazy_load_responsive_images_preview_data', true );

		if ( empty( $preview_data ) ) {
			self::$previews_to_generate[$attachment_id] = [
				'attachment_id' => $attachment_id,
				'src' => $src,
			];

			return [];
		}

		return $preview_data;
	}

	/**
	 * Generate preview strings for images
	 *
	 * @param array $previews_to_generate Array with image data that need previews.
	 * @return void
	 */
	public static function generate_preview_strings( array $previews_to_generate ) : void {
		foreach ( $previews_to_generate as $preview_to_generate ) {
			self::generate_preview_string( $preview_to_generate['attachment_id'], $preview_to_generate['src'] );
		}
	}

	/**
	 * Generate preview string for an image.
	 *
	 * @param integer $attachment_id The attachment id.
	 * @param string $image_src The image URL.
	 * @return void
	 */
	private static function generate_preview_string( int $attachment_id, string $image_src ) : void {
		// Get small image format.
		$thumbnail = image_get_intermediate_size( $attachment_id );

		$thumbnail = $thumbnail !== false ? $thumbnail['url'] : $image_src;
		// Generate preview string.
		$image = imagecreatefromstring( file_get_contents( $thumbnail ) );
		$width = imagesx( $image );
		$height = imagesy( $image );

		$components_x = (int) $width;
		$components_y = (int) $height;
		while ( $components_x >= 9 && $components_y >= 9 ) {
			$components_x /= 2;
			$components_y /= 2;
		}

		while ( $components_x >= 5 || $components_y >= 5 ) {
			$components_x /= 1.5;
			$components_y /= 1.5;
		}

		$components_x = (int) round( $components_x );
		$components_y = (int) round( $components_y );

		$pixels = [];
		for ( $y = 0; $y < $height; ++$y ) {
			$row = [];
			for ( $x = 0; $x < $width; ++$x ) {
				$index = imagecolorat( $image, $x, $y );
				$colors = imagecolorsforindex( $image, $index );

				$row[] = [ $colors['red'], $colors['green'], $colors['blue'] ];
			}
			$pixels[] = $row;
		}

		update_post_meta( $attachment_id, 'lazy_load_responsive_images_preview_data', 
			array(
				'blurhash' => Blurhash::encode( $pixels, $components_x, $components_y ),
				'components_x' => $components_x,
				'components_y' => $components_y,
			)
		);
	}
	
	/**
	 * Get data for images that need previews.
	 *
	 * @return array
	 */
	public static function get_previews_to_generate() : array {
		return self::$previews_to_generate;
	}
}
