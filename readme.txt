=== Lazy Loading Responsive Images ===
Contributors: FlorianBrinkmann, MarcDK
Tags: lazysizes, unveil, ricg, responsive, lazy, performance, images
Requires at least: 3.0
Tested up to: 4.8
Stable tag: 3.1.5

== Description ==

Lazy loading Images plugin that works with responsive images.
Compatible with »RICG Responsive Images« and works with responsive images introduced in WordPress 4.4.
Based on lazysizes.js

You can disable lazy loading for specific image classes by adding them in the customizer (*Appearance* › *Customize* › *Lazy loading options*).

== Installation ==

* Install plugin
* Activate it

== Changelog ==

= 3.1.5 — 20.07.2017 =

**Fixed**

* Further issues with PHP 5.3.
* Issue with lazy loaded post thumbnail in the backend.

= 3.1.4 — 14.07.2017 =

**Fixed**

* Replaced [] array syntax with traditional one, so it works with PHP 5.3 again.

= 3.1.3 — 07.07.2017 =

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
