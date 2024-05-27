<?php
/**
 * Plugin Name: Lenco Pay
 * Description: A custom WooCommerce payment gateway using Lenco.
 * Version: 1.0.0
 * Author: Mateo
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include the settings file
require_once plugin_dir_path(__FILE__) . 'lenco-settings.php';

// Enqueue Lenco script and custom JS
function enqueue_lenco_scripts() {
    if (is_checkout()) {
        wp_enqueue_script('lenco-inline', 'https://pay.sandbox.lenco.co/js/v1/inline.js', array(), null, true);
        wp_enqueue_script('lenco-custom', plugins_url('custom-lenco.js', __FILE__), array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_lenco_scripts');

// Add the Lenco public key to a JavaScript variable
function localize_lenco_script() {
    if (is_checkout()) {
        $options = get_option('woocommerce_lenco_settings');
        $total = WC()->session->get('lenco_order_total', 0);
        
        // Adjust the total based on the currency if needed
        $currency = get_woocommerce_currency();
        if (in_array($currency, array('USD', 'EUR', 'GBP'))) {
            $total = $total * 100;
        }

        wp_localize_script('lenco-custom', 'lenco_params', array(
            'public_key' => $options['public_key'],
            'total_amount' => $total
        ));
    }
}
add_action('wp_enqueue_scripts', 'localize_lenco_script', 20);

// Change the Place Order button text
function change_place_order_button_text() {
    return 'Pay with Lenco';
}
add_filter('woocommerce_order_button_text', 'change_place_order_button_text');


add_action('woocommerce_checkout_create_order', 'get_total_cost', 20, 1);
function get_total_cost($order) {
    $total = $order->get_total();

    // Save the total in a session variable to access it in JavaScript
    WC()->session->set('lenco_order_total', $total);
}

// Override the WooCommerce checkout button to handle Lenco payment
function pay_with_lenco_button() {
    $options = get_option('woocommerce_lenco_settings');
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            $('form.checkout').on('submit', function(e) {
                e.preventDefault(); // Prevent the default WooCommerce checkout form submission

                // Get the form data
                var form = $(this);
                var formData = form.serializeArray();
                var email = '';
                var amount = lenco_params.total_amount;
                var currency = '<?php echo esc_js(get_option('woocommerce_currency')); ?>';
                var customer = {};

                // Extract email and customer info from the form data
                formData.forEach(function(field) {
                    if (field.name === 'billing_email') {
                        email = field.value;
                    }
                    if (field.name === 'billing_first_name') {
                        customer.firstName = field.value;
                    }
                    if (field.name === 'billing_last_name') {
                        customer.lastName = field.value;
                    }
                    if (field.name === 'billing_phone') {
                        customer.phone = field.value;
                    }
                });

                // Call the Lenco payment widget
                LencoPay.getPaid({
                    key: lenco_params.public_key,
                    reference: 'ref-' + Date.now(),
                    email: email,
                    amount: amount,
                    currency: currency,
                    channels: <?php echo json_encode($options['channels']); ?>,
                    customer: customer,
                    onSuccess: function(response) {
                        // Handle successful payment
                        const reference = response.reference;
                        //window.location.href = '<?php echo esc_js($options['success_url']); ?>';
                        console.log(response);
                    },
                    onError: function(errot) {
                        // Handle successful payment
                        console.log(error);
                        //window.location.href = '<?php echo esc_js($options['failure_url']); ?>';
                    },
                    onClose: function() {
                        alert('Payment was not completed, window closed.');
                    },
                    onConfirmationPending: function() {
                        alert('Your purchase will be completed when the payment is confirmed');
                    }
                });
            });
        });
    </script>
    <?php
}
add_action('woocommerce_review_order_before_submit', 'pay_with_lenco_button', 10);


add_action('woocommerce_checkout_update_order_meta', 'process_lenco_payment', 20, 1);
function process_lenco_payment($order_id) {
    if (isset($_POST['lenco_reference'])) {
        echo "Here now in processs payment";
        $order = wc_get_order($order_id);
        $reference = sanitize_text_field($_POST['lenco_reference']);
        $payment_status = sanitize_text_field($_POST['payment_status']);
        
        if ($payment_status === 'success') {
            $order->payment_complete($reference);
            $order->add_order_note('Lenco payment completed. Reference: ' . $reference);
        } else {
            $order->update_status('failed', 'Lenco payment failed. Reference: ' . $reference);
        }
    }
}

// Register Lenco as a WooCommerce payment gateway
function add_lenco_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_Lenco';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'add_lenco_gateway_class');

// Initialize Lenco gateway class
add_action('plugins_loaded', 'init_lenco_gateway_class');
function init_lenco_gateway_class() {
    class WC_Gateway_Lenco extends WC_Payment_Gateway {
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
    }
}


