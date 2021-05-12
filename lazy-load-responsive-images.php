<?php

declare( strict_types=1 );
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
 * Version:     8.1.0
 * Author:      Florian Brinkmann
 * Author URI:  https://florianbrinkmann.com/en/
 * License:     GPL v2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: lazy-loading-responsive-images
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use BrightNucleus\Config\ConfigFactory;
use BrightNucleus\Config\Exception\KeyNotFoundException;

// Load Composer autoloader. From https://github.com/brightnucleus/jasper-client/blob/master/tests/bootstrap.php#L55-L59
$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Create object.
$lazy_loader = new Plugin( ConfigFactory::create( __DIR__ . '/config/config.php' ), plugin_basename( __FILE__ ) );

// Init the plugin.
$lazy_loader->init();

// Action on uninstall.
register_uninstall_hook( plugin_basename( __FILE__ ), 'FlorianBrinkmann\LazyLoadResponsiveImages\uninstall' );

/**
 * Function that triggers routine to delete the settings from the database.
 *
 * @return void
 */
function uninstall(): void {
	$config = ConfigFactory::create( __DIR__ . '/config/config.php' );
	try {
		$lazy_loader_setting_controls = $config->getSubConfig( PLUGIN_PREFIX, 'setting_controls' );
	} catch ( KeyNotFoundException $e ) {
		error_log( 'The passed config key for Lazy Loader setting controls does not exist.' );
	}
	( new Uninstall( $lazy_loader_setting_controls ) )->run();
}