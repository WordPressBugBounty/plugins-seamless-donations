<?php
/**
 * Seamless Donations by David Gewirtz, adopted from Allen Snook
 *
 * Lab Notes: http://zatzlabs.com/lab-notes/
 * Plugin Page: http://zatzlabs.com/seamless-donations/
 * Contact: http://zatzlabs.com/contact-us/
 *
 * Copyright (c) 2015-2020 by David Gewirtz
 *
 */

function seamless_donations_init_stripe($api_key) {
    \Stripe\Stripe::setAppInfo(
        'WordPress SeamlessDonationsStripe',
        get_option('dgx_donate_active_version'),
        'https://zatzlabs.com',
        'pp_partner_HLBzVKtlNzaGrU'           // Used by Stripe to identify your plugin
    );

    \Stripe\Stripe::setApiKey($api_key);
    \Stripe\Stripe::setApiVersion('2017-06-05');

    wp_enqueue_script('stripe', 'https://js.stripe.com/v3/');

    if (isset($_SERVER['HTTPS'])) {
        // Present an error to the user
    }
}

function seamless_donations_stripe_get_payment_intent($payment_id) {
    $intent = \Stripe\PaymentIntent::retrieve(
        $payment_id,
        []
    );
    return $intent;
}

function seamless_donations_stripe_get_invoice_from_payment_intent($payment_id) {
    $intent     = seamless_donations_stripe_get_payment_intent($payment_id);
    $invoice_id = $intent->invoice;
    return $invoice_id;
}

function seamless_donations_stripe_is_zero_decimal_currency($currency) {
    $zeroes = array(
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    );
    if (in_array($currency, $zeroes)) {
        return true;
    } else {
        return false;
    }
}

function seamless_donations_stripe_get_invoice_list_from_payment_intents($days = 30) {
    $intent_array = array();
    $intent_list  = \Stripe\PaymentIntent::all([
        'created' => [
            // Check for subscriptions created in the last year.
            'gte' => time() - $days * 24 * 60 * 60,
        ],
    ]);
    foreach ($intent_list->autoPagingIterator() as $intent) {
        if (isset($intent->invoice)) {
            $invoice_id                = $intent->invoice;
            $subscription_id           = seamless_donations_stripe_get_subscription_from_invoice($invoice_id);
            $intent_array[$invoice_id] = $subscription_id;
        }
    }
    return $intent_array;
}

function seamless_donations_stripe_get_invoice($invoice_id) {
    $invoice = \Stripe\Invoice::retrieve(
        $invoice_id,
        []
    );
    return $invoice;
}

function seamless_donations_stripe_get_subscription_from_invoice($invoice_id) {
    $invoice         = seamless_donations_stripe_get_invoice($invoice_id);
    $subscription_id = $invoice->subscription;
    return $subscription_id;
}

function seamless_donations_stripe_get_invoice_list_from_subscription($subscription_id, $days = 30) {
    $invoice_array = array();
    $invoice_list  = \Stripe\Invoice::all([
        'created'      => [
            // Check for subscriptions created in the last year.
            'gte' => time() - $days * 24 * 60 * 60,
        ],
        'subscription' => $subscription_id,
    ]);
    foreach ($invoice_list->autoPagingIterator() as $invoice) {
        $reason                     = $invoice->billing_reason;
        $invoice_id                 = $invoice->id;
        $invoice_array[$invoice_id] = $reason;
    }
    return $invoice_array;
}

function seamless_donations_stripe_get_subscription($subscription_id) {
    $subscription = \Stripe\Subscription::retrieve(
        $subscription_id,
        []
    );
    return $subscription;
}

function seamless_donations_stripe_get_latest_invoice_from_subscription($subscription_id) {
    $subscription = seamless_donations_stripe_get_subscription($subscription_id);
    $latest       = $subscription->latest_invoice;
    return $latest;
}

function seamless_donations_stripe_get_first_invoice_from_subscription($subscription_id, $days = 30) {
    $list = seamless_donations_stripe_get_invoice_list_from_subscription($subscription_id, $days);
    foreach ($list as $invoice_id => $status) {
        if ($status == 'subscription_update') {
            return $invoice_id;
        }
        if ($status == 'subscription_create') {
            return $invoice_id;
        }
    }
    return false;
}

