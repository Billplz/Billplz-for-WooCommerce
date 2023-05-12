# Billplz for WooCommerce

Accept payment using Billplz.
Compatible up to:
- PHP 8.1
- Wordpress 6.2
- Woocommerce 7.6.0

# Installation

There 2 ways to Install this plugin:

## 1-Click Installation

* Login to WordPress Dashboard
* Navigate to Plugins >> Add New
* Search "Billplz for WooCommerce"
* Click Install >> Activate

## Manual Installation

* Download: https://github.com/Billplz/Billplz-for-WooCommerce/archive/master.zip
* Extract the folder billplz-for-woocommerce
* ZIP the folder to billplz-for-woocommerce.zip
* Login to WordPress Dashboard
* Navigate to Plugins >> Add New >> Upload
* Upload the files >> Activate


# Configuration

* Login to WordPress Dashboard
* Navigate to WooCommerce >> Settings >> Checkout >> Billplz
* Set up API Secret Key, Collection ID and X Signature Key
* Save changes

## Hiding API Key, Collection ID and X Signature Key

The API Key, Collection and X Signature Key can be hidden from WordPress Dashboard by setting it on wp-config.php

**Live API Credentials**
* API Key: `define('BFW_API_KEY', '<your-live-api-key-here>');`
* X Signature: `define('BFW_X_SIGNATURE', '<your-live-x-signature-here>');`
* Collection ID: `define('BFW_COLLECTION_ID', '<your-live-collection-id-here>');`

**Sandbox API Credentials**
* API Key: `define('BFW_SANDBOX_API_KEY', '<your-sandbox-api-key-here>');`
* X Signature: `define('BFW_SANDBOX_X_SIGNATURE', '<your-sandbox-x-signature-here>');`
* Collection ID: `define('BFW_SANDBOX_COLLECTION_ID', '<your-sandbox-collection-id-here>');`

# Other

Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)
