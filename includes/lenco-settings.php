<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lenco_payment_gateway_settings() {
    return array(
        'enabled' => array(
            'title' => 'Enable/Disable',
            'type' => 'checkbox',
            'label' => 'Enable Lenco Payment Gateway',
            'default' => 'yes'
        ),
        'title' => array(
            'title' => 'Title',
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
            'default' => __('Pay with Lenco', 'woocommerce'),
            'desc_tip'    => false,
        ),
        'description' => array(
            'title' => 'Description',
            'type' => 'textarea',
            'description' => 'Description that the user sees during checkout.',
            'default' => 'Pay securely using your credit card with Lenco.',
        ),
        'public_key' => array(
            'title' => 'Lenco Public Key',
            'type' => 'text',
            'description' => 'Enter your Lenco public key.',
            'default' => 'pub-88dd921c0ecd73590459a1dd5a9343c77db0f3c344f222b9',
        ),
        'secrete_key' => array(
            'title' => 'Lenco Secrete Key',
            'type' => 'text',
            'description' => 'Enter your Lenco Secrete key.',
            'default' => '993bed87f9d592566a6cce2cefd79363d1b7e95af3e1e6642b294ce5fc8c59f6',
        ),
        'channels' => array(
            'title' => 'Payment Channels',
            'type' => 'multiselect',
            'description' => 'Select payment channels to be enabled (card, mobile-money).',
            'options' => array(
                'card' => 'Card (Visa, Mastercard)',
                'mobile-money' => 'Mobile Money',
            ),
            'default' => array('card', 'mobile-money'),
        ),
        'success_url' => array(
            'title' => 'Success Redirect URL',
            'type' => 'text',
            'description' => 'URL to redirect to after a successful payment.',
            'default' => '',
        ),
        'failure_url' => array(
            'title' => 'Failure Redirect URL',
            'type' => 'text',
            'description' => 'URL to redirect to after a failed payment.',
            'default' => '',
        ),
        'currency' => array(
            'title' => 'Currency',
            'type' => 'select',
            'description' => 'Select the currency for transactions.',
            'options' => array(
                'ZMW' => 'Zambian Kwacha',
                'EUR' => 'Euro',
                'GBP' => 'British Pound',
                'USD' => 'US Dollar',
                'ZAR' => 'South African Rand',
            ),
            'default' => 'ZMW',
        ),
        'environment' => array(
            'title' => 'Environment',
            'type' => 'select',
            'description' => 'Select the environment for transactions.',
            'options' => array(
                'production' => 'Production',
                'sandbox' => 'Sandbox',
            ),
            'default' => 'Sandbox',
        ),
    );
}
?>
