=== Lazy Loader ===
Contributors: FlorianBrinkmann, MarcDK
Tags: lazysizes, lazy loading, performance, images
Requires at least: 4.5
Tested up to: 4.9.6
Stable tag: 3.4.0
Requires PHP: 5.3

== Description ==

Lazy loading plugin that supports images, iFrames, video and audio elements and uses the lightweight lazysizes script. With manual modification of the markup it is also possible to lazy load background images, scripts, and styles.

Lazy loads (without the need of any manually modifications):

* Images inserted via `img` or `picture` in posts, pages, Custom Post Types, Text Widgets, …
* Post thumbnails.
* iFrames.*
* Video elements.*
* Audio elements.*

\* *Can be enabled in the plugin options.*

**The plugin comes with the following options (under Settings › Media › Lazy Loader options):**

* Do not lazy load elements with specific CSS classes.
* Enable lazy loading for iFrames.
* Include the [lazysizes unveilhooks plugin](https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/unveilhooks) that adds support for more elements, for example, video and audio elements.*
* Enable lazy loading for the poster frame of video elements.
* Enable lazy loading for audio elements.
* Include [lazysizes aspectratio plugin](https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/aspectratio). This plugin calculates the needed space for images before they are loaded. That avoids content jumping when the images are loaded and makes the lazy loading work with masonry grids.
* Display a loading spinner.

\* The unveilhooks extension of lazysizes supports more than video and audio elements, but you need to manually modify the markup to use it for:

* Background images.
* Scripts.
* Styles.

The plugin adds a `noscript` element as fallback for disabled JavaScript.

The auto-modifying of the image markup does not work for images that are added using `wp_get_attachment_image()`, because there cannot be a `noscript` fallback added.

You can disable lazy loading for elements with specific CSS classes by defining them via the plugin settings (*Settings* › *Media* › *Lazy Loader options*). Or use the data-no-lazyload attribute.

== Installation ==

* Install plugin.
* Activate it.
* You can find the plugin settings under *Settings* › *Media* › *Lazy Loader options*.

== Frequently Asked Questions ==

= How can I disable/modify the inline styles? =

