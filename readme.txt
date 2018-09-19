=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz,paymentgateway,fpx,boost
Tested up to: 4.9.8
Stable tag: 3.20.10
Donate link: http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/
Requires at least: 4.6
License: GPL-3.0-or-later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.2.4

Accept Internet Banking Payment by using Billplz.

== Description ==
Install this plugin to accept payment using Billplz.

== Upgrade Notice ==

None

== Screenshots ==
* Installing Billplz for WooCommerce
* Activate plugin after installation
* Set API Secret Key and X Signature Key
* Enable X Signature Key at [Billplz Account Settings](https://www.billplz.com/enterprise/setting)

== Changelog ==

= 3.20.10 =
* NEW: Added more hooks to support premium features!

= 3.20.9 =
* IMPROVED: Added PHP version support from 5.2.4 to 7.2

= 3.20.8 =
* FIX: Order ID should not be accessed directly according to WooCommerce 3.x

= 3.20.7 =
* NEW: Alert will be given if Collection ID is not set
* IMPROVED: Reduced Billplz API query for Production Mode

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

= Where can I get X Signature Key? =

You can the X Signature Key at your Billplz Account Settings. [Get it here](https://www.billplz.com/enterprise/setting)

= Troubleshooting =

1. If you are not getting a **Callback/Redirect** response from Billplz:

	Please make sure you have **Tick "Enable XSignature Payment Completion"** on Billplz Account Settings and make sure you have set your **X Signature Key**.

2. If you want both Email & Phone Number to be captured on Bills:

	Set Notification settings to **No Notification** or **Both**

== Links ==
[Sign Up](http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/) for Billplz account to accept payment using Billplz now!