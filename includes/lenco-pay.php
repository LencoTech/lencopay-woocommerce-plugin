<?php

require_once plugin_dir_path(__FILE__) . 'class-wc-lenco-payment-gateway.php';

// Enqueue Lenco script and custom JS
function enqueue_lenco_scripts() {
    if (is_checkout()) {
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

// Change the Place Order button text
function change_place_order_button_text() {
    return 'Pay with Lenco';
}
add_filter('woocommerce_order_button_text', 'change_place_order_button_text');

// Hook to handle LencoPay initiation
add_action('woocommerce_checkout_before_customer_details', 'initiate_lenco_payment_script');
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

                    // Call LencoPay payment widget
                    LencoPay.getPaid({
                        key: lenco_params.public_key,
                        reference: 'ref-' + Date.now(),
                        email: email,
                        amount: amount,
                        currency: currency,
                        customer: customer,
                        onSuccess: function(response) {
                            // Handle successful payment
                            const reference = response.reference;
                            console.log('Payment successful. Reference: ' + reference);
                            
                            // Create order via AJAX
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                data: {
                                    action: 'create_order',
                                    email: email,
                                    reference: reference,
                                    amount: amount,
                                    currency: currency,
                                    customer: customer
                                },
                                success: function(order_id) {
                                    // Redirect to thank you page or update order status
                                    window.location.href = '<?php echo wc_get_checkout_url(); ?>';
                                },
                                error: function(error) {
                                    console.error('Error creating order: ' + error);
                                    // Handle error scenario
                                }
                            });
                        },
                        onError: function(error) {
                            // Handle payment error
                            console.error('Payment error: ' + error.message);
                            // Notify the user or retry payment
                        },
                        onClose: function() {
                            // Handle when payment modal is closed
                            console.log('Payment modal closed');
                            // Notify the user or take appropriate action
                        },
                        onConfirmationPending: function() {
                            // Handle when payment is pending confirmation
                            console.log('Payment pending confirmation');
                        }
                    });
                }

                // Trigger payment initiation on Place Order button click
                $('body').on('click', '#place_order', function(e) {
                    e.preventDefault();
                    initiateLencoPayment();
                });
            });
        </script>
        <?php
    }
}

// AJAX action to create WooCommerce order
add_action('wp_ajax_create_order', 'create_order');
add_action('wp_ajax_nopriv_create_order', 'create_order');
function create_order() {
    if (!empty($_POST['email']) && !empty($_POST['reference']) && !empty($_POST['amount']) && !empty($_POST['currency'])) {
        // Get customer email, reference, amount, currency, and customer details
        $email = sanitize_email($_POST['email']);
        $reference = sanitize_text_field($_POST['reference']);
        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $customer = isset($_POST['customer']) ? $_POST['customer'] : array();

        // Create new order programmatically
        $order = wc_create_order();

        // Set customer information
        $order->set_billing_email($email);
        $order->set_currency($currency);

        // Add products from cart to order
        $cart_items = WC()->cart->get_cart();
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $product = wc_get_product($product_id);
            $order->add_product($product, $cart_item['quantity']);
        }

        // Set order total
        $order->set_total($amount);

        // Payment complete
        $order->payment_complete($reference);

        // Save order
        $order_id = $order->get_id();
        $order->save();

        // Return the order ID
        wp_send_json_success($order_id);
    } else {
        // Invalid request
        wp_send_json_error('Missing required parameters');
    }
}
?>


