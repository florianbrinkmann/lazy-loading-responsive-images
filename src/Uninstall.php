<?php
/**
 * Code that runs on uninstalling the plugin.
 *
 * @package FlorianBrinkmann\LazyLoadResponsiveImages
 */

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;

class Uninstall {
	use ConfigTrait;

	/**
	 * Uninstall constructor function.
	 *
	 * @param ConfigInterface $config
	 */
	public function __construct( ConfigInterface $config )
	{
		$this->processConfig( $config );
	}
    /**
     * Run things on uninstallation.
     *
     * @return void
     */
    public function run(): void {
		// Delete options.
		foreach ( $this->getConfigArray() as $option ) {
			delete_option( $option['wp_option_name'] );
		}
	}
}