function seamless_donations_stripe_is_first_invoice_of_subscription($invoice_id, $days = 30) {
    $subscription_id = seamless_donations_stripe_get_subscription_from_invoice($invoice_id);
    $list            = seamless_donations_stripe_get_invoice_list_from_subscription($subscription_id, $days);
    if ($list[$invoice_id] == 'subscription_update') {
        return true;
    }
    return false;
}

function seamless_donations_stripe_get_event_history_json($days = 1) {
    dgx_donate_debug_log('Entering Stripe event scan');
    $event_log     = '';
    $currency_code = get_option('dgx_donate_currency');
    $server_mode   = get_option('dgx_donate_stripe_server');
    if ($server_mode == 'LIVE') {
        $stripe_secret_key = get_option('dgx_donate_live_stripe_secret_key');
        $endpoint_secret   = get_option('dgx_donate_live_webhook_stripe_secret_key');
    } else {
        $stripe_secret_key = get_option('dgx_donate_test_stripe_secret_key');
        //$stripe_secret_key = get_option('dgx_donate_test_stripe_api_key');
        $endpoint_secret = get_option('dgx_donate_test_webhook_stripe_secret_key');
    }

    // Set your secret key. Remember to switch to your live secret key in production!
    // See your keys here: https://dashboard.stripe.com/account/apikeys
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    dgx_donate_debug_log('Stripe API key set');

    // 'dgx_donate_stripe_event_type_to_scan'
    $stripe_event_query_array = [
        //'type'    => 'checkout.session.completed',
        'created' => [
            // Check for events created in the last n days.
            'gte' => time() - 24 * 60 * 60 * $days,
        ],
    ];

    $stripe_event_type_to_scan = get_option('dgx_donate_stripe_event_type_to_scan');
    if ($stripe_event_type_to_scan != 'ALL') {
        $stripe_event_query_array['type'] = $stripe_event_type_to_scan;
    }

    $events = \Stripe\Event::all($stripe_event_query_array);

    dgx_donate_debug_log('Stripe events object created');

    foreach ($events->autoPagingIterator() as $event) {
        if ($event_log != '') {
            $event_log .= PHP_EOL . PHP_EOL;
        }
        $stripe_event_id             = $event->id;
        $stripe_event_created        = $event->created;
        $stripe_event_created_string = date('m/d/Y H:i:s', $stripe_event_created);
        $stripe_event_livemode       = $event->livemode;
        $stripe_event_type           = $event->type;

        $event_log .= "EVENT ID: " . $stripe_event_id . PHP_EOL;
        $event_log .= "EVENT TYPE: " . $stripe_event_type . PHP_EOL;
        $event_log .= "EVENT CREATED: " . $stripe_event_created_string . PHP_EOL;
        $event_log .= "LIVE MODE: " . $stripe_event_livemode . PHP_EOL;

        $stripe_retrieved_event = \Stripe\Event::retrieve(
            $stripe_event_id,
            []
        );

        $json = $stripe_retrieved_event->toJSON();
        // I would modify dgx_donate_debug_log('Checking for donations'); to explicitly log the count of $events->data and the first element
        $event_log .= $json;
    }
    return $event_log;
}

