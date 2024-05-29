<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WC_Lenco_Payment_Gateway extends WC_Payment_Gateway {
    public $id;
    public $icon;
    public $method_title;
    public $method_description;
    public $has_fields;
    public $title;
    public $description;
    public $public_key;
    public $secret_key;
    public $channels;
    public $currency;
    public $success_url;
    public $failure_url;
    public $form_fields;

    public function __construct() {
        $this->id = 'lenco';
        $this->icon = plugins_url('assets/icon.png', dirname(__FILE__));
        $this->method_title = 'Lenco Payment';
        $this->method_description = 'Allows payments using Lenco gateway.';
        $this->has_fields = true;
        $this->title = '';
        $this->description = '';
        $this->public_key = '';
        $this->secret_key  = '';
        $this->channels = array();
        $this->currency = 'ZMW'; 
        $this->success_url = home_url('/');
        $this->failure_url = home_url('/');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->channels = $this->get_option('channels');
        $this->currency = $this->get_option('currency');
        $this->success_url = $this->get_option('success_url');
        $this->failure_url = $this->get_option('failure_url');

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
}
?>