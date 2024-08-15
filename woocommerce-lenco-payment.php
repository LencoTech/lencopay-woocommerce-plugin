<?php
/*
Plugin Name: Lenco Payment Gateway
Description: A WooCommerce plugin to integrate Lenco payment gateway.
Version: 1.0
Author: Mathews Musukuma
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize the gateway class when WooCommerce is fully loaded
add_action('plugins_loaded', 'init_lenco_payment_gateway');

function init_lenco_payment_gateway() {

    include_once(plugin_dir_path(__FILE__) . 'includes/lenco-settings.php');
    include_once(plugin_dir_path(__FILE__) . 'includes/lenco-pay.php');


    if (!class_exists('WC_Payment_Gateway')) return;

    add_filter('woocommerce_payment_gateways', 'add_lenco_gateway');
    function add_lenco_gateway($methods) {
        $methods[] = 'WC_Lenco_Payment_Gateway';
        return $methods;
    }
}

function lenco_plugin_action_links( $links ) {
    $settings_link = array(
    	'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=lenco' ) . '" title="View Settings">Settings</a>'
    );
    return array_merge( $settings_link, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lenco_plugin_action_links' );