function seamless_donations_redirect_to_stripe($post_data, $api_key, $notify_url, $cancel_url) {
    $session = false;
    dgx_donate_debug_log('Preparing Stripe donation description...');
    $desc = seamless_donations_build_donation_description($post_data);

    dgx_donate_debug_log('Preparing redirect to Stripe...');
    dgx_donate_debug_log('-- Using API key: ' . seamless_donations_obscurify_stripe_key($api_key));
    \Stripe\Stripe::setApiKey($api_key);
    dgx_donate_debug_log('-- Setting API version to 2020-03-02');
	\Stripe\Stripe::setApiVersion('2020-03-02');
    dgx_donate_debug_log('Completed setting Stripe API key and version.');

    $billing_address_collection = get_option('dgx_donate_stripe_billing_address');

    // prep currency special cases
    $currency = get_option('dgx_donate_currency');
    $amount   = $post_data['AMOUNT'];
    if (!seamless_donations_stripe_is_zero_decimal_currency($currency)) {
        $amount = $amount * 100;
    }
    $currency = strtolower($currency); // stripe prefers lower case

    if ($post_data['REPEATING'] != '') {
        // this is a recurring donation
        dgx_donate_debug_log("Checking for repeat. REPEAT value is [" . $post_data['AMOUNT'] . "].");
        $donation = [
            'billing_address_collection' => $billing_address_collection,
            'payment_method_types'       => ['card'],
            'line_items'                 => [
                [
                    'price_data'  => [
                        'currency'     => $currency,
                        'product_data' => [
                            'name' => $desc,
                        ],
                        'recurring'    => [
                            'interval' => 'month',
                        ],
                        'unit_amount'  => $amount,
                    ],
                    'quantity'    => 1,
                    'description' => $desc,
                ],
            ],
            'mode'                       => 'subscription',
            'metadata'                   => [
                'sd_session_id' => $post_data['SESSIONID'],
            ],
            'success_url'                => $post_data['RETURN'],
            'cancel_url'                 => $cancel_url,
            'customer_email'             => $post_data["EMAIL"],
        ];
    } else {
        $donation = [
            'billing_address_collection' => $billing_address_collection,
            'payment_method_types'       => ['card'],
            'line_items'                 => [
                [
                    'name'     => $desc,
                    //'description' => $desc,
                    'amount'   => $amount,
                    'currency' => $currency,
                    'quantity' => 1,
                ],
            ],
            'metadata'                   => [
                'sd_session_id' => $post_data['SESSIONID'],
            ],
            'success_url'                => $post_data['RETURN'],
            'cancel_url'                 => $cancel_url,
            'customer_email'             => $post_data["EMAIL"],
            'submit_type'                => 'donate',
        ];
    }
    $donation = apply_filters('seamless_donations_stripe_checkout_data', $donation);

    // https://www.php.net/manual/en/class.exception.php
    try {
        // Use Stripe's library to make requests
        $session = \Stripe\Checkout\Session::create($donation);
    } catch (\Stripe\Exception\CardException $e) {
        // Since it's a decline, \Stripe\Exception\CardException will be caught
        dgx_donate_debug_log('Status is:' . $e->getHttpStatus());
        dgx_donate_debug_log('Type is:' . $e->getError()->type);
        dgx_donate_debug_log('Code is:' . $e->getError()->code);
        // param is '' in this case
        dgx_donate_debug_log('Param is:' . $e->getError()->param);
        dgx_donate_debug_log('Message is:' . $e->getError()->message);
    } catch (\Stripe\Exception\RateLimitException $e) {
        dgx_donate_debug_log("Too many requests made to the API too quickly");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Too many requests made to the API too quickly
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        dgx_donate_debug_log("Invalid parameters were supplied to Stripe API");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Invalid parameters were supplied to Stripe's API
    } catch (\Stripe\Exception\AuthenticationException $e) {
        dgx_donate_debug_log("Authentication with Stripe API failed");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        dgx_donate_debug_log("Network communication with Stripe failed");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Network communication with Stripe failed
    } catch (\Stripe\Exception\ApiErrorException $e) {
        dgx_donate_debug_log("Stripe API error exception.");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Display a very generic error to the user, and maybe send
        // yourself an email
    } catch (Exception $e) {
        dgx_donate_debug_log("A Stripe invocation failure occurred unrelated to Stripe functionality.");
        dgx_donate_debug_log('-- Stripe message is: ' . $e->getMessage());
        // Something else happened, completely unrelated to Stripe
    }

    //    https://stackoverflow.com/questions/17750143/catching-stripe-errors-with-try-catch-php-method
    //    $body = $e->getJsonBody();
    //    $err  = $body['error'];
    //
    //    print('Status is:' . $e->getHttpStatus() . "\n");
    //    print('Type is:' . $err['type'] . "\n");
    //    print('Code is:' . $err['code'] . "\n");
    //    // param is '' in this case
    //    print('Param is:' . $err['param'] . "\n");
    //    print('Message is:' . $err['message'] . "\n");

    return $session;
}

