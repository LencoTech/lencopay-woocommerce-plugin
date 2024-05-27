jQuery(document).ready(function($) {
    $('form.checkout').on('submit', function(e) {
        e.preventDefault(); // Prevent the default WooCommerce checkout form submission

        // Get the form data
        var form = $(this);
        var formData = form.serializeArray();
        var email = '';
        var amount = 0;
        var currency = 'ZMW';
        var customer = {};

        // Extract email, amount, and customer info from the form data
        formData.forEach(function(field) {
            if (field.name === 'billing_email') {
                email = field.value;
            }
            if (field.name === 'order_total') {
                amount = parseFloat(field.value) * 100; // Convert to smallest currency unit
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
            channels: ["mobile-money"],
            customer: customer,
            onSuccess: function(response) {
                // Handle successful payment
                const reference = response.reference;
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize() + '&lenco_reference=' + reference,
                    success: function() {
                        alert('Payment complete! Reference: ' + reference);
                        window.location.href = '/checkout/order-received/'; // Redirect to order received page
                    }
                });
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
