=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz,paymentgateway,fpx,malaysia
Tested up to: 4.8.2
Stable tag: 3.18
Donate link: http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/
Requires at least: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.6

Accept Internet Banking Payment by using Billplz. 

== Description ==
Install this plugin to accept payment using Billplz (Maybank2u, CIMB Clicks, Bank Islam, FPX). 

== Upgrade Notice == 

WARNING! THIS UPDATE MAY BREAK YOUR SITE!

Please re-configure the plugin if you upgrading from version 3.14 or ealier
1. Make sure your PHP Version is : 5.6/7.0/7.1
2. Set X Signature Key

== Screenshots ==
* Installing Billplz for WooCommerce
* Activate plugin after installation
* Set API Secret Key and X Signature Key
* Enable X Signature Key at [Billplz Account Settings](https://www.billplz.com/enterprise/setting)

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

**Step 1:**

- Login to your *WordPress Dashboard*
- Navigate to **Plugins >> Add New**
- Search **Billplz for WooCommerce >> Install Now**

**Step 2:**

- Activate Plugin

**Step 3:**

- Navigate to **WooCommerce >> Settings >> Checkout >> Billplz**
- Insert your **API Secret Key** and **X Signature Key**
- Save changes

== Frequently Asked Questions ==


= Where can I get API Secret Key? =

You can the API Secret Key at your Billplz Account Settings. [Get it here](https://www.billplz.com/enterprise/setting)

= Where can I get X Signature Key? =

You can the X Signature Key at your Billplz Account Settings. [Get it here](https://www.billplz.com/enterprise/setting)

= Troubleshooting =

1. If you are not getting a **Callback/Redirect** response from Billplz:

	Please make sure you have **Tick "Enable XSignature Payment Completion"** on Billplz Account Settings and make sure you have set your **X Signature Key**.
	
2. If you want both Email & Phone Number to be captured on Bills:

	Set Notification settings to **No Notification** or **Both**	

== Links ==
[Sign Up](http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/) for Billplz account to accept payment using Billplz now!