**Important:** If you modify or remove the inline styles, also the style that shows the loading spinner is affected. So if you remove the styles or modify them without adding back the spinner styles, the spinner option will not work. [I created a Gist with the spinner styles – just add them to your modification to make it work](https://gist.github.com/florianbrinkmann/937495c7b41df3c1600ef7d9c6e95a9e).

To disable or modify the plugin’s inline styles (except the style that hides the `img.lazyload` elements when JS is disabled, so only the `noscript` version is displayed) you can use the `lazy_load_responsive_images_inline_styles` filter. For example, to remove the inline styles, use the following code:

`
add_filter( 'lazy_load_responsive_images_inline_styles', function () {
	return '';
} );
`

If you want to modify it, you can overwrite the plugin’s styles like that (remember to include the opening and closing `style` tags):

`
add_filter( 'lazy_load_responsive_images_inline_styles', function ( $default_styles ) {
	return '<style>.lazyload {
			display: block;
		}

		.lazyload,
		.lazyloading {
			opacity: 0;
		}


		.lazyloaded {
			opacity: 1;
			transition: opacity 300ms;
		}</style>';
} );
`

The CSS from the example are the default styles that are used by the plugin (without the loading spinner styles). The `display: block` for `.lazyload` is important for the aspectratio plugin option.

== Changelog ==

= 3.4.0 – 05.07.2018 =

**Added**

* Support for `picture` element.

**Fixed**

* Only use `data-sizes` attribute, if value is `auto`.
* Removed unnecessary check for `src` attribute in `modify_img_markup()`.

= 3.3.7 – 12.06.2018 =

**Fixed**

* Disable libxml errors.

= 3.3.6 – 11.06.2018 =

**Changed**

* Set a transparent data URI as img `src` instead of removing it to avoid page jumps that can happen during image loading (at least in Chrome).
* Updated lazysizes and plugins to 4.0.4.

**Fixed**

* Fix encoding issues with strings inside `script` elements.

= 3.3.5 – 14.04.2018 =

**Fixed**

* Not working with PHP 5.3.

= 3.3.4 – 05.04.2018 =

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

= 3.3.3 – 13.03.2018 =

**Fixed**

* Fix broken code blocks in readme file.
* Set `.lazyload` to `display: block` to make the aspectratio option work correctly again.

= 3.3.2 – 09.03.2018 =

**Fixed**

* Removed try to get width and height from images without `width` and `height` attr with `getimagesize` because it may cause a PHP warning.

= 3.3.1 – 09.03.2018 =

**Fixed**

* Added inline doc for `FlorianBrinkmann\LazyLoadResponsiveImages\Settings()->add_color_picker()`.
* Only load color picker styles and script if on media settings page.

= 3.3.0 – 09.03.2018 =

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

= 3.2.10 – 06.03.2018 =

**Fixed**

* Small error in the readme.

= 3.2.9 – 06.03.2018 =

**Added**

* Option to load the lazysizes aspectratio plugin. This plugin calculates the space that the images need before they are loaded. With that enabled, the lazy loading should also work for masonry grids without further markup modifications. Thanks to W.org user zitrusblau for the hint with the plugin.

= 3.2.8 – 27.02.2018 =

**Fixed**

* Correctly set `.lazyload` images to `display: none` if JS is disabled.

= 3.2.7 – 22.02.2018 =

**Added**

* Animated the opacity change when the images are loaded.

**Fixed**

* Duplicated images if, for example, the `the_content` filter is run multiple times.
* Small doc fix.

= 3.2.6 – 30.11.2017 =

**Changed**

* Automatically load unveilhooks extension if audio or video option is enabled, regardless of the option to load the unveilhooks plugin is enabled or not.
* Updated *Tested up to* version to 4.9.1.

= 3.2.5 – 27.11.2017 =

**Fixed**

* Wrong path to plugin options in the readme.txt.

= 3.2.4 – 25.11.2017 =

**Fixed**

* Fatal error due case mismatch in referencing the SmartDOMDocument class – sorry for that!

= 3.2.3 – 25.11.2017 =

**Fixed**

* Line break issues with the readme for W.org.

= 3.2.2 – 25.11.2017 =

**Fixed**

* Problem with duplicated images in HTML widget.

= 3.2.1 – 25.11.2017 =

**Fixed**

* Load minified version of lazysizes unveilhooks plugin.

= 3.2.0 – 25.11.2017 =

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

= 3.1.13 – 08.11.2017 =

**Changed**

* Updated lazysizes to 4.0.1.
* Updated »Tested up to« version to 4.9 in readme.

= 3.1.12 – 22.09.2017 =

**Fixed**

* Added back the support for `data-no-lazyload` attr to disable lazy loading for specific images (was removed in 3.1.0 without keeping backwards compatibility in mind, sorry).

= 3.1.11 – 19.09.2017 =

**Changed**

* Added details to the readme, what images are lazy loaded.

**Fixed**

* Now also adds a `noscript` element for gallery images.

**Removed**

* No lazy loading for images that are added via `wp_get_attachment_image()`, because for those images cannot be added a `noscript` fallback.

= 3.1.10 – 17.09.2017 =

**Fixed**

* is_admin_request() check does not work for subdirectory installs.

= 3.1.9 – 25.08.2017 =

**Fixed**

* Product image appears twice in WooCommerce cart.

= 3.1.8 – 10.08.2017 =

**Fixed**

* Bump »Requires at least« to 4.5 because using wp_add_inline_script() since a few versions.

= 3.1.7 – 10.08.2017 =

**Fixed**

* Use saveHTMLExact() method from SmartDomDocument to prevent doctype, html and body element to be added to the content.

= 3.1.6 – 07.08.2017 =

**Fixed**

* Disable lazy loading for AMP pages which are generated by the »AMP« plugin by Automattic.

= 3.1.5 – 20.07.2017 =

**Fixed**

* Further issues with PHP 5.3.
* Issue with lazy loaded post thumbnail in the backend.

= 3.1.4 – 14.07.2017 =

**Fixed**

* Replaced [] array syntax with traditional one, so it works with PHP 5.3 again.

= 3.1.3 – 07.07.2017 =

**Fixed**

* Wrong check for js class, which causes hidden images if nothing else added a js class…

= 3.1.2 =

**Fixed**

* Capital P for one »WordPress« in readme and one in plugin description.

= 3.1.1 =

**Changed**

* Added option to disable lazy loading for specific classes to readme.

**Fixed**

* Correct text domain.

= 3.1.0 =

**Added**

* Customizer option to specify image class names to disable lazy loading for those.

**Changed**

* Using semver.
* Make it work with AJAX requests (thanks to zitrusblau).
* Using classes and namespaces.
* Using SmartDomDocument class.
* Updated plugin URL.
* Doc improvements.

= 3.0 =

* Updated lazysizes to 3.0.0 (Thanks FlorianBrinkmann)
* Plugin version reflects version of lazysizes.

= 1.0.9 =

* Allow opting out of lazy load with "data-no-lazyload" attribute (Thanx wheresrhys)

= 1.0.8 =

* Bugfix for missing images if javascript is disabled.

= 1.0.7 =

* Added a check to prevent the plugin being applied multiple times on the same image. (Thanx to saxonycreative)

= 1.0.6 =

* added missing src attribute. Older browsers like the PS4 browser now work again.

= 1.0.5 =

* now prevents lazy loading in atom feeds and the WordPress backend.

= 1.0.4 =

* Changed description to reflect the compatibility with WordPress 4.4 core responsive images.
* Removed skipping of the first image in the post.
* Adds css class "js" to body tag for better compatibility.

= 1.0.3 =

* fixed path to js and css.

= 1.0.2 =

* typo in WordPress usernames.
