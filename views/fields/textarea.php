<?php

declare( strict_types=1 );

namespace FlorianBrinkmann\LazyLoadResponsiveImages;

/**
 * Textarea field callback.
 *
 * @global array $args               {
 *                                  Argument array.
 *
 * @type string $label_for          (Required) The label for the textarea.
 * @type string $value              (Required) The value.
 * @type string $description        (Optional) Description.
 * }
 */
$option_value = $args['value'];
$label = $args['label_for'];
$desc = $args['description'] ?? '';
?>
<textarea id="<?php echo esc_attr( $label ); ?>" name="<?php echo esc_attr( $label ); ?>" style="width: 100%;"><?php echo esc_textarea( $option_value ); ?></textarea>
<?php
if ( '' !== $desc ) { ?>
	<p class="description">
		<?php echo wp_kses( $desc, ALLOWED_DESCRIPTION_HTML  ); ?>
	</p>
	<?php
}