=== Billplz for WooCommerce ===
Contributors: wanzulnet
Tags: billplz,paymentgateway,fpx,boost
Tested up to: 4.9.7
Stable tag: 3.20.4
Donate link: http://billplz.com/join/lz7pmrxa45tiihvqdydxqq/
Requires at least: 4.6
License: GPL-3.0-or-later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.6

Accept Internet Banking Payment by using Billplz.

== Description ==
Install this plugin to accept payment using Billplz (Maybank2u, CIMB Clicks, Bank Islam, FPX).

== Upgrade Notice ==

WARNING! THIS UPDATE WILL BREAK YOUR SITE!

Please re-configure the plugin if you upgrading from version 3.19.0 or earlier

== Screenshots ==
* Installing Billplz for WooCommerce
* Activate plugin after installation
* Set API Secret Key and X Signature Key
* Enable X Signature Key at [Billplz Account Settings](https://www.billplz.com/enterprise/setting)

== Changelog ==

= 3.20.4 =
* NEW: Added link on setting page to BFW Tool for Bill Requery
* FIX: Added action hook to automatically remove cron created in version 3.19 and earlier

= 3.20.1 =
* FIX: Floating number precision issue.

= 3.20.0 =

* NEW: Introduced semantic versioning. X.Y.Z (X: API Version, Y: Major Release, Z: Minor Release)
* NEW: Ability to customize Proceed to Checkout button label
* NEW: Ability to add more information on Bills using Reference 2
* NEW: Ability to re-query bill and order for payment status
* IMPROVED: Order is now strongly coupled with Bill
* IMPROVED: CPU usage spike by previous version caused by Cron Jobs
* IMPROVED: Filters and Action Hooks are introduced
* IMPROVED: Full support for PHP 7.2

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
