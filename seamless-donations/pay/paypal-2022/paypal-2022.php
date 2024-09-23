<?php
/*
 * Seamless Donations by David Gewirtz, adopted from Allen Snook
 *
 * Lab Notes: http://zatzlabs.com/lab-notes/
 * Plugin Page: http://zatzlabs.com/seamless-donations/
 * Contact: http://zatzlabs.com/contact-us/
 *
 * Copyright (c) 2015-2022 by David Gewirtz
 *
 */

add_filter( 'seamless_donations_form_section_order', 'seamless_donations_paypal_form_section_order' );

function seamless_donations_paypal_form_section_order( $form_data ) {
	$payment_gateway = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_gateway == 'PAYPAL2022' ) {
		dgx_donate_debug_log( 'Inside form filter' );
		$form_data['action'] = $form_data['action'] . '?noshow&paypal2022';
		// was	$form_data['action'] = $form_data['action'] . '?noshow';
		dgx_donate_debug_log( '$form_data[action] = ' . $form_data['action'] );
		dgx_donate_debug_log( 'Leaving form filter' );
	}

	return $form_data;
}

function seamless_donations_paypal2022_js_redirect( $session ) {
	$data_array                 = array();
	$data_array['gateway_mode'] = get_option( 'dgx_donate_paypal_server' );
	if ( $data_array['gateway_mode'] == 'LIVE' ) {
		$data_array['paypal_client_id'] = get_option( 'dgx_donate_paypal_client_live' );
		$data_array['paypal_secret_id'] = get_option( 'dgx_donate_paypal_secret_live' );
	} else {
		$data_array['paypal_client_id'] = get_option( 'dgx_donate_paypal_client_sandbox' );
		$data_array['paypal_secret_id'] = get_option( 'dgx_donate_paypal_secret_sandbox' );
	}
	$access_token = seamless_donations_paypal2022_rest_get_access_token( $data_array['paypal_client_id'], $data_array['paypal_secret_id'] );

	dgx_donate_debug_log( 'Entering PayPal2022 js test with mode ' . $data_array['gateway_mode'] );
	//dgx_donate_debug_log( 'PayPal2022 session id: ' . $session['id'] );
	dgx_donate_debug_log( 'Entering PayPal2022 redirect test' );
	$data_array['session_id'] = sanitize_text_field( $_POST['_dgx_donate_session_id'] );
	dgx_donate_debug_log( "Session ID retrieved from _POST: " . $data_array['session_id'] );

	$data_array['currency_code'] = get_option( 'dgx_donate_currency' );
	if ( isset( $_POST["_dgx_donate_session_id"] ) ) {
		$data_array['session_id'] = sanitize_text_field( $_POST["_dgx_donate_session_id"] );
		//$_POST["_dgx_donate_session_id"]
	} else {
		$data_array['session_id'] = '';
	}

	if ( isset( $_POST["_dgx_donate_amount"] ) ) {
		$data_array['amount'] = sanitize_text_field( $_POST["_dgx_donate_amount"] );
	} else {
		$data_array['amount'] = '0';
	}
	if ( strtoupper( $data_array['amount'] ) == 'OTHER' ) {
		if ( isset( $_POST["_dgx_donate_user_amount"] ) ) {
			$data_array['amount'] = sanitize_text_field( $_POST["_dgx_donate_user_amount"] );
		} else {
			$data_array['amount'] = '0';
		}
	}
	if ( isset( $session["REPEATING"] ) ) {
		$data_array['repeating'] = strtolower( sanitize_text_field( $session["REPEATING"] ) );
		if ( $data_array['repeating'] == 'on' ) {
			$data_array['product_id'] = seamless_donations_paypal2022_get_paypal_donation_product_id();
			$data_array['plan_id']    = seamless_donations_paypal2022_rest_create_plan_monthly_donation(
				$access_token,
				$data_array['product_id'],
				$data_array['amount'] );
		} else {
			$data_array['repeating'] = '';
		}
	} else {
		$data_array['repeating'] = '';
	}
	if ( isset( $_POST["_dgx_donate_success_url"] ) ) {
		$data_array['return_url'] = sanitize_url( $_POST["_dgx_donate_success_url"] );
	} else {
		$data_array['return_url'] = '';
	}
	if ( isset( $session["RETURN"] ) ) {
		$data_array['success_url'] = $session["RETURN"];
	} else {
		$data_array['success_url'] = $data_array['return_url'] . '?thanks=true&sessionid=' . $data_array['session_id'];
	}
	$data_array['abort_url'] = $data_array['return_url'] . '?cancel=error';

	$data_array = apply_filters( 'seamless_donations_paypal2022_checkout_data', $data_array );
	$new_amount = $data_array['amount'];
	$foo        = $data_array['session_id'];
	?>
    <!--  https://developer.paypal.com/sdk/js/
		  https://youtu.be/j-Gz0TVdDms -->

    <script src="<?php
	$sdk_data = 'https://www.paypal.com/sdk/js?' .
	            '&disable-funding=paylater,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sepa,sofort,venmo' .
	            '&client-id=' . esc_attr( $data_array['paypal_client_id'] ) .
	            '&currency=' . esc_attr( $data_array['currency_code'] );
	if ( $data_array['repeating'] == 'on' ) {
		$sdk_data .= '&vault=true&intent=subscription';
	}
	echo $sdk_data;
	?>" data-partner-attribution-id="ZATZPPCP_PSP"></script>

    <script>
        // https://developer.paypal.com/sdk/js/reference/#buttons
        // TODO Make recurring donations work, also update DonorsPayFees callback for donation -- see DEBUG.PHP
        // TODO We can record a recurring donation at PayPal. Now it's time to clone the stripe system
        // TODO for recording and polling for transactions and using the AUDIT table.

        let repeatingFlag = '<?php echo $data_array['repeating'] ?>';

        let paypalCommands = {};

        if (repeatingFlag === 'on') {
            paypalCommands.createSubscription = function (data, actions) {
                // Sets up the transaction when a payment button is clicked
                return actions.subscription.create({
                    plan_id: '<?php echo esc_textarea( $data_array['plan_id'] ); ?>'
                });
            };
            paypalCommands.onApprove = function (data, actions) {
                // processes the order and sends it back to Seamless Donations
                var params;
                params = '';
                if (data.hasOwnProperty("subscriptionID")) {
                    params += '&subscriptionID=' + data.subscriptionID;
                }
                if (params !== '') {
                    params = encodeURI(params);
                }
                window.location.href = '<?php echo esc_url_raw( $data_array['success_url'] ) ?>' + params;
            }
        } else {
            paypalCommands.createOrder = function (data, actions) {
                // Sets up the transaction when a payment button is clicked
                return actions.order.create({
                    intent: 'CAPTURE',
                    purchase_units: [{
                        reference_id: '<?php echo esc_textarea( $data_array['session_id'] ); ?>',
                        amount: {
                            value: '<?php echo esc_attr( $data_array['amount'] ); ?>'
                        },
                        item: {
                            category: 'DONATION' // ** DOESN'T APPEAR TO BE RECORDING IN PAYPAL
                        }
                    }]
                });
            };
            paypalCommands.onApprove = function (data, actions) {
                // processes the order and sends it back to Seamless Donations
                return actions.order.capture().then(function (details) {
                    // See notes in Apple Notes for how to parse details.
                    var params;
                    params = '';
                    if (details.hasOwnProperty("id")) {
                        params += '&orderid=' + details.id
                    }
                    if (details.hasOwnProperty("status")) {
                        params += '&status=' + details.status;
                    }
                    if (details.hasOwnProperty("payer")) {
                        if (details.payer.hasOwnProperty("payer_id")) {
                            // corresponds to paypal_account_id
                            params += '&payerid=' + details.payer.payer_id;
                        }
                    }

                    // I know this is insanely messy, but I haven't figured out a cleaner test
                    // it's also quite a pain, because the id that cross-references with other data
                    // is buried way down load in the array
                    if (details.purchase_units !== undefined) {
                        if (details.purchase_units[0] !== undefined) {
                            if (details.purchase_units[0].payments !== undefined) {
                                if (details.purchase_units[0].payments.captures !== undefined) {
                                    if (details.purchase_units[0].payments.captures[0] !== undefined) {
                                        if (details.purchase_units[0].payments.captures[0].id !== undefined) {
                                            console.log(details.purchase_units[0].payments.captures[0].id);
                                            params += '&id=' + details.purchase_units[0].payments.captures[0].id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (params !== '') {
                        params = encodeURI(params);
                    }
                    window.location.href = '<?php echo esc_url_raw( $data_array['success_url'] ) ?>' + params;
                });
            }
        }
        paypalCommands.onCancel = function (data) {
            window.location.href = '<?php echo esc_url_raw( $data_array['abort_url'] ) ?>';
        };
        paypalCommands.onError = function (err) {
            window.location.href = '<?php echo esc_url_raw( $data_array['abort_url'] ) ?>';
        };

        paypal.Buttons(paypalCommands).render('#dgx-donate-pay-enabled');
    </script>
	<?php
}

function seamless_donations_paypal2022_check_for_successful_transaction() {
	// https://stripe.com/docs/payments/checkout/accept-a-payment#payment-success
	// https://stripe.com/docs/cli/flags

	dgx_donate_debug_log( 'Entering PayPal2022 checking for successful transaction' );
	$currency_code = get_option( 'dgx_donate_currency' );
	$gateway_mode  = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_live' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_live' );
	} else {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_sandbox' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_sandbox' );
	}
	$access_token = seamless_donations_paypal2022_rest_get_access_token( $paypal_client_id, $paypal_secret_id );

	$donation_succeeded = false;
	if ( isset( $_GET["sessionid"] ) ) {
		$donation_session_id = sanitize_text_field( $_GET["sessionid"] );
		if ( $donation_session_id == '' ) {
			$donation_session_id = 'NONE';
		}
	} else {
		$donation_session_id = 'NONE';
	}
	dgx_donate_debug_log( 'Provided donation session ID: ' . $donation_session_id );

	// this is the order id
	// this is being passed to seamless_donations_process_confirmed_purchase as $transaction_id
	if ( isset( $_GET["id"] ) ) {
		$donation_order_id = sanitize_text_field( $_GET["id"] );
		if ( $donation_order_id == '' ) {
			$donation_order_id = 'NONE';
		}
	} else {
		$donation_order_id = 'NONE';
	}
	dgx_donate_debug_log( 'Donation order ID: ' . $donation_order_id );
	if ( $donation_order_id != 'NONE' ) {
		dgx_donate_debug_log( 'Retrieving order data from PayPal2022 REST...' );
		$paypal_order_details = seamless_donations_paypal2022_rest_get_order_details( $access_token, $donation_order_id );
		dgx_donate_debug_log( 'Order details retrieved.' );
	}

	if ( isset( $paypal_order_details["purchase_units"][0]["reference_id"] ) ) {
		$sd_session_id = $paypal_order_details["purchase_units"][0]["reference_id"];
	} else {
		$sd_session_id = $donation_session_id;
	}
	dgx_donate_debug_log( 'Donation session ID: ' . $sd_session_id );

	if ( isset( $_GET["status"] ) ) {
		$donation_status = sanitize_text_field( $_GET["status"] );
		if ( $donation_status == '' ) {
			$donation_status = 'NONE';
		}
	} else {
		$donation_status = 'NONE';
	}
	dgx_donate_debug_log( 'Donation status: ' . $donation_status );

	if ( isset( $_GET["payerid"] ) ) {
		$donation_payer_id = sanitize_text_field( $_GET["payerid"] );
		if ( $donation_payer_id == '' ) {
			$donation_payer_id = 'NONE';
		}
	} else {
		$donation_payer_id = 'NONE';
	}
	dgx_donate_debug_log( 'Donation payer ID: ' . $donation_payer_id );
	// $_GET["subscriptionID"]
	// $_GET["sessionid"]
	// $_GET["thanks"] == "true"
	// donation_status is NONE

	if ( $donation_status == 'COMPLETED' ) {
		seamless_donations_process_confirmed_purchase( 'PAYPAL2022',
			$currency_code, $donation_session_id, $donation_order_id, $paypal_order_details );
		seamless_donations_add_audit_string( 'PAYPAL2022-COMPLETE-' . $sd_session_id, $donation_order_id );
		dgx_donate_debug_log( '== Donation complete in Seamless Donations' );
	} else {
		if ( $donation_status == 'NONE' ) {
			// record an invoice ID if a subscription
			//	        $subscription_id       = $stripe_session->subscription;
			//	        $stripe_transaction_id = seamless_donations_stripe_get_latest_invoice_from_subscription($subscription_id);
			//	        seamless_donations_add_audit_string('STRIPE-SUBSCRIPTION-' . $subscription_id, $donation_session_id);
			//	        dgx_donate_debug_log('== Stripe Subscription ID: ' . $subscription_id);
			//	        dgx_donate_debug_log('== Stripe Transaction ID: ' . $stripe_transaction_id);
			if ( isset( $_GET["subscriptionID"] ) ) {
				$subscription_id      = strtoupper( sanitize_key( $_GET["subscriptionID"] ) );
				$transaction_list     = seamless_donations_paypal2022_rest_list_transactions( $access_token, 1, 0 );
				$sub_transaction_list = seamless_donations_paypal2022_rest_get_subscription_transactions( $access_token, $subscription_id, 30, 0 );
				if ( isset( $sub_transaction_list["transactions"][0]["id"] ) ) {
					$transaction_id = $sub_transaction_list["transactions"][0]["id"];
				} else {
					$transaction_id = $subscription_id . ' (SUBSCRIPTION ID)';
				}
				// fill $transaction_data array for adding purchase:
				$transaction_data = array(
					'id'             => $transaction_id,
					'payment_source' => array(
						'paypal' => array(
							'account_id' => 'NOT AVAILABLE YET',
						),
					),
				);

				seamless_donations_process_confirmed_purchase( 'PAYPAL2022',
					$currency_code, $donation_session_id, $donation_order_id, $transaction_data );
				seamless_donations_add_audit_string( 'PAYPAL2022-SUBSCRIPTION-' . $subscription_id, $donation_session_id );
			}
		}
		dgx_donate_debug_log( 'Donation not showing as succeeded' );
	}

	return 'PASS';
}

function seamless_donations_paypal2022_get_paypal_donation_product_id() {
	$id = get_option( 'dgx_donate_paypal2022_rest_product_id' );
	if ( $id === false ) {
		$gateway_mode = get_option( 'dgx_donate_paypal_server' );
		if ( $gateway_mode == 'LIVE' ) {
			$paypal_client_id = get_option( 'dgx_donate_paypal_client_live' );
			$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_live' );
		} else {
			$paypal_client_id = get_option( 'dgx_donate_paypal_client_sandbox' );
			$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_sandbox' );
		}
		$access_token = seamless_donations_paypal2022_rest_get_access_token( $paypal_client_id, $paypal_secret_id );
		$id           = seamless_donations_paypal2022_rest_create_donation_product_placeholder( $access_token );
		update_option( 'dgx_donate_paypal2022_rest_product_id', $id );
	}

	return $id;
}

function seamless_donations_paypal2022_poll_last_months_transactions() {
	// scan transactions and update accordingly
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_live' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_live' );
	} else {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_sandbox' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_sandbox' );
	}
	$access_token = seamless_donations_paypal2022_rest_get_access_token( $paypal_client_id, $paypal_secret_id );
	dgx_donate_cron_log( '...PayPal 2022 chron placeholder...' );
	$transaction_page_count = seamless_donations_paypal2022_rest_list_transactions_page_count( $access_token, 30, 0 );
	for ( $page = 1; $page <= $transaction_page_count; ++ $page ) {
		$transaction_list = seamless_donations_paypal2022_rest_list_transactions( $access_token, 30, 0, $page );
		foreach ( $transaction_list["transaction_details"] as $transaction_node ) {
			if ( isset( $transaction_node["transaction_info"]["paypal_reference_id_type"] ) ) {
				if ( $transaction_node["transaction_info"]["paypal_reference_id_type"] == 'SUB' ) {
					if ( isset( $transaction_node["transaction_info"]["paypal_reference_id"] ) ) {
						$subscription_id = $transaction_node["transaction_info"]["paypal_reference_id"];
					} else {
						$subscription_id = 'NONE';
					}
					if ( isset( $transaction_node["transaction_info"]["transaction_id"] ) ) {
						$transaction_id = $transaction_node["transaction_info"]["transaction_id"];
					} else {
						$transaction_id = 'NONE';
					}
					if ( isset( $transaction_node["transaction_info"]["paypal_account_id"] ) ) {
						$paypal_account_id = $transaction_node["transaction_info"]["paypal_account_id"];
					} else {
						$paypal_account_id = 'NONE';
					}
					$donation_id = seamless_donations_get_donations_by_meta( '_dgx_donate_transaction_id', $transaction_id, 1 );

					if ( count( $donation_id ) == 0 ) {
						// We haven't seen this transaction ID already
						$original_session_id = seamless_donations_get_audit_option( 'PAYPAL2022-SUBSCRIPTION-' . $subscription_id );
						if ( $original_session_id !== false ) {
							// we'll want to copy the contents of the session, pull out data for the call to
							// seamless_donations_process_confirmed_purchase, create a new session id
							// and write the old session data with the new ID to the audit table
							// this for any new subscription entry

							$original_session = seamless_donations_get_audit_option( $original_session_id );
							$new_session_id   = seamless_donations_get_guid( 'sd' ); // UUID on server
							seamless_donations_update_audit_option( $new_session_id, $original_session );

							dgx_donate_debug_log( 'Found new recurring donation on PayPal 2022' );
							dgx_donate_debug_log( 'New session ID:' . $new_session_id );

							$currency = $original_session['CURRENCY'];
							seamless_donations_process_confirmed_purchase(
								'PAYPAL2022', $currency, $new_session_id, $transaction_id, $transaction_node["transaction_info"] );
						}
					}
				}
			}
		}
	}

	return 'PASS';
}

