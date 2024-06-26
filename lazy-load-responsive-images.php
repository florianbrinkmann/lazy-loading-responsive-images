<?php
/**
 * Lazy Loader plugin.
 *
 * @package   FlorianBrinkmann\LazyLoadResponsiveImages
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Lazy Loader
 * Plugin URI:  https://florianbrinkmann.com/en/3350/responsive-images-and-lazy-loading-in-wordpress/
 * Description: Lazy loading plugin that supports images, iFrames, video and audio elements and uses lazysizes.js. With manual modification of the markup it is also possible to lazy load background images, scripts, and styles.
 * Version:     8.2.0
 * Author:      Florian Brinkmann
 * Author URI:  https://florianbrinkmann.com/en/
 * License:     GPL v2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: lazy-loading-responsive-images
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

if ( ! defined( 'WPINC' ) ) {
	die;
}

$lazy_loader = new Plugin();


$lazy_loader->set_basename( plugin_basename( __FILE__ ) );
$lazy_loader->set_js_asset_url( plugins_url( 'js/build/functions.js', __FILE__ ) );

$lazy_loader->init();
