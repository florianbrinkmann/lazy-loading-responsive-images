<?php

declare( strict_types=1 );

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Checkbox callback.
 *
 * @global bool $maybe_enabled If checkbox is enabled (depending on user role).
 * @global int $value Current value of checkbox.
 */
printf(
	'<div class="misc-pub-section dewp-planet">
		<label for="disable-lazy-loader">
			<input type="checkbox" id="disable-lazy-loader" name="disable-lazy-loader" class="disable-lazy-loader" %s %s />
			<span class="dewp-planet__label-text">%s</span>
		</label>
	</div>',
	$maybe_enabled ? '' : 'disabled',
	$value === 1 ? 'checked' : '',
	esc_html__( 'Disable Lazy Loader', 'lazy-loading-responsive-images' )
);