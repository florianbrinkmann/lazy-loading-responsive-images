<?php
/**
 * Plugin Name: Lazy Loader
 * Plugin URI: https://florianbrinkmann.com/en/3350/responsive-images-and-lazy-loading-in-wordpress/
 * Description: Lazy loading plugin that supports images, iFrames, video and audio elements and uses lazysizes.js. With manual modification of the markup it is also possible to lazy load background images, scripts, and styles.
 * Version: 3.2.0
 * Author: Florian Brinkmann, MarcDK
 * Author URI: https://florianbrinkmann.com/en/
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: lazy-loading-responsive-images
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * your option) any later version.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

// Include plugin class.
require_once 'src/class-plugin.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Plugin as LazyLoadResponsiveImages;

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Create object.
$plugin = new LazyLoadResponsiveImages();

// Init the plugin.
$plugin->init();

// Set plugin basename.
$plugin->basename = plugin_basename( __FILE__ );
