<?php
//lenco-pay.php
require_once plugin_dir_path(__FILE__) . 'class-wc-lenco-payment-gateway.php';

// Enqueue Lenco script and custom JS
function enqueue_lenco_scripts() {
    if (is_checkout() || is_cart()) {
        $options = get_option('woocommerce_lenco_settings');
        $environment = $options['environment'] === 'production' ? 'https://pay.lenco.co/js/v1/inline.js' : 'https://pay.sandbox.lenco.co/js/v1/inline.js';

        wp_enqueue_script('lenco-inline', $environment, array(), null, true);

        wp_localize_script('lenco-inline', 'lenco_params', array(
            'public_key' => $options['public_key'],
            'success_url' => $options['success_url'],
            'currency' => $options['currency'],
            'channels' => $options['channels'],
            'total_amount' => WC()->cart->total,
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_lenco_scripts');

// Hook to handle LencoPay initiation
function initiate_lenco_payment_script() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                function initiateLencoPayment() {
                    let billing_email = $('input#billing_email').val();

                    if (!billing_email || !lenco_params.total_amount) {
                        alert('Please complete all required fields.');
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: {
                            action: 'create_order_before_payment',
                            billing_email: billing_email,
                            amount: lenco_params.total_amount,
                            currency: lenco_params.currency,
                        },
                        success: function(response) {
                            if (!response.success) {
                                alert(typeof response.data === "string" ? response.data : 'Failed to create order. Please try again.');
                            }
                            else {
                                openPaymentWidget(response.data, billing_email);
                            }
                        },
                        error: function() {
                            alert('Failed to create order. Please try again.');
                        }
                    });
                }

                function openPaymentWidget(order_id, billing_email) {
                    LencoPay.getPaid({
                        key: lenco_params.public_key,
                        reference: 'ref-' + Date.now(),
                        email: billing_email,
                        amount: lenco_params.total_amount,
                        currency: lenco_params.currency,
                        channels: lenco_params.channels.length === 0 ? undefined : lenco_params.channels,
                        onSuccess: function(response) {
                            onLencoPaySuccess(order_id, response);
                        },
                        onError: function() {
                            alert('Payment failed. Please try again.');
                        },
                        onClose: function() {
                            console.log('Payment modal closed');
                        }
                    });
                }

                function onLencoPaySuccess(order_id, lencoPayResponse) {
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: {
                            action: 'update_order_after_payment',
                            order_id: order_id,
                            reference: lencoPayResponse.reference,
                        },
                        success: function(response) {
                            if (!response.success) {
                                alert(typeof response.data === "string" ? response.data : 'An Error Occurred.');
                            }
                            else {
                                window.location.href = response.data.redirect_url;
                            }
                        }
                    });
                }

                $('body').on('click', '#place_order', function(e) {
                    // Check if the selected payment method is Lenco
                    if ($('input[name="payment_method"]:checked').val() === 'lenco') {
                        e.preventDefault();
                        initiateLencoPayment();
                    }
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

function create_order_before_payment()
{
    if (!empty($_POST['billing_email']) && !empty($_POST['amount']) && !empty($_POST['currency'])) {
        $order = wc_create_order();

        // Set billing information
        $order->set_billing_first_name(sanitize_text_field($_POST['billing_first_name']));
        $order->set_billing_last_name(sanitize_text_field($_POST['billing_last_name']));
        $order->set_billing_company(sanitize_text_field($_POST['billing_company']));
        $order->set_billing_address_1(sanitize_text_field($_POST['billing_address_1']));
        $order->set_billing_address_2(sanitize_text_field($_POST['billing_address_2']));
        $order->set_billing_city(sanitize_text_field($_POST['billing_city']));
        $order->set_billing_state(sanitize_text_field($_POST['billing_state']));
        $order->set_billing_postcode(sanitize_text_field($_POST['billing_postcode']));
        $order->set_billing_country(sanitize_text_field($_POST['billing_country']));
        $order->set_billing_phone(sanitize_text_field($_POST['billing_phone']));
        $order->set_billing_email(sanitize_email($_POST['billing_email']));

        // Set shipping information if available
        if (!empty($_POST['shipping_first_name'])) {
            $order->set_shipping_first_name(sanitize_text_field($_POST['shipping_first_name']));
        }
        if (!empty($_POST['shipping_last_name'])) {
            $order->set_shipping_last_name(sanitize_text_field($_POST['shipping_last_name']));
        }
        if (!empty($_POST['shipping_company'])) {
            $order->set_shipping_company(sanitize_text_field($_POST['shipping_company']));
        }
        if (!empty($_POST['shipping_address_1'])) {
            $order->set_shipping_address_1(sanitize_text_field($_POST['shipping_address_1']));
        }
        if (!empty($_POST['shipping_address_2'])) {
            $order->set_shipping_address_2(sanitize_text_field($_POST['shipping_address_2']));
        }
        if (!empty($_POST['shipping_city'])) {
            $order->set_shipping_city(sanitize_text_field($_POST['shipping_city']));
        }
        if (!empty($_POST['shipping_state'])) {
            $order->set_shipping_state(sanitize_text_field($_POST['shipping_state']));
        }
        if (!empty($_POST['shipping_postcode'])) {
            $order->set_shipping_postcode(sanitize_text_field($_POST['shipping_postcode']));
        }
        if (!empty($_POST['shipping_country'])) {
            $order->set_shipping_country(sanitize_text_field($_POST['shipping_country']));
        }

        // Add items to the order
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            $product = wc_get_product($product_id);
            $category_ids = $product->get_category_ids();

            $order->add_product($product, $quantity);

            // Add order note for category information
            foreach ($category_ids as $category_id) {
                $category = get_term($category_id, 'product_cat');
                $order->add_order_note('Product: ' . $product->get_name() . ', Category: ' . $category->name . ', Quantity: ' . $quantity);
            }
        }

        // Set the order amount and currency
        $order->set_currency(sanitize_text_field($_POST['currency']));
        $order->set_total(floatval($_POST['amount']));

        // Save the order
        $order->save();

        // Clear the cart
        WC()->cart->empty_cart();

        $order_id = $order->get_id();
        wp_send_json_success($order_id);
    } else {
        wp_send_json_error('Missing required parameters');
    }
}

add_action('wp_ajax_update_order_after_payment', 'update_order_after_payment');
add_action('wp_ajax_nopriv_update_order_after_payment', 'update_order_after_payment');
function update_order_after_payment()
{
    if (!empty($_POST['order_id'])) {
        // Get order ID and payment reference
        $order_id = $_POST['order_id'];
        $order = wc_get_order($order_id);

        if ($order) {
            $order->payment_complete($order_id);
            $order->update_status('processing');
            $order->add_order_note('Payment completed via LencoPay. Reference: ' . $order_id);
            WC()->cart->empty_cart();
            $order->save();
            // wp_send_json_success('Order updated after payment');
            $gateway = WC()->payment_gateways()->payment_gateways()['lenco'];
            $redirect_url = $gateway->get_success_redirect_url($order);
            wp_send_json_success(array(
                'redirect_url' => $redirect_url,
            ));
        } else {
            wp_send_json_error('Order not found');
        }
    } else {
        wp_send_json_error('Missing required parameters');
    }
}
?>
