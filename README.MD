# Lenco payment gateway WooCommerce plugin

## Instructions for Using the Lenco Payment Gateway Plugin for WooCommerce/WordPress

## Overview

The Lenco Payment Gateway Plugin for WooCommerce allows you to accept payments via Lenco, a secure payment platform supporting card and mobile money transactions. Follow these steps to install, configure, and use the plugin.

## Installation Guide

1. Download the plugin as a ZIP file [here](https://github.com/LencoTech/lencopay-woocommerce-plugin/archive/refs/heads/main.zip).

### Upload the Plugin to WordPress:

1. Log in to your WordPress dashboard.
2. Navigate to Plugins > Add New.
3. Click Upload Plugin.
4. Choose the plugin ZIP file and click Install Now.
5. Once installed, click Activate.

## Configuration

### Access Plugin Settings:

1. Go to WooCommerce > Settings > Payments.
2. Find Lenco Payment Gateway and click Manage.

### Configure Settings:

1. **Enable/Disable:**
   - Check the box to enable the Lenco Payment Gateway.
2. **Description:**
   - Enter a description that will be shown to customers during checkout.
3. **Lenco Public Key:**
   - Enter your Lenco public key (the sandbox pub is set by default). This is required to authenticate your transactions. 
4. **Payment Channels:**
   - Select the payment channels you want to enable (card, or mobile-money) both are set by default.
5. **Success Redirect URL:**
   - Enter the URL where customers will be redirected after a successful payment. Redirects to the store by default.
6. **Failure Redirect URL:**
   - Enter the URL where customers will be redirected after a failed payment. Redirects to the store by default.
7. **Currency:**
   - Select the currency for transactions (e.g., Zambian Kwacha, Euro, British Pound, US Dollar, South African Rand).
8. **Environment:**
   - Choose the environment for transactions (Production or Sandbox). By default, the environment is set to sandbox.

## License

This plugin is licensed under the Apache License License.