function seamless_donations_paypal2022_audit_last_months_transactions() {
	// scan transactions and return summary
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_live' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_live' );
	} else {
		$paypal_client_id = get_option( 'dgx_donate_paypal_client_sandbox' );
		$paypal_secret_id = get_option( 'dgx_donate_paypal_secret_sandbox' );
	}
	$access_token = seamless_donations_paypal2022_rest_get_access_token( $paypal_client_id, $paypal_secret_id );
	dgx_donate_audit_log( '...PayPal 2022 audit placeholder...' );
	$transaction_page_count = seamless_donations_paypal2022_rest_list_transactions_page_count( $access_token, 30, 0 );
	dgx_donate_audit_log( 'transaction page count:' . $transaction_page_count );

	for ( $page = 1; $page <= $transaction_page_count; ++ $page ) {
		dgx_donate_audit_log( 'processing transaction page:' . $page );

		$transaction_list = seamless_donations_paypal2022_rest_list_transactions( $access_token, 30, 0, $page );

		foreach ( $transaction_list["transaction_details"] as $transaction_node ) {
            dgx_donate_audit_log( '======================================');
            foreach ($transaction_node["transaction_info"] as $key => $transaction_element) {
                if (is_string( $transaction_element)) {
	                dgx_donate_audit_log( $key . ': ' . $transaction_element);
                }
            }
		}
	}

    //todo might want to take a look at invoices, either search or list
    // https://developer.paypal.com/docs/api/invoicing/v2/#invoices_search-invoices

	dgx_donate_audit_log( '...PayPal 2022 audit complete...' );

	return 'PASS';
}