function seamless_donations_stripe_js_redirect($session) {
    $stripe_mode = get_option('dgx_donate_stripe_server');
    if ($stripe_mode == 'LIVE') {
        $api_key = get_option('dgx_donate_live_stripe_api_key');
    } else {
        $api_key = get_option('dgx_donate_test_stripe_api_key');
    }

    dgx_donate_debug_log('Entering stripe js test with mode ' . $stripe_mode);
    dgx_donate_debug_log('Stripe session id: ' . $session['id']);

    ?>
    <script src='https://js.stripe.com/v3/?ver=5.4.1'></script>
    <script>
        console.log('JS Stripe redirect');
        try {
            var stripe = Stripe(<?php echo '\'' . esc_attr($api_key) . '\''; ?>);
            stripe.redirectToCheckout({
                // Make the id field from the Checkout Session creation API response
                // available to this file, so you can provide it as parameter here
                // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
                <?php
                echo 'sessionId: \'' . esc_attr($session['id']) . '\'';
                ?>
            }).then(function (result) {
                if (result.error) {
                    // Error scenario 1
                    // If `redirectToCheckout` fails due to a browser or network
                    // error, display the localized error message to your customer.
                    // todo = grab error, serialize it to JSON and send back to debug log
                    alert("An error has occurred. " + result.error.message);
                }
            }).catch(function (error) {
                if (result.error) {
                    // Error scenario 2
                    // If the promise throws an error
                    alert("We are experiencing issues connecting to our"
                        + " payments provider. " + error);
                }
            });
        } catch (error) {
            // Error scenario 3
            // If there is no internet connection at all
            alert("We are experiencing issues connecting to our"
                + " payments provider. You have not been charged. Please check"
                + " your internet connection and try again. If the problem"
                + " persists please contact us.");
        }
    </script>
    <?php
}

