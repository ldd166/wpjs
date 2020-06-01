=== jsDelivr - Wordpress CDN Plugin ===
Contributors: jimaek
Donate link: https://www.jsdelivr.com
Tags: cdn,speed,jsdelivr,optimize,delivery,network,javascript,async,defer,performance,
Requires at least: 4.0
Tested up to: 5.0
Stable tag: 1.0

The official plugin of jsDelivr.com, a free public CDN. An easy way to integrate the service and speed up your website using our super fast CDN.

== Description ==

This plugin allows you to easily integrate and use the services of [jsDelivr.com](https://www.jsdelivr.com) in your WordPress website.

[Support](https://github.com/jsdelivr/jsdelivr-wordpress) | [Github Repo](https://github.com/jsdelivr/jsdelivr-wordpress)

jsDelivr is a free public CDN that hosts open source files, including javascript libraries, fonts, css frameworks all of the files they need to work (css/png).
It also hosts all files used by all free WordPress plugins and themes hosted in the official WordPress repo.

jsDelivr uses multiple CDN providers such as Stackpath, Fastly, Cloudflare and Quantil in China to ensure the best possible performance and 100% uptime to all users. Make sure to [checkout our infographic to learn how it works](https://www.jsdelivr.com/network/infographic).

With this plugin you can automatically integrate our super fast CDN into your website without the trouble of editing your code and searching for the correct URLs.
Just install the plugin and it will automatically take care of the rest and ensure that all valid files will be loaded through our super fast CDN.

**Benefits:**

*	Speeds up your website
*	Cuts the bandwidth bill
*	Offloads the server from extra requests

**Features:**

* 	On the fly rewriting of all URLs. No need to change the code.
* 	Move selected files to footer
* 	Apply Async/Defer loading to your javascripts.
* 	Compatible with W3 Total Cache and WP Super Cache
* 	Fully automatic. Install and forget about it.
* 	Allows you to select the files you want to load from the CDN
*	  Supports HTTPS


== Installation ==

1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation.
2. Activate the Plugin from Plugins page.
3. Go to Settings-> jsDelivr CDN and follow the instructions

== Screenshots ==
1. First time screen
2. Settings menu after update and scan are finished. Move to footer is enabled.

== Frequently Asked Questions ==

= What does the yellow match mean? =
You have to be careful with those. It can be two things.

*	It can be a more recent version of the same file you are using. In this case you must make sure that the newer version wont break anything. You can enable it temporary and test it.
*	It can be a similar file (from the plugin's perspective) from an other package. Again you will have to test it to be sure.

= I get a 100% matching file but the name of the package is wrong =
If the match is 100% then you just matched a file used also by that package.
Some plugins use common images or well known libraries to work, you just matched an identical file from an other package.
Dont worry about it.



== Changelog ==

= 1.0 =
* first stable release




= 0.1 =
* First release
