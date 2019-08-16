=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz
Tested up to: 5.2
Stable tag: 3.21.9
Donate link: http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/
Requires at least: 4.6
License: GPL-3.0-or-later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.2.4

Accept payment by using Billplz.

== Description ==
Install this plugin to accept payment using Billplz.

== Upgrade Notice ==

None

== Screenshots ==
* Billplz for WooCommerce installation
* Activate plugin after installation
* Set API Secret Key, Collection ID and X Signature Key
* Enable X Signature Key at [Billplz Account Settings](https://www.billplz.com/enterprise/setting)

== Changelog ==

= 3.21.9 =
* IMPROVED: Add bfw_filter_order_data filter
* IMPROVED: Auto disable plugin when using other than MYR Currency
* IMPROVED: Webhook to have 403 forbidden status code if X Signature comparison failed
* REMOVED: Support for WooCommerce 2.x

= 3.21.8.1 =
* IMPROVED: Removed Boost e-wallet as a default title

= 3.21.8 =
* FIXED: Prevent X Signature Key from being exposed when PHP display errors is On

= 3.21.7 =
* FIXED: Undefined index when upgrading from old verison
* NEW: Added more hooks
* REMOVED: Billplz sign up referral link

= 3.21.6 =
* FIXED: Undefined index for type variable
* IMPROVED: Prevent multiple bill from being created if OTHERS option is choosen

== Installation ==

**Step 1:**

- Login to your *WordPress Dashboard*
- Navigate to **Plugins >> Add New**
- Search **Billplz for WooCommerce >> Install Now**

**Step 2:**

- Activate Plugin

**Step 3:**

- Navigate to **WooCommerce >> Settings >> Checkout >> Billplz**
- Insert your **API Secret Key**, **Collection ID** and **X Signature Key**
- Save changes

== Frequently Asked Questions ==


= Where can I get API Secret Key? =

You can the API Secret Key at your Billplz Account Settings. [Get it here](https://www.billplz.com/enterprise/setting)

= Where can I get Collection ID? =

You can the Collection ID at your Billplz >> Billing. [Get it here](https://www.billplz.com/enterprise/billing)

= Where can I get X Signature Key? =

You can the X Signature Key at your Billplz Account Settings. [Get it here](https://www.billplz.com/enterprise/setting)

= Troubleshooting =

1. If you are not getting a **Callback/Redirect** response from Billplz:

	Please make sure you have **Tick "Enable XSignature Payment Completion"** on Billplz Account Settings and make sure you have set your **X Signature Key**.

2. If you want both Email & Phone Number to be captured on Bills:

	Set Notification settings to **No Notification** or **Both**

== Links ==
[Sign Up](http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/) for Billplz account to accept payment using Billplz now!
