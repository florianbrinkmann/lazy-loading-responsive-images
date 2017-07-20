<?php
/**
 * Plugin Name: Lazy Loading Responsive Images
 * Plugin URI: https://florianbrinkmann.com/en/3350/responsive-images-and-lazy-loading-in-wordpress/
 * Description: Lazy loading Images plugin that works with responsive images introduced in WordPress 4.4.
 * Version: 3.1.5
 * Author: Florian Brinkmann, MarcDK
 * Author URI: http://www.marc.tv
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

/**
 * Include plugin class.
 */
require_once 'src/class-plugin.php';

use FlorianBrinkmann\LazyLoadResponsiveImages\Plugin as LazyLoadResponsiveImages;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Run the plugin.
 */
new LazyLoadResponsiveImages();
