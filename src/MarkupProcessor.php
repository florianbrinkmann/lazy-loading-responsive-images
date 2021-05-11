<?php

declare( strict_types=1 );


namespace FlorianBrinkmann\LazyLoadResponsiveImages;


use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Masterminds\HTML5;

/**
 * Class MarkupParser
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */
class MarkupProcessor {
	/**
	 * If content was modified or not.
	 *
	 * @var bool
	 */
	private $content_is_modified = false;

	private $original_markup = '';

	private $html5;

	public function parse_markup( string $markup ): DOMDocument {
		$this->original_markup = $markup;

		// Disable libxml errors.
		libxml_use_internal_errors( true );

		// Create new HTML5 object.
		$this->html5 = new HTML5( array(
            'disable_html_ns' => true,
        ) );

		// Preserve html entities and conditional IE comments.
		// @link https://github.com/ivopetkov/html5-dom-document-php.
		$markup = preg_replace( '/&([a-zA-Z]*);/', 'lazy-loading-responsive-images-entity1-$1-end', $markup );
		$markup = preg_replace( '/&#([0-9]*);/', 'lazy-loading-responsive-images-entity2-$1-end', $markup );
		$markup = preg_replace( '/<!--\[([\w ]*)\]>/', '<!--[$1]>-->', $markup );
		$markup = str_replace( '<![endif]-->', '<!--<![endif]-->', $markup );

		// Load the HTML.
		return $this->html5->loadHTML( $markup );
	}

	/**
	 * Returns DOMNodeList of nodes that need to be processed.
	 * 
	 * @param DOMDocument DOMDocument object.
	 *
	 * @return DOMNodeList List of DOM nodes.
	 */
	public function get_dom_nodes( DOMDocument $dom ): DOMNodeList {
		$xpath = new DOMXPath( $dom );

		// Get all nodes except the ones that live inside a noscript element.
		// @link https://stackoverflow.com/a/19348287/7774451.
		return $xpath->query( "//*[not(ancestor-or-self::noscript)][not(ancestor-or-self::*[contains(@class, 'disable-lazyload') or contains(@class, 'skip-lazy') or @data-skip-lazy])]" );
	}

	public function get_html_string( DOMDocument $dom ): string {
		$content = $this->original_markup;

		if ( true === $this->content_is_modified ) {
			$content = $this->save_html( $dom, $this->html5 );
		}

		// Restore the entities and conditional comments.
		// @link https://github.com/ivopetkov/html5-dom-document-php/blob/9560a96f63a7cf236aa18b4f2fbd5aab4d756f68/src/HTML5DOMDocument.php#L343.
		if ( strpos( $content, 'lazy-loading-responsive-images-entity') !== false || strpos( $content, '<!--<script' ) !== false ) {
			$content = preg_replace('/lazy-loading-responsive-images-entity1-(.*?)-end/', '&$1;', $content );
			$content = preg_replace('/lazy-loading-responsive-images-entity2-(.*?)-end/', '&#$1;', $content );
			$content = preg_replace( '/<!--\[([\w ]*)\]>-->/', '<!--[$1]>', $content );
			$content = str_replace( '<!--<![endif]-->', '<![endif]-->', $content );
		}

		return $content;
	}

	/**
	 * Enhanced variation of \DOMDocument->saveHTML().
	 *
	 * Fix for cyrillic from https://stackoverflow.com/a/47454019/7774451.
	 * Replacement of doctype, html, and body from archon810\SmartDOMDocument.
	 *
	 * @param DOMDocument $dom DOMDocument object of the dom.
	 * @param HTML5 $html5 HTML5 object.
	 *
	 * @return string DOM or empty string.
	 */
	protected function save_html( DOMDocument $dom, HTML5 $html5 ): string {
		$xpath = new DOMXPath( $dom );
		$first_item = $xpath->query( '/' )->item( 0 );

		return preg_replace(
			[
				'/^\<\!DOCTYPE html>.*?<html>/si',
				'/<\/html>[\n\r]?$/si',
			],
			'',
			$this->html5->saveHTML( $first_item )
		);
	}

	/**
	 * Set content_is_modified property to true.
	 *
	 * @return void
	 */
	public function content_is_modified() {
		$this->content_is_modified = true;
	}
}