# Lazy Loader 
**Contributors:** FlorianBrinkmann  
**Donate link:** https://www.paypal.me/flobrinkmann  
**Tags:** lazysizes, lazy loading, performance, images  
**Requires at least:** 4.9.8  
**Tested up to:** 6.5  
**Stable tag:** 8.2.0  
**Requires PHP:** 7.0  


## Description 

Lazy loading plugin that supports images, iFrames, video and audio elements and uses the lightweight lazysizes script. With manual modification of the markup it is also possible to lazy load background images, scripts, and styles.

Lazy loads (without the need of any manually modifications):

* Images inserted via `img` or `picture` in posts, pages, Custom Post Types, Text Widgets, …
* Post thumbnails.
* Inline background images.
* Or all images and other enabled media in the markup (that is not excluded or already has a lazy loading attribute), via the option to process the complete markup.
* iFrames.*
* Video elements.*
* Audio elements.*

\* *Can be enabled in the plugin options.*

**The plugin comes with the following options (under Settings › Media › Lazy Loader options):**

* Do not lazy load elements with specific CSS classes (you can use the class `skip-lazy` by default).
* Add filters that should be processed by Lazy Loader.
* Enable lazy loading for iFrames.
* Include the [lazysizes native loading plugin](https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/native-loading) that modifies images and iFrames to use the native lazy loading feature of browsers that already support it.
* Include the [lazysizes unveilhooks plugin](https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks) that adds support for more elements, for example, video and audio elements.*
* Enable lazy loading for the poster frame of video elements.
* Enable lazy loading for audio elements.
* Enable lazy loading for inline background images.
* Display a loading spinner.
* Disable the plugin on specific posts/pages (this shows a checkbox in the edit view of all public post types (except attachments) to disable lazy loading for an entire post).
* Process the complete markup of the site.
* A textarea to modify the default lazysizes config values.

\* The unveilhooks extension of lazysizes supports more than video and audio elements, but you need to manually modify the markup to use it for:

* Background images not inserted with inline styles.
* Scripts.
* Styles.

The plugin adds a `noscript` element as fallback for disabled JavaScript (can be disabled with the `lazy_loader_generate_noscript` filter).

You can disable lazy loading for elements with specific CSS classes by defining them via the plugin settings (*Settings* › *Media* › *Lazy Loader options*). Or use the `skip-lazy` class or the `data-skip-lazy` attribute. `skip-lazy` and `data-skip-lazy` also work on wrapper elements to exclude the wrapper and its children from being processed.


## Installation 

* Install plugin.
* Activate it.
* You can find the plugin settings under *Settings* › *Media* › *Lazy Loader options*.


## Frequently Asked Questions 


### Is there a way to manually call the plugin to modify markup of not-supported image functions? 

Yes. See the following example that would generate lazy-load-ready output for the result of the not-supported `wp_get_attachment_image()` function:

```php
global $lazy_loader;
if ( isset( $lazy_loader ) && $lazy_loader instanceof FlorianBrinkmann\LazyLoadResponsiveImages\Plugin ) {
	echo $lazy_loader->filter_markup( wp_get_attachment_image( 1261 ) );
}
```

To make it happen, you need to pass the markup that contains the image (or images) to `$lazy_loader->filter_markup()`. The `if` statement ensures that the Lazy Loader object is there and that it is an object of the correct class.


### How can I disable/modify the inline styles? 

