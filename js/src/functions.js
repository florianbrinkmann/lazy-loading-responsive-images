// Highly inspired by https://github.com/inpsyde/gutenberg-example/blob/ceac1c6fa0f1484b955d2ba5b7414cc5672617b1/assets/js/src/EditorPicks/index.js
const { CheckboxControl } = wp.components;
const { PluginPostStatusInfo } = wp.editPost;
const { 
	compose,
	withInstanceId
 } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { registerPlugin } = wp.plugins;
const { __ } = wp.i18n;

const LazyLoadCheckboxRender = ({ isLazyLoaderDisableCheckboxChecked = false, onChangeLazyLoaderCheckbox }) => (
	<PluginPostStatusInfo className='lazy-loader-plugin'>
		<div>
			<CheckboxControl
					label={ __( 'Disable Lazy Loader', 'lazy-loading-responsive-images' ) }
					checked={ isLazyLoaderDisableCheckboxChecked }
					onChange={ () => onChangeLazyLoaderCheckbox( ! isLazyLoaderDisableCheckboxChecked ) }
			/>
		</div>
	</PluginPostStatusInfo>
)

const LazyLoaderGutenberg = compose(
	[
		withSelect((select) => {
			return {
				isLazyLoaderDisableCheckboxChecked: select('core/editor').getEditedPostAttribute('meta').lazy_load_responsive_images_disabled
			}
		}),
		withDispatch((dispatch) => {
			return {
				onChangeLazyLoaderCheckbox (lazy_load_responsive_images_disabled) {
					dispatch('core/editor').editPost({ meta: { lazy_load_responsive_images_disabled} })
				}
			}
		})
	]
)(LazyLoadCheckboxRender)

registerPlugin('lazy-loader-gutenberg', {
  render: LazyLoaderGutenberg
})
