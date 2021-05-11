<?php

declare( strict_types=1 );

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Text field callback.
 *
 * @global array $args               {
 *                                  Argument array.
 *
 * @type string $label_for          (Required) The label for the text field.
 * @type string $value              (Required) The value.
 * @type string $description        (Optional) Description.
 * }
 */
$option_value = $args['value'];
$label = $args['label_for'];
$desc = $args['description'] ?? '';
?>
<input id="<?php echo esc_attr( $label ); ?>" name="<?php echo esc_attr( $label ); ?>"
   type="text" value="<?php echo esc_attr( $option_value ); ?>">
<?php
if ( '' !== $desc ) { ?>
	<p class="description">
		<?php echo wp_kses( $desc, LAZY_LOADER_ALLOWED_DESCRIPTION_HTML  ); ?>
	</p>
	<?php
}