**Important:** If you modify or remove the inline styles, also the style that shows the loading spinner is affected. So if you remove the styles or modify them without adding back the spinner styles, the spinner option will not work. [I created a Gist with the spinner styles – just add them to your modification to make it work](https://gist.github.com/florianbrinkmann/937495c7b41df3c1600ef7d9c6e95a9e).

To disable or modify the plugin’s inline styles (except the style that hides the `img.lazyload` elements when JS is disabled, so only the `noscript` version is displayed) you can use the `lazy_load_responsive_images_inline_styles` filter. For example, to remove the inline styles, use the following code:

```php
add_filter( 'lazy_load_responsive_images_inline_styles', function () {
	return '';
} );
```

If you want to modify it, you can do that like in the following code block (remember to include the opening and closing `style` tags for additions/replacements). The code modifies the duration of the fade-in-effect:

```php
add_filter( 'lazy_load_responsive_images_inline_styles', function ( $default_styles ) {
	$default_styles = sprintf(
		'%s <style>:root {
	--lazy-loader-animation-duration: 600ms;
}</style>',
		$default_styles
	);
	
	return $default_styles;
} );
```

The CSS from the example are the default styles that are used by the plugin (without the loading spinner styles). The `display: block` for `.lazyload` is important for the aspectratio plugin option.


### How can I adjust the lazy load threshold/other lazysizes settings? 

There is a textarea in the plugin settings where you can insert custom settings for the lazysizes config.

## Changelog 

### 8.2.0 – 14.04.2024

**Added**

* `lazy_loader_generate_noscript` filter to allow disabling of `noscript` element generation.

Tested with WordPress 6.5.

**Fixed**

- Updated `mastermind/html5` dependency to latest version, which fixes a PHP deprecation notice.

### 8.1.1 – 20.12.2022

Tested with WordPress 6.1.

**Fixed**

- Disable lazy loading in TranslatePress editor.

### 8.1.0 – 29.03.2021

**Added**

- Support for input elements of type `image`.

### 8.0.0 – 22.03.2021

**Removed**

- Processing of `wp_get_attachment_image`, introduced in 7.2.0. I just noticed that processing that filter by default may break cases where a wrapper element has the `skip-lazy` class to disable lazy loading for contained media elements. Lazy Loader is not aware of this when processing `wp_get_attachment_image`, because it cannot check the parent elements. If you want to process that filter with Lazy Loader, you can enter it in the settings field for additional filters.

### 7.2.3 – 19.03.2021

**Fixed**

- Stop processing embeds, because the lazysizes script is not loaded in the iframe and so the image in the embed can not be lazy loaded.

### 7.2.2 – 17.03.2021

**Fixed**

- Duplicate images in Ultimate Addons for Elementor widget – thanks @kagg-design.

### 7.2.1 – 09.03.2021 =

**Fixed**

- Missing JS files in SVN repo.

### 7.2.0 – 08.03.2021

Tested with WordPress 5.7.

**Added**

* Process `wp_get_attachment_image` filter that was added in WordPress 5.6.

**Changed**

* Updated lazysizes and its plugins to 5.3.0.

**Fixed**

* Use same escaped version of SVG placeholder for `src` and `srcset` to fix a HTML validation error.

### 7.1.0 – 04.11.2020

**Changed**

* Add timestamp of file modification as version query string to Lazy Loader JS files instead of WordPress version.

**Fixed**

* Problem with Lazy Loader being active in Oxygen Builder editor.

### 7.0.0 – 10.08.2020  

If you depend on the aspectratio script of lazysizes somehow that came with the plugin, this release has a breaking change: it removes the aspectratio option (and the script) because it uses inline SVGs as placeholders that have the same effect without needing the aspectratio script.

**Added**

* Disable core lazy load funcionality that comes with WP 5.5.
* `lazy_loader_attrs_to_strip_from_fallback_elem` filter that allows to provide an array of HTML attributes that should be stripped from the fallback element in the `noscript` element.

**Changed**

* If `width` and `height` attributes are present, the plugin uses an inline SVG to prevent content reflow – thanks to WP.org user lozula.
* Updated lazysizes and its extensions to version 5.2.2.

**Fixed**

* Problems with inline HTML comments in `script` tags. To fix that. inline script elements are no longer wrapped in HTML comments during markup processing to hide them from the parser. That was necessary for `DOMDocument()` but is not needed when using `Masterminds\HTML5()` (what Lazy Loader does).

**Removed**

* Aspectratio option. The change with the inline SVG makes that obsolete.

### 6.0.1 – 01.05.2020 

**Changed**

* Added empty default value for the `$content` param of `Plugin\filter_markup()` method to prevent a fatal error that occurs sometimes when Lazy Loader is used with the Content Cards plugin.
* Check if there is already a script with the `lazysizes` handle registered and deregister it before enqueuing own version. Fixes issue with Avada theme that includes own version.

### 6.0.0 – 31.01.2020 

**This release requires PHP 7**

**Added**

* Option to process the complete markup of a page instead of only parts of it via the filters (might take a little longer because the plugin needs to process more markup and, if your site contains HTML errors, lead to unwanted behavior, because the DOM Parser tries to correct that).
* Option to define additional filters for the plugin to process.
* Ignore patterns for `skip-lazy` class and `data-skip-lazy` attribute. Use them if you want Lazy Loader to ignore an element and its children. The `disable-lazyload` class and `data-no-lazyload` attribute will keep working for backwards compatibility. Adding the new class and attribute goes back to an initiative by Frank Goossens, developer of Autoptimize, who reached out to several devs of lazy loading plugins or plugins that also have a lazy loading feature to get a standard class and attribute across multiple plugins.
* Added donation link to plugin header.

**Changed**

* Updated required PHP version to 7.0.
* Security improvements via escaping and specifying allowed HTML in setting descriptions. Thanks @igmoweb
* Simplified `Plugin\add_noscript_element()` method. It just clones the source element now, instead of creating a new empty element and adding all attributes to it.
* Check markup for `[caption]` shortcode to prevent duplicated images in Elementor.
* Class properties are private now.

**Fixed**

* Error with invalid attribute names in `Plugin\add_noscript_element()` method.


### 5.1.2 – 12.11.2019 

**Changed**

* Updated background image option text.


### 5.1.1 – 11.11.2019 

**Changed**

* Updated readme.


### 5.1.0 – 11.11.2019 

**Added**

* Lazy loading of background images.
* Lazy loading of videos with autoplay.

**Changed**

* Updated lazysizes and its extensions to 5.1.2.
* Updated tested up to version to 5.3.

**Fixed**

* Handle rest api requests with edit context as admin requests (thanks @websupporter!).
* Check if a post type supports `custom-fields` before loading block editor script.


### 5.0.0 – 28.06.2019 

**Changed**

* Increased priority for the calls of `the_content` and `post_thumbnail_html` filters from `500` to `10001` to fix issues with the ShortPixel Image Optimizer. So if you use those filters and they need to run after the Lazy Loader, you need to increase the priority, too. This is the change that makes it a major version change. 

**Fixed**

* Audio shortcode stripped from frontend with all following content.


### 4.1.0 – 31.05.2019 

*Tested with WordPress 5.2*

**Added**

* Option for using the native loading extension from lazysizes.
* Textarea for custom lazysizes config values.

**Changed**

* Updated lazysizes to 5.1.0.
* Use CSS variable `var(--lazy-loader-animation-duration)` for duration of fade-in-effect after loading.

**Fixed**

* Do not load assets on pages where Lazy Loader is disabled.
* Preserve HTML and hex entities.
* Ignore inline scripts.
* Skip images that already have a `data-src` attribute.
* Checkbox for disabling Lazy Loader not showing for Custom Post Types added via a plugin.


### 4.0.1 – 20.02.2019 

**Fixed**

* Removed debug code.


### 4.0.0 – 20.02.2019 

*Tested with WordPress 5.1.*

**Changed**
* Renamed object in main plugin file from `$plugin` to `$lazy_loader` to make it accessible via the theme.
* Added an example for calling the `filter_markup()` method of the plugin from the theme to modify markup of not-supported image functions like `wp_get_attachment_image()`.
* Updated lazysizes to 4.1.6.

**Fixed**

* Wrong year in changelog for 3.5.0 and 3.5.1. Thanks @pra85!
* Correctly remove the plugin options from the options database table on uninstall.


### 3.5.1 – 28.01.2019 

**Fixed**

* Wrong version number in plugin file.


### 3.5.0 – 28.01.2019 

**Added**

* Option to disable lazy loading for specific posts/pages via a checkbox. The checkbox can be enabled via an option under *Settings* › *Media* › *Lazy Loader options*.
* Possibility to use the `disable-lazyload` class to disable the lazy loader for an element and its children.

**Changed**

* Updated lazysizes to 4.1.5.
* Added note about limited browser support to loading spinner option.
* Updated placeholder source to a more stable variation (thanks diegocanal for the hint).

**Fixed**

* Only use `save_html()` method if markup was modified.
* Keep `srcset` attribute with placeholder source to get valid HTML.


### 3.4.0 – 05.07.2018 

**Added**

* Support for `picture` element.

**Fixed**

* Only use `data-sizes` attribute, if value is `auto`.
* Removed unnecessary check for `src` attribute in `modify_img_markup()`.


### 3.3.7 – 12.06.2018 

**Fixed**

* Disable libxml errors.


### 3.3.6 – 11.06.2018 

**Changed**

* Set a transparent data URI as img `src` instead of removing it to avoid page jumps that can happen during image loading (at least in Chrome).
* Updated lazysizes and plugins to 4.0.4.

**Fixed**

* Fix encoding issues with strings inside `script` elements.


### 3.3.5 – 14.04.2018 

**Fixed**

* Not working with PHP 5.3.


### 3.3.4 – 05.04.2018 

**Fixed**

* Use correct pattern for lazy loading of `video` and `audio` elements.
* Removed unnecessary `else` and a chunk of duplicate code (thanks mackzwellz).
* Issue with encoding of cyrillic characters.

**Changed**

* Updated lazysizes.js and the bundled plugins to 4.0.2.
* Run `post_thumbnail_html` filter later (priority 500, like for the `the_content` filter call, instead of 10), to fix a problem that appears when used with Responsify WP (thanks jgadbois).
* Moved the `add_noscript_element()` method call to the beginning of the `modify_*_markup` methods. With that, there is no need to remove the `lazyload` class in the `add_noscript_element()` method, because it was not added yet.
* Removed unnecessary `$new_iframe->setAttribute( 'src', $src )` call from the `add_noscript_element()`.
* Removed unnecessary `$dom->saveHTMLExact()` calls from the `modify_*_markup` methods.
* Use own `FlorianBrinkmann\LazyLoadResponsiveImages\Helpers()->save_html()` method for saving the HTML.


### 3.3.3 – 13.03.2018 

**Fixed**

* Fix broken code blocks in readme file.
* Set `.lazyload` to `display: block` to make the aspectratio option work correctly again.


### 3.3.2 – 09.03.2018 

**Fixed**

* Removed try to get width and height from images without `width` and `height` attr with `getimagesize` because it may cause a PHP warning.


### 3.3.1 – 09.03.2018 

**Fixed**

* Added inline doc for `FlorianBrinkmann\LazyLoadResponsiveImages\Settings()->add_color_picker()`.
* Only load color picker styles and script if on media settings page.


### 3.3.0 – 09.03.2018 

(there was also a new feature added in 3.2.9, but I forgot to increase the minor version number there…)

**Added**

* `lazy_load_responsive_images_inline_styles` filter for modifying the inline CSS (for modification, you also need to add the opening and closing `style` tags). If you use the spinner option and the filter, you need to add the spinner styles, because they are part of the filtered CSS ([Gist with the spinner styles used by default](https://gist.github.com/florianbrinkmann/937495c7b41df3c1600ef7d9c6e95a9e)).
* Option to display a loading spinner and define its color.

**Changed**

* Use style inside `noscript` element to hide `.lazyload` images if no JS instead of adding a `js` class via JS to the `html` element.
* Using `DOMXpath()->query()` to fetch the element nodes.
* Looping the nodes once inside `FlorianBrinkmann\LazyLoadResponsiveImages\Plugin()->filter_markup()` and no longer one time in each of the three element-specific methods.

**Fixed**

* Do not modify elements inside noscript elements.
* Doc fixes.


### 3.2.10 – 06.03.2018 

**Fixed**

* Small error in the readme.


### 3.2.9 – 06.03.2018 

**Added**

* Option to load the lazysizes aspectratio plugin. This plugin calculates the space that the images need before they are loaded. With that enabled, the lazy loading should also work for masonry grids without further markup modifications. Thanks to W.org user zitrusblau for the hint with the plugin.


### 3.2.8 – 27.02.2018 

**Fixed**

* Correctly set `.lazyload` images to `display: none` if JS is disabled.


### 3.2.7 – 22.02.2018 

**Added**

* Animated the opacity change when the images are loaded.

**Fixed**

* Duplicated images if, for example, the `the_content` filter is run multiple times.
* Small doc fix.


### 3.2.6 – 30.11.2017 

**Changed**

* Automatically load unveilhooks extension if audio or video option is enabled, regardless of the option to load the unveilhooks plugin is enabled or not.
* Updated *Tested up to* version to 4.9.1.


### 3.2.5 – 27.11.2017 

**Fixed**

* Wrong path to plugin options in the readme.txt.


### 3.2.4 – 25.11.2017 

**Fixed**

* Fatal error due case mismatch in referencing the SmartDOMDocument class – sorry for that!


### 3.2.3 – 25.11.2017 

**Fixed**

* Line break issues with the readme for W.org.


### 3.2.2 – 25.11.2017 

**Fixed**

* Problem with duplicated images in HTML widget.


### 3.2.1 – 25.11.2017 

**Fixed**

* Load minified version of lazysizes unveilhooks plugin.


### 3.2.0 – 25.11.2017 

**Added**

* Option to automatically lazy load iFrames.
* Option to automatically lazy load videos.
* Option to automatically lazy load audio.
* Option to additionally load the unveilhooks plugin of lazysizes. This enables lazy loading of audio, video, scripts, and more.
* Support for images inside the text and HTML widget. Does not work for galleries in the widgets.
* Support for Gravatars.
* Autoloading of classes with Composer.

**Changed**

* Changed plugin name to »Lazy Loader«.
* Moved settings to the media settings page and removed the customizer section.
* PHP comment style for inline comments.
* Renamed the class files in `src`.

**Fixed**

* Small doc errors.


### 3.1.13 – 08.11.2017 

**Changed**

* Updated lazysizes to 4.0.1.
* Updated »Tested up to« version to 4.9 in readme.


### 3.1.12 – 22.09.2017 

**Fixed**

* Added back the support for `data-no-lazyload` attr to disable lazy loading for specific images (was removed in 3.1.0 without keeping backwards compatibility in mind, sorry).


### 3.1.11 – 19.09.2017 

**Changed**

* Added details to the readme, what images are lazy loaded.

**Fixed**

* Now also adds a `noscript` element for gallery images.

**Removed**

* No lazy loading for images that are added via `wp_get_attachment_image()`, because for those images cannot be added a `noscript` fallback.


### 3.1.10 – 17.09.2017 

**Fixed**

* is_admin_request() check does not work for subdirectory installs.


### 3.1.9 – 25.08.2017 

**Fixed**

* Product image appears twice in WooCommerce cart.


### 3.1.8 – 10.08.2017 

**Fixed**

* Bump »Requires at least« to 4.5 because using wp_add_inline_script() since a few versions.


### 3.1.7 – 10.08.2017 

**Fixed**

* Use saveHTMLExact() method from SmartDomDocument to prevent doctype, html and body element to be added to the content.


### 3.1.6 – 07.08.2017 

**Fixed**

* Disable lazy loading for AMP pages which are generated by the »AMP« plugin by Automattic.


### 3.1.5 – 20.07.2017 

**Fixed**

* Further issues with PHP 5.3.
* Issue with lazy loaded post thumbnail in the backend.


### 3.1.4 – 14.07.2017 

**Fixed**

* Replaced [] array syntax with traditional one, so it works with PHP 5.3 again.


### 3.1.3 – 07.07.2017 

**Fixed**

* Wrong check for js class, which causes hidden images if nothing else added a js class…


### 3.1.2 

**Fixed**

* Capital P for one »WordPress« in readme and one in plugin description.


### 3.1.1 

**Changed**

* Added option to disable lazy loading for specific classes to readme.

**Fixed**

* Correct text domain.


### 3.1.0 

**Added**

* Customizer option to specify image class names to disable lazy loading for those.

**Changed**

* Using semver.
* Make it work with AJAX requests (thanks to zitrusblau).
* Using classes and namespaces.
* Using SmartDomDocument class.
* Updated plugin URL.
* Doc improvements.


### 3.0 

* Updated lazysizes to 3.0.0 (Thanks FlorianBrinkmann)
* Plugin version reflects version of lazysizes.


### 1.0.9 

* Allow opting out of lazy load with "data-no-lazyload" attribute (Thanx wheresrhys)


### 1.0.8 

* Bugfix for missing images if javascript is disabled.


### 1.0.7 

* Added a check to prevent the plugin being applied multiple times on the same image. (Thanx to saxonycreative)


### 1.0.6 

* added missing src attribute. Older browsers like the PS4 browser now work again.


### 1.0.5 

* now prevents lazy loading in atom feeds and the WordPress backend.


### 1.0.4 

* Changed description to reflect the compatibility with WordPress 4.4 core responsive images.
* Removed skipping of the first image in the post.
* Adds css class "js" to body tag for better compatibility.


### 1.0.3 

* fixed path to js and css.


### 1.0.2 

* typo in WordPress usernames.

## Thanks

Thanks to [@mtoensing](https://github.com/mtoensing) who created a W.org plugin from the code described in one of my posts and maintained it for the first 1,5 years.

And thanks to all contributors who helped with Pull Requests and/or opened Issues.