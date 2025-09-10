<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Lenco extends WC_Payment_Gateway {
    private $public_key;
    private $secret_key;
    private $channels;
    private $currency;
    private $environment;
    private $success_url;

    public function __construct() {
        $this->id = 'lenco';
        $this->icon = plugins_url('assets/icon.png', dirname(__FILE__));
        $this->method_title = 'Lenco Payment';
        $this->method_description = "Accept payments using Lenco's secure payment gateway.";
        $this->has_fields = true;
        $this->order_button_text = __('Pay with Lenco', 'lenco');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->channels = $this->get_option('channels');
        $this->currency = $this->get_option('currency', 'ZMW');
        $this->environment = $this->get_option('environment', 'sandbox');
        $this->success_url = $this->get_option('success_url');

        // Add action for saving settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = lenco_payment_gateway_settings();
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
    }

    public function get_success_redirect_url($order) {
        if (empty($this->success_url)) {
            return $this->get_return_url($order);
        }
        else {
            return $this->success_url;
        }
    }
}

?>