/////////////// PAYPAL REST UTILITY FUNCTIONS ///////////////////////////

function seamless_donations_paypal2022_rest_get_access_token( $client_id, $client_secret ) {
	// returns access token if successful, false if fails
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/oauth2/token";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/oauth2/token";
	}
	// https://developer.paypal.com/api/rest/authentication/

	$auth    = seamless_donations_http_basic_auth( $client_id, $client_secret );
	$request = array(
		'method'  => 'POST',
		'headers' => array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Authorization' => $auth,
		),
		'body'    => array(
			'grant_type' => 'client_credentials',
		),
	);

	$response = wp_remote_post( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		if ( isset( $data['access_token'] ) ) {
			return $data['access_token'];
		} else {
			seamless_donations_debug_log( 'Could not retrieve PayPal access token.' );

			return false;
		}
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_list_transactions( $access_token, $days_ago_start, $days_ago_end, $page = 1 ) {
	// start is the older date, end is the more recent date
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/reporting/transactions";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/reporting/transactions";
	}
	//https://www.paypal.com/us/smarthelp/article/what-format-does-the-api-timestamp-use-ts1085
	// https://www.php.net/manual/en/datetime.format.php
	$start_date = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_start ) . ' days' ) );
	$end_date   = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_end ) . ' days' ) );

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	$date_string = '?start_date=' . $start_date . '&end_date=' . $end_date;
	$page_string = '&page_size=100&page=' . strval( $page );

	$response = wp_remote_get( $url . $date_string . $page_string, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		// this code successfully brings data from the date range into the $data array
		// however, the session ID is not contained in the data array for any transaction
		// might need to go webhooks. Here's some info: https://www.youtube.com/watch?v=OBnFeqA9IOo
		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_list_transactions_page_count( $access_token, $days_ago_start, $days_ago_end, $page = 1 ) {
	// start is the older date, end is the more recent date
	// returns the number of sets of transactions returned by PayPal
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/reporting/transactions";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/reporting/transactions";
	}
	//https://www.paypal.com/us/smarthelp/article/what-format-does-the-api-timestamp-use-ts1085
	// https://www.php.net/manual/en/datetime.format.php
	$start_date = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_start ) . ' days' ) );
	$end_date   = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_end ) . ' days' ) );

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	$date_string = '?start_date=' . $start_date . '&end_date=' . $end_date;
	$page_string = '&page_size=100&page=' . strval( $page );

	$response = wp_remote_get( $url . $date_string . $page_string, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data       = json_decode( $response['body'], true );
		$page_count = $data["total_pages"];
		// this code successfully brings data from the date range into the $data array
		// however, the session ID is not contained in the data array for any transaction
		// might need to go webhooks. Here's some info: https://www.youtube.com/watch?v=OBnFeqA9IOo
		return $page_count;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_transaction_details( $access_token, $transaction_id, $days_ago_start, $days_ago_end ) {
	// start is the older date, end is the more recent date
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/reporting/transactions";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/reporting/transactions";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
		//		'body'    => array(
		//			'grant_type' => 'client_credentials',
		//		),
	);

	//https://www.paypal.com/us/smarthelp/article/what-format-does-the-api-timestamp-use-ts1085
	// https://www.php.net/manual/en/datetime.format.php
	$start_date = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_start ) . ' days' ) );
	$end_date   = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_end ) . ' days' ) );
	$query      = '?start_date=' . $start_date .
	              '&end_date=' . $end_date .
	              '&transaction_id=' . $transaction_id .
	              '&fields=all' .
	              '&page_size=100&page=1';

	$response = wp_remote_get( $url . $query, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		// this code successfully brings data from the date range into the $data array
		// however, the session ID is not contained in the data array for any transaction
		// might need to go webhooks. Here's some info: https://www.youtube.com/watch?v=OBnFeqA9IOo

		// See notes in Apple Notes
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_order_details( $access_token, $order_id ) {
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v2/checkout/orders/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v2/checkout/orders/";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	$response = wp_remote_get( $url . $order_id, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		// this code successfully brings data from the date range into the $data array
		// however, the session ID is not contained in the data array for any transaction
		// might need to go webhooks. Here's some info: https://www.youtube.com/watch?v=OBnFeqA9IOo
		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_plan_list( $access_token ) {
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/billing/plans/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/billing/plans/";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
		//		'body'    => array(
		//			'grant_type' => 'client_credentials',
		//		),
	);

	$response = wp_remote_get( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		// this is the plans array: $data["plans"]
		// if it's empty, there are no plans
		// this code successfully brings data from the date range into the $data array
		// however, the session ID is not contained in the data array for any transaction
		// might need to go webhooks. Here's some info: https://www.youtube.com/watch?v=OBnFeqA9IOo
		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_product_list( $access_token ) {
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/catalogs/products/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/catalogs/products/";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	$response = wp_remote_get( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );

		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_product_details( $access_token, $product_id ) {
	// returns a product detail array, or false if the product id doesn't exist
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/catalogs/products/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/catalogs/products/";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	$url = $url . $product_id;

	$response = wp_remote_get( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		if ( isset( $data["name"] ) ) {
			if ( $data["name"] == 'RESOURCE_NOT_FOUND' ) {
				return false;
			}
		}

		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_create_donation_product_placeholder( $access_token ) {
	// returns product id to save into WordPress or false, if the process fails
	$name        = 'Donation';
	$description = 'Donation powered by Seamless Donations';
	$type        = 'SERVICE';
	$category    = 'NONPROFIT';
	$image_url   = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/PP_Acceptance_Marks_for_LogoCenter_266x142.png';
	$home_url    = 'https://paypal.com';

	$data = seamless_donations_paypal2022_rest_create_product( $access_token, $name, $description, $type, $category, $image_url, $home_url );
	if ( isset( $data["id"] ) ) {
		return $data["id"];
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_create_product( $access_token, $name, $description, $type, $category, $image_url, $home_url ) {
	// this will create multiple identical products
	// there is no error checking at PayPal
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/catalogs/products/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/catalogs/products/";
	}

	$data = array(
		'name'        => $name,
		'description' => $description,
		'type'        => $type,
		'category'    => $category,
		'image_url'   => $image_url,
		'home_url'    => $home_url,
	);
	$data = wp_json_encode( $data );

	$request = array(
		'method'  => 'POST',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
		'body'    => $data,
	);

	$response = wp_remote_post( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );

		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_create_plan_monthly_donation( $access_token, $id, $amount ) {
	// returns the plan id
	// see: https://developer.paypal.com/docs/subscriptions/customize/pricing-plans/
	// an original idea was to use 'quantity_supported' to implement a quantity-based plan
	// to allows buyer to choose quantity and hopefully allows us to have just one plan
	// instead, we're assigning a plan to every donation subscription scheduled.
	$currency = get_option( 'dgx_donate_currency' );
	$amount   = strval( $amount );

	$plan_data = array(
		'product_id'          => $id,
		'name'                => 'Donation Monthly Plan',
		'description'         => 'Donation Monthly Plan, powered by Seamless Donations',
		'status'              => 'ACTIVE',
		'billing_cycles'      => array(
			array(
				'frequency'      => array(
					'interval_unit'  => 'MONTH',
					'interval_count' => 1,
				),
				'tenure_type'    => 'REGULAR',
				'sequence'       => 1,
				'total_cycles'   => 0,
				'pricing_scheme' => array(
					'fixed_price' => array(
						'value'         => $amount,
						'currency_code' => $currency,
					),
				),
			),
		),
		//'quantity_supported' => true,
		'payment_preferences' => array(
			'auto_bill_outstanding'     => true,
			'payment_failure_threshold' => 3,
		),
	);

	$result = seamless_donations_paypal2022_rest_create_plan( $access_token, $plan_data );
	if ( isset( $result["id"] ) ) {
		return $result["id"];
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_create_plan( $access_token, $data_array ) {
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/billing/plans/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/billing/plans/";
	}

	$data_array = wp_json_encode( $data_array );

	$request = array(
		'method'  => 'POST',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
		'body'    => $data_array,
	);

	$response = wp_remote_post( $url, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );
		// this is the plans array: $data["plans"]
		// if it's empty, there are no plans
		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_subscription_details( $access_token, $subscription_id ) {
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/billing/subscriptions/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/";
	}

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
		//		'body'    => array(
		//			'grant_type' => 'client_credentials',
		//		),
	);

	$response = wp_remote_get( $url . $subscription_id, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );

		return $data;
	} else {
		return false;
	}
}

function seamless_donations_paypal2022_rest_get_subscription_transactions( $access_token, $subscription_id, $days_ago_start, $days_ago_end ) {
	// start is the older date, end is the more recent date
	$gateway_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $gateway_mode == 'LIVE' ) {
		$url = "https://api-m.paypal.com/v1/billing/subscriptions/";
	} else {
		$url = "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/";
	}
	//https://www.paypal.com/us/smarthelp/article/what-format-does-the-api-timestamp-use-ts1085
	// https://www.php.net/manual/en/datetime.format.php
	$start_date = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_start ) . ' days' ) );
	$end_date   = date( 'Y-m-d\TH:i:s.v\Z', strtotime( '-' . strval( $days_ago_end ) . ' days' ) );

	$request = array(
		'method'  => 'GET',
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $access_token,
		),
	);

	// /transactions?start_time=2018-01-21T07:50:20.940Z&end_time=2018-08-21T07:50:20.940Z
	$query = strtoupper( $subscription_id ) . '/transactions?start_time=' . $start_date . '&end_time=' . $end_date;

	$response = wp_remote_get( $url . $query, $request );
	if ( ! seamless_donations_http_is_error( $response ) ) {
		$data = json_decode( $response['body'], true );

		return $data;
	} else {
		return false;
	}
}

/////////////// UTILITY FUNCTIONS ///////////////////////////

function seamless_donations_http_basic_auth( $user, $pw ) {
	// Authorization header Basic word followed by a space and a base64-encoded username:password string
	// How to make it work with WordPress: https://johnblackbourn.com/wordpress-http-api-basicauth/
	$auth = 'Basic ' . base64_encode( $user . ':' . $pw );

	return $auth;
}

function seamless_donations_http_is_error( $response ) {
	// returns true if an error is found, false otherwise
	if ( isset( $response['body'] ) ) {
		$data = json_decode( $response['body'], true );
		if ( isset( $data['error'] ) ) {
			$msg = 'HTTP response error ' . $data['error'];
			if ( isset( $data['error_description'] ) ) {
				$msg .= ': ' . $data['error_description'];
			}
			seamless_donations_debug_log( $msg );

			return true;
		} else {
			return false;
		}
	} else {
		seamless_donations_debug_log( 'No response body from JSON request.' );

		return true;
	}
}
