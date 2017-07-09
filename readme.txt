=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz,paymentgateway,fpx,malaysia
Tested up to: 4.8
Stable tag: 3.18
Donate link: https://www.billplz.com/hpojtffm3
Requires at least: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept Internet Banking Payment by using Billplz. 

== Description ==
Install this plugin to accept payment using Billplz (Maybank2u, CIMB Clicks, Bank Islam, FPX). 

For Installation Instruction, please refer to:
[How to Install](http://bit.ly/1SwkWJL)

== Upgrade Notice == 

WARNING! THIS UPDATE MAY BREAK YOUR SITE!

Please re-configure the plugin if you upgrading from version 3.14 or ealier
1. Make sure your PHP Version is : 5.6/7.0/7.1
2. Set X Signature Key

== Screenshots ==
* Will available soon

== Changelog ==

= 3.18 =

* IMPROVED: Bills will not be created twice in some circumstances

= 3.17 =
* IMPROVED: Compatibility with WooCommerce 2.x and 3.x
* IMPROVED: Some minor issue with template customization
* IMPROVED: There will be no leftover table data after Uninstallation
* IMPROVED: Reduced database query on Bills creation
* IMPROVED: No additional page on redirection to Billplz
* IMPROVED: Billplz Auto Invalidate Bills are also run on Hooks.
* IMPROVED: No Bills will be left unpaid for Bills Created in version 3.17
* UPDATED: Billplz API Class 3.04

= 3.16 =
* IMPROVED: Fix for WooCommerce 3.0 API Issue (do_it_wrong: wc_order)
* IMPROVED: Fix guzzle issue having same class name and function

= 3.15 =
* NEW: Implemented API Calls by using Billplz-API-Class (GitHub.com/wzul)
* NEW: API Calls now will made by using GuzzleHttp 6.0
* NEW: X Signature Key is MANDATORY to be set
* CHANGED: Collection ID is optional to be set
* REMOVED: Support for PHP 5.4 and older are removed
* REMOVED: Option for Mode are removed. Automatic detection by API Key

= 3.14 =
* NEW: Instruction added after payment
* IMPROVED: PHP 5.3 Compatibility

== Installation ==

For Installation Instruction, please refer to:
[How to Install](http://bit.ly/1SwkWJL)

== Frequently Asked Questions ==

= Where can I get Collection ID? =

You can the Collection ID at your Billplz Billing. Login to http://www.billplz.com


= Troubleshooting =

If you get infinite loop or JSON-like error:
1. Ensure the correct API Key and Collection ID has been set up
2. Contact us at sales@wanzul-hosting.com

== Links ==
[Wanzul Hosting](http://wanzul-hosting.com/) is the most reliable, cheap, recommended by the most web master around the world.

== Thanks ==
Special thanks to Akhie Joe for designing the button/banner and all donators! Thank You so much!
