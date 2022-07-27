=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz
Tested up to: 6.0
Stable tag: 3.27.3
Requires at least: 4.6
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 7.0

Accept payment by using Billplz.

== Description ==
Install this plugin to accept payment using Billplz.

== Upgrade Notice ==

== Screenshots ==
* Billplz for WooCommerce installation
* Activate plugin after installation
* Set API Secret Key, Collection ID and X Signature Key
* Enable X Signature Key at Billplz Account Settings

== Changelog ==

= 3.27.3 =
* NEW: Added 2c2p Shopee Pay

= 3.27.2 =
* FIXED: Issue with unpaid bill result to processing for order in callback

= 3.27.1 =
* FIXED: Order status not updated when order are created from version prior to 3.27.0.

= 3.27.0 =
* NEW: Added ability to hard code API Key, X Signature Key and Collection ID
* NEW: Mobile phone number regular expression pattern to ensure non mobile phone number are removed
* NEW: Avoid cluttering the post meta key by using new table
* NEW: Changed how X Signature Hash is constructed
* NEW: Support for FPX B2B1 for pending transaction state

= 3.26.3 =
* IMPROVED: Bank list is now synched with Billplz API docs. 

= 3.26.2 =
* IMPROVED: Fix issue where no error message are displayed when payment cancelled 

= 3.26.1 =
* IMPROVED: Fix issue with 2c2p-wallet not appearing when 2c2p-card deactivated

= 3.26.0 =
* NEW: Added option to activate 2c2p wallet
* IMPROVED: Using woocommerce_form_field to generate select option
* IMPROVED: Using wp_remote_retrieve_response_code to prevent unexpected errors
* IMPROVED: Changed bank name according to Billplz

= 3.25.6 =
* NEW: Support for Enable Extra Payment Completion Information

== Installation ==

**Step 1:**

- Login to your *WordPress Dashboard*
- Navigate to **Plugins >> Add New**
- Search **Billplz for WooCommerce >> Install Now**

**Step 2:**

- Activate Plugin

**Step 3:**

- Navigate to **WooCommerce** >> **Settings** >> **Checkout** >> **Billplz**
- Insert your **API Secret Key**, **Collection ID** and **X Signature Key**
- Save changes

**Hiding API Key, Collection ID and X Signature Key**

The API Key, Collection and X Signature Key can be hidden from WordPress Dashboard by setting it on wp-config.php

- API Key: `define('BFW_API_KEY', '<your-api-key-here>');`
- X Signature: `define('BFW_X_SIGNATURE', '<your-x-signature-here>');`
- Collection ID: `define('BFW_COLLECTION_ID', '<your-collection-id-here>');`

== Frequently Asked Questions ==


= Where can I get API Secret Key? =

You can the API Secret Key at your Billplz Account Settings.

= Where can I get Collection ID? =

You can the Collection ID at your Billplz >> Billing.

= Where can I get X Signature Key? =

You can the X Signature Key at your Billplz Account Settings.

= Troubleshooting =

1. If you are not getting a **Callback/Redirect** response from Billplz:

	Please make sure you have **Tick "Enable XSignature Payment Completion"** on Billplz Account Settings and make sure you have set your **X Signature Key**.

2. To immediately reduce stock on add to cart, we strongly recommend you to use [WooCommerce Cart Stock Reducer](http://bit.ly/1UDOQKi) plugin.

== Links ==
[Sign Up](https://www.billplz.com) for Billplz account to accept payment using Billplz now!