function seamless_donations_stripe_check_for_successful_transaction() {
    // https://stripe.com/docs/payments/checkout/accept-a-payment#payment-success
    // https://stripe.com/docs/cli/flags

    dgx_donate_debug_log('Entering Stripe checking for successful transaction');

    $donation_succeeded = false;
    if (isset($_GET["sessionid"])) {
        $donation_session_id = sanitize_text_field( $_GET["sessionid"] );
        if ($donation_session_id == '') {
            $donation_session_id = 'NONE';
        }
    } else {
        $donation_session_id = 'NONE';
    }
    dgx_donate_debug_log('Provided donation session ID: ' . $donation_session_id);

    $currency_code = get_option('dgx_donate_currency');
    $server_mode   = get_option('dgx_donate_stripe_server');
    if ($server_mode == 'LIVE') {
        $stripe_secret_key = get_option('dgx_donate_live_stripe_secret_key');
        $endpoint_secret   = get_option('dgx_donate_live_webhook_stripe_secret_key');
    } else {
        $stripe_secret_key = get_option('dgx_donate_test_stripe_secret_key');
        //$stripe_secret_key = get_option('dgx_donate_test_stripe_api_key');
        $endpoint_secret = get_option('dgx_donate_test_webhook_stripe_secret_key');
    }

    // See your keys here: https://dashboard.stripe.com/account/apikeys
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    dgx_donate_debug_log('Stripe API key set');

    $events = \Stripe\Event::all([
        'type'    => 'checkout.session.completed',
        'created' => [
            // Check for events created in the last 24 hours.
            'gte' => time() - 24 * 60 * 60,
        ],
    ]);

    dgx_donate_debug_log('Checking for donations');

    foreach ($events->autoPagingIterator() as $event) {
        $stripe_session = $event->data->object;
        // todo if breaks, look at alternate way to get session: https://stripe.com/docs/api/checkout/sessions/retrieve
        $stripe_session_id = $stripe_session->id;
        $stripe_mode       = $stripe_session->mode;
        $sd_session_id     = $stripe_session->metadata['sd_session_id'];
        dgx_donate_debug_log('== Stripe Metadata Donation Session ID: ' . $sd_session_id);

        dgx_donate_debug_log('== Stripe Sesssion ID: ' . $stripe_session_id);
        dgx_donate_debug_log('== Stripe Mode: ' . $stripe_mode);
        if ($stripe_mode == 'payment') {
            // record a payment intent ID if a one-off donation
            $stripe_transaction_id = $stripe_session->payment_intent;
            dgx_donate_debug_log('== Stripe Payment Intent: ' . $stripe_transaction_id);
        } else {
            // record an invoice ID if a subscription
            $subscription_id       = $stripe_session->subscription;
            $stripe_transaction_id = seamless_donations_stripe_get_latest_invoice_from_subscription($subscription_id);
            seamless_donations_add_audit_string('STRIPE-SUBSCRIPTION-' . $subscription_id, $donation_session_id);
            dgx_donate_debug_log('== Stripe Subscription ID: ' . $subscription_id);
            dgx_donate_debug_log('== Stripe Transaction ID: ' . $stripe_transaction_id);
        }

        if ($donation_session_id == 'NONE') {
            dgx_donate_debug_log('== Donation Session ID showing as NONE');
            $audit_data = seamless_donations_get_audit_option('STRIPE-COMPLETE-' . $sd_session_id);

            if ($audit_data == false) {
                dgx_donate_debug_log('== Audit data showing as false');
                $donation_session_id = $sd_session_id;
                dgx_donate_debug_log('== Assigning ' . $sd_session_id . 'as donation session ID.');
                $_GET["sessionid"] = $sd_session_id;
                dgx_donate_debug_log('== Populating transaction global session ID');
            } else {
                dgx_donate_debug_log('== Audit data not showing as false');
            }
        }
        dgx_donate_debug_log('== Donation Session ID: ' . $donation_session_id);

        if ($sd_session_id == $donation_session_id) {
            $donation_succeeded = true;
            dgx_donate_debug_log('== Donation succeeded on Stripe');
            break;
        }
    }

    if ($donation_succeeded) {
        seamless_donations_process_confirmed_purchase('STRIPE', $currency_code, $donation_session_id, $stripe_transaction_id, $stripe_session);
        seamless_donations_add_audit_string('STRIPE-COMPLETE-' . $sd_session_id, $stripe_transaction_id);
        dgx_donate_debug_log('== Donation complete in Seamless Donations');
    } else {
        dgx_donate_debug_log('Donation not showing as succeeded');
    }
    return 'PASS';
}

function seamless_donations_stripe_poll_last_months_transactions() {
    dgx_donate_debug_log('Entering Stripe polling for unrecorded transactions');

    $server_mode = get_option('dgx_donate_stripe_server');
    if ($server_mode == 'LIVE') {
        $stripe_secret_key = get_option('dgx_donate_live_stripe_secret_key');
    } else {
        $stripe_secret_key = get_option('dgx_donate_test_stripe_secret_key');
    }

    // Set your secret key. Remember to switch to your live secret key in production!
    // See your keys here: https://dashboard.stripe.com/account/apikeys
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    dgx_donate_debug_log('Stripe API key set');

    $invoice_list = seamless_donations_stripe_get_invoice_list_from_payment_intents();

    foreach ($invoice_list as $invoice_id => $subscription_id) {
        $donation_id = seamless_donations_get_donations_by_meta('_dgx_donate_transaction_id', $invoice_id, 1);

        if (count($donation_id) == 0) {
            // We haven't seen this transaction ID already

            $original_session_id = seamless_donations_get_audit_option('STRIPE-SUBSCRIPTION-' . $subscription_id);
            if ($original_session_id !== false) {
                // we'll want to copy the contents of the session, pull out data for the call to
                // seamless_donations_process_confirmed_purchase, create a new session id
                // and write the old session data with the new ID to the audit table
                // this for any new subscription entry

                $original_session = seamless_donations_get_audit_option($original_session_id);
                $new_session_id   = seamless_donations_get_guid('sd'); // UUID on server
                seamless_donations_update_audit_option($new_session_id, $original_session);

                dgx_donate_debug_log('Found new recurring donation on Stripe');
                dgx_donate_debug_log('New session ID:' . $new_session_id);

                $currency         = $original_session['CURRENCY'];
                $transaction_data = seamless_donations_stripe_get_invoice($invoice_id);
                seamless_donations_process_confirmed_purchase('STRIPE', $currency, $new_session_id, $invoice_id, $transaction_data);
            }
            $a = 1;
        }
    }

    return 'PASS';
}

