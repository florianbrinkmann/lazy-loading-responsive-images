// Highly inspired by https://github.com/inpsyde/gutenberg-example/blob/ceac1c6fa0f1484b955d2ba5b7414cc5672617b1/assets/js/src/EditorPicks/index.js
// And the final fix by https://github.com/WordPress/gutenberg/issues/12289#issuecomment-441585195
import { CheckboxControl } from '@wordpress/components';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { 
	compose,
	withInstanceId
} from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

const LazyLoadCheckboxRender = ( { meta, updateMeta } ) => {
	const lazyLoaderDisabled = meta.lazy_load_responsive_images_disabled;
	return (
		<PluginPostStatusInfo className='lazy-loader-plugin'>
			<div>
				<CheckboxControl
						label={ __( 'Disable Lazy Loader', 'lazy-loading-responsive-images' ) }
						checked={ lazyLoaderDisabled }
						onChange={ ( value ) => {
							updateMeta( { lazy_load_responsive_images_disabled: value || 0 } );
						} }
				/>
			</div>
		</PluginPostStatusInfo>
	)
}

const LazyLoaderGutenberg = compose(
	[
		withSelect( ( select ) => {
			const {
				getEditedPostAttribute,
			} = select( 'core/editor' );
	
			return {
				meta: getEditedPostAttribute( 'meta' ),
			};
		} ),
		withDispatch( ( dispatch, { meta } ) => {
			const { editPost } = dispatch( 'core/editor' );

			return {
				updateMeta( newMeta ) {
					// Important: Old and new meta need to be merged in a non-mutating way!
					editPost( { meta: { ...meta, ...newMeta } } );
				},
			};
		} )
	]
)( LazyLoadCheckboxRender )

registerPlugin( 'lazy-loader-gutenberg', {
  render: LazyLoaderGutenberg
} )
