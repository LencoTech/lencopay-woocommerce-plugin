<?php

require_once plugin_dir_path(__FILE__) . 'class-wc-lenco-payment-gateway.php';

// Enqueue Lenco script and custom JS
function enqueue_lenco_scripts() {
    if (is_checkout() || is_cart()) {
        wp_enqueue_script('lenco-inline', 'https://pay.sandbox.lenco.co/js/v1/inline.js', array(), null, true);

        // Localize script with necessary data
        $options = get_option('woocommerce_lenco_settings');
        $total = WC()->cart->total;
        $currency = get_woocommerce_currency();

        if (in_array($currency, array('USD', 'EUR', 'GBP'))) {
            $total = $total * 100;
        }

        wp_localize_script('lenco-inline', 'lenco_params', array(
            'public_key' => $options['public_key'],
            'total_amount' => $total,
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_lenco_scripts');
add_action('enqueue_block_assets', 'enqueue_lenco_scripts');

// Change the Place Order button text
function change_place_order_button_text()
{
    return 'Pay with Lenco';
}
add_filter('woocommerce_order_button_text', 'change_place_order_button_text');

// Hook to handle LencoPay initiation
function initiate_lenco_payment_script() {
    if (is_checkout()) {
        global $woocommerce;
?>
<script type="text/javascript">
jQuery(function($) {
    // Function to initiate LencoPay payment
    function initiateLencoPayment() {
        var email = $('input#billing_email').val();
        var amount = lenco_params.total_amount;
        var currency = '<?php echo esc_js(get_option('woocommerce_currency')); ?>';
        var customer = {
            firstName: $('input#billing_first_name').val(),
            lastName: $('input#billing_last_name').val(),
            phone: $('input#billing_phone').val()
        };

        // Create order via AJAX before payment
        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: {
                action: 'create_order_before_payment',
                email: email,
                amount: amount,
                currency: currency,
                customer: customer
            },
            success: function(response) {
                console.log('Order created before payment: ', response.data);
                var order_id = response.data;
                // Call LencoPay payment widget after order creation
                LencoPay.getPaid({
                    key: lenco_params.public_key,
                    reference: 'ref-' + Date.now(),
                    email: email,
                    amount: amount,
                    currency: currency,
                    customer: customer,
                    onSuccess: function(response) {
                        const reference = response.reference;
                        console.log('Payment successful ', { response });

                        // Update order after payment via AJAX
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            data: {
                                action: 'update_order_after_payment',
                                order_id: order_id,
                                reference: reference
                            },
                            success: function(response) {
                                console.log('Order updated after payment ', { response });
                                // Add success redirect url here
                            },
                            error: function(error) {
                                console.error('Error updating order: ' + error);
                                alert('Error updating order, please contact website administrator');
                                
                            }
                        });
                    },
                    onError: function(error) {
                        console.error('Payment error: ' + error.message);
                        // Add failed redirect url here
                    },
                    onClose: function() {
                        console.log('Payment modal closed');
                    },
                    onConfirmationPending: function() {
                        console.log('Payment pending confirmation');
                    }
                });
            },
            error: function(error) {
                alert('Error creating order before payment, please try again');
            }
        });
    }

    // Trigger payment initiation on Place Order button click for classic checkout
    $('body').on('click', '#place_order', function(e) {
        e.preventDefault();
        initiateLencoPayment();
    });

    // Trigger payment initiation for block checkout
    wp.hooks.addAction('experimental/woocommerce-blocks-checkout/express-payment-request-completed', 'my-namespace', function() {
        initiateLencoPayment();
    });
});
</script>
<?php
    }
}
add_action('woocommerce_checkout_before_customer_details', 'initiate_lenco_payment_script');
add_action('woocommerce_blocks_enqueue_cart_and_checkout_scripts', 'initiate_lenco_payment_script');

// AJAX action to create WooCommerce order before payment
add_action('wp_ajax_create_order_before_payment', 'create_order_before_payment');
add_action('wp_ajax_nopriv_create_order_before_payment', 'create_order_before_payment');
function create_order_before_payment() {
    if (!empty($_POST['email']) && !empty($_POST['amount']) && !empty($_POST['currency'])) {
        // Get customer email, amount, currency, and customer details
        $email = sanitize_email($_POST['email']);
        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $customer = isset($_POST['customer']) ? $_POST['customer'] : array();

        // Create new order programmatically
        $order = wc_create_order();

        // Set customer information
        $order->set_billing_email($email);
        $order->set_currency($currency);

        // Set order total
        $order->set_total($amount);

        // Save order
        $order->save();
        $order_id = $order->get_id();

        // Return the order ID
        wp_send_json_success($order_id);
    } else {
        // Invalid request
        wp_send_json_error('Missing required parameters');
    }
}

add_action('wp_ajax_update_order_after_payment', 'update_order_after_payment');
add_action('wp_ajax_nopriv_update_order_after_payment', 'update_order_after_payment');
function update_order_after_payment() {
    if (!empty($_POST['order_id']) && !empty($_POST['reference'])) {
        // Get order ID and payment reference
        $order_id = intval($_POST['order_id']);
        $reference = sanitize_text_field($_POST['reference']);

        // Load the order
        $order = wc_get_order($order_id);

        if ($order) {
            // Mark the order as paid and complete
            $order->payment_complete($order_id);
            $order->add_order_note('Payment completed via LencoPay. Reference: ' . $order_id);

            // Save order
            $order->save();

            // Return success response
            wp_send_json_success('Order updated after payment');
        } else {
            // Order not found
            wp_send_json_error('Order not found');
        }
    } else {
        // Invalid request
        wp_send_json_error('Missing required parameters');
    }
}
?>