<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
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
            'description' => 'Title that the user sees during checkout.',
            'default' => 'Lenco Payment',
            'desc_tip' => true,
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
            'default' => '',
        ),
        'secret_key' => array(
            'title' => 'Lenco Secret Key',
            'type' => 'text',
            'description' => 'Enter your Lenco secret key.',
            'default' => '',
        ),
        'channels' => array(
            'title' => 'Payment Channels',
            'type' => 'multiselect',
            'description' => 'Select payment channels to be enabled (card, mobile-money).',
            'options' => array(
                'card' => 'Card',
                'mobile-money' => 'Mobile Money',
            ),
            'default' => array('card', 'mobile-money'),
        ),
        'currency' => array(
            'title' => 'Currency',
            'type' => 'text',
            'description' => 'Currency code (e.g., USD, ZMW).',
            'default' => 'ZMW',
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
    );
}
?>