function seamless_donations_stripe_sd_5021_fix_uninvoiced_donation_subscriptions() {
    // this should only happen once as part of the Stripe update for 5.0.21
    // initiate Stripe

    $run_update  = false;
    $server_mode = get_option('dgx_donate_stripe_server');
    if ($server_mode == 'LIVE') {
        $stripe_secret_key = get_option('dgx_donate_live_stripe_secret_key');
        $stripe_updated    = get_option('dgx_donate_5021_stripe_invoices_live');
        if (!$stripe_updated) {
            $run_update     = true;
            $plugin_version = 'sd5021';
            update_option('dgx_donate_5021_stripe_invoices_live', $plugin_version);
        }
    } else {
        $stripe_secret_key = get_option('dgx_donate_test_stripe_secret_key');
        $stripe_updated    = get_option('dgx_donate_5021_stripe_invoices_test');
        if (!$stripe_updated) {
            $run_update     = true;
            $plugin_version = 'sd5021';
            update_option('dgx_donate_5021_stripe_invoices_test', $plugin_version);
        }
    }
    if (!$run_update) return;

    \Stripe\Stripe::setApiKey($stripe_secret_key);

    // initiate donation scan
    $count     = -1; // all
    $post_type = 'donation';
    $args      = array(
        'numberposts' => $count,
        'post_type'   => $post_type,
        'orderby'     => 'post_date',
        'order'       => 'DESC',
    );
    // scann all historical donations
    $my_donations = get_posts($args);

    foreach ($my_donations as $donation) {
        $donation_id     = $donation->ID;
        $subscription_id = '';
        $invoice_id      = '';

        // fix the transaction_ids in each donation record
        $transaction_id = get_post_meta($donation_id, '_dgx_donate_transaction_id', true);
        if (strpos($transaction_id, 'sub_', 0) !== false) {
            $subscription_id = $transaction_id;
            $invoice_id      = seamless_donations_stripe_get_first_invoice_from_subscription($subscription_id, 365);
            update_post_meta($donation_id, '_dgx_donate_transaction_id', $invoice_id);
        }
        if (strpos($transaction_id, 'in_', 0) !== false) {
            $invoice_id      = $transaction_id;
            $subscription_id = seamless_donations_stripe_get_subscription_from_invoice($invoice_id);
        }
        if ($transaction_id == NULL or $transaction_id == '') {
            $method1   = get_post_meta($donation_id, '_dgx_donate_payment_method', true);
            $method2   = get_post_meta($donation_id, '_dgx_donate_payment_processor', true);
            $repeating = get_post_meta($donation_id, '_dgx_donate_repeating', true);

            if ($method1 == 'STRIPE' and $method2 == 'STRIPE' and $repeating == 'on') {
                $stripe_data     = get_post_meta($donation_id, '_dgx_donate_payment_processor_data', true);
                $subscription_id = $stripe_data->subscription;
                $invoice_id      = seamless_donations_stripe_get_first_invoice_from_subscription($subscription_id, 365);
                update_post_meta($donation_id, '_dgx_donate_transaction_id', $invoice_id);
            }
        }

        // now fix the audit index for the stripe subscription
        if ($invoice_id != '') {
            $session_id = get_post_meta($donation_id, '_dgx_donate_session_id', true);
            $audit_key  = 'STRIPE-SUBSCRIPTION-' . $subscription_id;
            if (seamless_donations_get_audit_option($audit_key) == false) {
                // doesn't exist, so add
                seamless_donations_add_audit_string($audit_key, $session_id);
            }
        }
    }
}
