<?php

declare( strict_types=1 );

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Checkbox callback.
 *
 * @global array $args               {
 *                                  Argument array.
 *
 * @type string $label_for          (Required) The label for the checkbox.
 * @type string $value              (Required) The value.
 * @type string $description        (Optional) Description.
 * }
 */
$option_value = $args['value'];
$label = $args['label_for'];
$desc = $args['description'] ?? '';
?>
<input id="<?php echo esc_attr( $label ); ?>" name="<?php echo esc_attr( $label ); ?>"
   type="checkbox" <?php echo ( $option_value == '1' || $option_value == 'on' ) ? 'checked="checked"' : ''; ?>>
<?php
if ( '' !== $desc ) { ?>
	<p class="description">
		<?php echo wp_kses( $desc, LAZY_LOADER_ALLOWED_DESCRIPTION_HTML  ); ?>
	</p>
	<?php
}