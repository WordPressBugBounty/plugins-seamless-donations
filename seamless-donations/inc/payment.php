<?php

/**
 * Seamless Donations by David Gewirtz, adopted from Allen Snook
 *
 * Lab Notes: http://zatzlabs.com/lab-notes/
 * Plugin Page: http://zatzlabs.com/seamless-donations/
 * Contact: http://zatzlabs.com/contact-us/
 *
 * Copyright (c) 2015-2022 by David Gewirtz
 */

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function seamless_donations_process_payment() {
	dgx_donate_debug_log( '----------------------------------------' );
	dgx_donate_debug_log( 'DONATION TRANSACTION STARTED' );

	seamless_donations_check_payment_nonce();

	$session_id  = sanitize_text_field( $_POST['_dgx_donate_session_id'] );
	$php_version = phpversion();

	$payment_gateway = get_option( 'dgx_donate_payment_processor_choice' );
	dgx_donate_debug_log( 'Payment gateway: ' . $payment_gateway );
	switch ( $payment_gateway ) {
		case 'PAYPAL':
			$gateway_mode = get_option( 'dgx_donate_paypal_server' );

			$notify_url = seamless_donations_get_paypal_notification_url();
			$notify_url = str_ireplace( 'http://', 'https://', $notify_url ); // force https check
			break;
		case 'STRIPE':
			$gateway_mode = get_option( 'dgx_donate_stripe_server' );
			$notify_url   = plugins_url( '/pay/stripe/webhook.php', dirname( __FILE__ ) );
			$notify_url   = str_ireplace( 'http://', 'https://', $notify_url ); // force https check
			if ( $gateway_mode == 'LIVE' ) {
				$stripe_api_key    = get_option( 'dgx_donate_live_stripe_api_key' );
				$stripe_secret_key = get_option( 'dgx_donate_live_stripe_secret_key' );
			} else {
				$stripe_api_key    = get_option( 'dgx_donate_test_stripe_api_key' );
				$stripe_secret_key = get_option( 'dgx_donate_test_stripe_secret_key' );
			}
			break;
		case 'PAYPAL2022':
			$gateway_mode = get_option( 'dgx_donate_paypal_server' );
			$notify_url = "Not Used";
			if ( $gateway_mode == 'LIVE' ) {
				$paypal_merchant_id    = get_option( 'dgx_donate_paypal_merchant_live' );
				$paypal_client_id = get_option( 'dgx_donate_paypal_client_live' );
			} else {
				$paypal_merchant_id    = get_option( 'dgx_donate_paypal_merchant_sandbox' );
				$paypal_client_id = get_option( 'dgx_donate_paypal_client_sandbox' );
			}
			break;
	}

	dgx_donate_debug_log( "Session ID retrieved from _POST: $session_id" );
	dgx_donate_debug_log( 'Processing mode: ' . $gateway_mode );
	dgx_donate_debug_log( "PHP version: $php_version" );
	dgx_donate_debug_log( 'Seamless Donations version: ' . dgx_donate_get_version() );
	dgx_donate_debug_log( 'User browser: ' . seamless_donations_get_browser_name() );
	dgx_donate_debug_log( 'Payment gateway: ' . $payment_gateway );
	dgx_donate_debug_log( 'Gateway mode: ' . $gateway_mode );
	dgx_donate_debug_log( 'Notify URL (https IPN): ' . $notify_url );

	$session_data = seamless_donations_check_preexisting_payment_session_data( $session_id );

	if ( $session_data !== false ) {
		update_option( 'dgx_donate_caching_causing_failure', 'true' );
		dgx_donate_debug_log( 'Session data already exists, returning false' );
		echo 'ERROR: Unable to create unique donation transaction due to page caching. Please notify system operator.';
		die();
	} else {
		dgx_donate_debug_log( 'Duplicate session data not found. Payment process data assembly can proceed.' );

		$post_data = seamless_donations_repack_payment_form_data_for_transmission_to_gateways();
		$post_data = apply_filters( 'seamless_donations_payment_post_data', $post_data );
		seamless_donations_perform_captcha_check( $post_data );

		seamless_donations_save_payment_transaction_data_for_audit( $post_data, $session_id );

		// more log data
		$donor_name = seamless_donations_obscurify_donor_name( $post_data );
		dgx_donate_debug_log( 'Name: ' . $donor_name );
		dgx_donate_debug_log( 'Amount: ' . $post_data['AMOUNT'] );
		dgx_donate_debug_log( 'Preparation complete.' );

		switch ( $payment_gateway ) {
			case 'PAYPAL':
				dgx_donate_debug_log( 'Entering PayPal gateway processing.' );
				$post_args = seamless_donations_build_paypal_query_string( $post_data, $notify_url );
				seamless_donations_redirect_to_paypal( $post_args, $gateway_mode );
				break;
			case 'STRIPE':
				dgx_donate_debug_log( 'Entering Stripe gateway processing.' );
				$cancel_url = get_option( 'dgx_donate_form_url' );
				if ( strpos( $cancel_url, '?' ) === false ) {
					$cancel_url .= '?';
				} else {
					$cancel_url .= '&';
				}
				$cancel_url .= 'cancel=true&sessionid=' . $session_id;

				$stripe_data = seamless_donations_redirect_to_stripe( $post_data, $stripe_secret_key, $notify_url, $cancel_url );
				if ( $stripe_data == null ) {
					wp_redirect( $cancel_url . '?cancel=error' );
					exit;
				}
				seamless_donations_stripe_js_redirect( $stripe_data );
				break;
			case 'PAYPAL2022':
				// run test code
				dgx_donate_debug_log( "Process Payment PAYPAL2022");
				seamless_donations_paypal2022_js_redirect($post_data);
				break;
		}
	}
}

function seamless_donations_check_payment_nonce() {
	$nonce_bypass = get_option( 'dgx_donate_ignore_form_nonce' );
	if ( $nonce_bypass != '1' ) {
		$nonce = $_POST['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'dgx-donate-nonce' ) ) {
			$nonce_error  = 'Payment process nonce validation failure. ';
			$nonce_error .= 'Consider turning on Ignore Form Nonce Value in the Seamless Donations ';
			$nonce_error .= 'Settings tab under Host Compatibility Options.';
			dgx_donate_debug_log( $nonce_error );
			die( 'Security violation detected [A021]. Access Denied. See Seamless Donations log for details.' );
		} else {
			dgx_donate_debug_log( "Payment process nonce $nonce validated." );
		}
	}
}

function seamless_donations_check_preexisting_payment_session_data( $session_id ) {
	// now attempt to retrieve session data to see if it already exists (which would trigger an error)
	$session_data = seamless_donations_get_audit_option( $session_id );
	dgx_donate_debug_log( 'Looking for pre-existing session data (guid/audit db mode): ' . $session_id );

	return $session_data;
}

function seamless_donations_perform_captcha_check( $post_data ) {
	// insert extra validation for GoodByeCaptcha and any other validation
	$challenge_response_passed = apply_filters( 'seamless_donations_challenge_response_request', true, $post_data );

	if ( true !== $challenge_response_passed ) {
		if ( is_wp_error( $challenge_response_passed ) ) {
			$error_message = $challenge_response_passed->get_error_message();
		} else {
			$error_message = (string) $challenge_response_passed;
		}
		dgx_donate_debug_log( 'Form challenge-response failed:' . $error_message );
		die( esc_html__( 'Invalid response to challenge. Are you human?' ) );
	}
}

function seamless_donations_save_payment_transaction_data_for_audit( $post_data, $session_id ) {
	seamless_donations_update_audit_option( $session_id, $post_data );
	if ( isset( $post_data['EMAIL'] ) ) {
		seamless_donations_update_audit_email( $post_data['EMAIL'], $session_id );
	}
	dgx_donate_debug_log( 'Saving transaction data using guid/audit db mode' );
}

function seamless_donations_repack_payment_form_data_for_transmission_to_gateways() {
	// There are a ton of nonce errors from the sniffer, but nonce processing is
	// higher up in the payment chain

	// Repack the POST
	$post_data = array();

	$organization_name = get_option( 'dgx_donate_organization_name' );
	if ( $organization_name == false ) {
		$organization_name = '';
	}
	$post_data['ORGANIZATION'] = $organization_name;

	if ( isset( $_POST['_dgx_donate_redirect_url'] ) ) {
		$post_data['REFERRINGURL'] = esc_url_raw( $_POST['_dgx_donate_redirect_url'] );
	} else {
		$post_data['REFERRINGURL'] = '';
	}
	if ( isset( $_POST['_dgx_donate_success_url'] ) ) {
		$post_data['SUCCESSURL'] = esc_url_raw( $_POST['_dgx_donate_success_url'] );
	} else {
		$post_data['SUCCESSURL'] = '';
	}
	if ( isset( $_POST['_dgx_donate_session_id'] ) ) {
		$post_data['SESSIONID'] = sanitize_text_field( $_POST['_dgx_donate_session_id'] );
	} else {
		$post_data['SESSIONID'] = '';
	}
	if ( isset( $_POST['_dgx_donate_repeating'] ) ) {
		$post_data['REPEATING'] = sanitize_text_field( $_POST['_dgx_donate_repeating'] );
	} else {
		$post_data['REPEATING'] = '';
	}
	if ( isset( $_POST['_dgx_donate_designated'] ) ) {
		$post_data['DESIGNATED'] = sanitize_text_field( $_POST['_dgx_donate_designated'] );
	} else {
		$post_data['DESIGNATED'] = '';
	}
	if ( isset( $_POST['_dgx_donate_designated_fund'] ) ) {
		$post_data['DESIGNATEDFUND'] = sanitize_text_field( $_POST['_dgx_donate_designated_fund'] );
	} else {
		$post_data['DESIGNATEDFUND'] = '';
	}
	if ( isset( $_POST['_dgx_donate_tribute_gift'] ) ) {
		$post_data['TRIBUTEGIFT'] = sanitize_text_field( $_POST['_dgx_donate_tribute_gift'] );
	} else {
		$post_data['TRIBUTEGIFT'] = '';
	}
	if ( isset( $_POST['_dgx_donate_memorial_gift'] ) ) {
		$post_data['MEMORIALGIFT'] = sanitize_text_field( $_POST['_dgx_donate_memorial_gift'] );
	} else {
		$post_data['MEMORIALGIFT'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_name'] ) ) {
		$post_data['HONOREENAME'] = sanitize_text_field( $_POST['_dgx_donate_honoree_name'] );
	} else {
		$post_data['HONOREENAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honor_by_email'] ) ) {
		$post_data['HONORBYEMAIL'] = sanitize_text_field( $_POST['_dgx_donate_honor_by_email'] );
	} else {
		$post_data['HONORBYEMAIL'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_email'] ) ) {
		$post_data['HONOREEEMAIL'] = sanitize_email( $_POST['_dgx_donate_honoree_email'] );
	} else {
		$post_data['HONOREEEMAIL'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_address'] ) ) {
		$post_data['HONOREEADDRESS'] = sanitize_text_field( $_POST['_dgx_donate_honoree_address'] );
	} else {
		$post_data['HONOREEADDRESS'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_city'] ) ) {
		$post_data['HONOREECITY'] = sanitize_text_field( $_POST['_dgx_donate_honoree_city'] );
	} else {
		$post_data['HONOREECITY'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_state'] ) ) {
		$post_data['HONOREESTATE'] = sanitize_text_field( $_POST['_dgx_donate_honoree_state'] );
	} else {
		$post_data['HONOREESTATE'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_province'] ) ) {
		$post_data['HONOREEPROVINCE'] = sanitize_text_field( $_POST['_dgx_donate_honoree_province'] );
	} else {
		$post_data['HONOREEPROVINCE'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_country'] ) ) {
		$post_data['HONOREECOUNTRY'] = sanitize_text_field( $_POST['_dgx_donate_honoree_country'] );
	} else {
		$post_data['HONOREECOUNTRY'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_zip'] ) ) {
		$post_data['HONOREEZIP'] = sanitize_text_field( $_POST['_dgx_donate_honoree_zip'] );
	} else {
		$post_data['HONOREEZIP'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_email_name'] ) ) {
		$post_data['HONOREEEMAILNAME'] = sanitize_text_field( $_POST['_dgx_donate_honoree_email_name'] );
	} else {
		$post_data['HONOREEEMAILNAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_honoree_post_name'] ) ) {
		$post_data['HONOREEPOSTNAME'] = sanitize_text_field( $_POST['_dgx_donate_honoree_post_name'] );
	} else {
		$post_data['HONOREEPOSTNAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_first_name'] ) ) {
		$post_data['FIRSTNAME'] = sanitize_text_field( $_POST['_dgx_donate_donor_first_name'] );
	} else {
		$post_data['FIRSTNAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_last_name'] ) ) {
		$post_data['LASTNAME'] = sanitize_text_field( $_POST['_dgx_donate_donor_last_name'] );
	} else {
		$post_data['LASTNAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_phone'] ) ) {
		$post_data['PHONE'] = sanitize_text_field( $_POST['_dgx_donate_donor_phone'] );
	} else {
		$post_data['PHONE'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_email'] ) ) {
		$post_data['EMAIL'] = sanitize_email( $_POST['_dgx_donate_donor_email'] );
	} else {
		$post_data['EMAIL'] = '';
	}
	if ( isset( $_POST['_dgx_donate_add_to_mailing_list'] ) ) {
		$post_data['ADDTOMAILINGLIST'] = sanitize_text_field( $_POST['_dgx_donate_add_to_mailing_list'] );
	} else {
		$post_data['ADDTOMAILINGLIST'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_address'] ) ) {
		$post_data['ADDRESS'] = sanitize_text_field( $_POST['_dgx_donate_donor_address'] );
	} else {
		$post_data['ADDRESS'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_address2'] ) ) {
		$post_data['ADDRESS2'] = sanitize_text_field( $_POST['_dgx_donate_donor_address2'] );
	} else {
		$post_data['ADDRESS2'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_city'] ) ) {
		$post_data['CITY'] = sanitize_text_field( $_POST['_dgx_donate_donor_city'] );
	} else {
		$post_data['CITY'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_state'] ) ) {
		$post_data['STATE'] = sanitize_text_field( $_POST['_dgx_donate_donor_state'] );
	} else {
		$post_data['STATE'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_province'] ) ) {
		$post_data['PROVINCE'] = sanitize_text_field( $_POST['_dgx_donate_donor_province'] );
	} else {
		$post_data['PROVINCE'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_country'] ) ) {
		$post_data['COUNTRY'] = sanitize_text_field( $_POST['_dgx_donate_donor_country'] );
	} else {
		$post_data['COUNTRY'] = '';
	}
	if ( isset( $_POST['_dgx_donate_donor_zip'] ) ) {
		$post_data['ZIP'] = sanitize_text_field( $_POST['_dgx_donate_donor_zip'] );
	} else {
		$post_data['ZIP'] = '';
	}
	if ( isset( $_POST['_dgx_donate_increase_to_cover'] ) ) {
		$post_data['INCREASETOCOVER'] = sanitize_text_field( $_POST['_dgx_donate_increase_to_cover'] );
	} else {
		$post_data['INCREASETOCOVER'] = '';
	}
	if ( isset( $_POST['_dgx_donate_anonymous'] ) ) {
		$post_data['ANONYMOUS'] = sanitize_text_field( $_POST['_dgx_donate_anonymous'] );
	} else {
		$post_data['ANONYMOUS'] = '';
	}
	if ( isset( $_POST['_dgx_donate_employer_match'] ) ) {
		$post_data['EMPLOYERMATCH'] = sanitize_text_field( $_POST['_dgx_donate_employer_match'] );
	} else {
		$post_data['EMPLOYERMATCH'] = '';
	}
	if ( isset( $_POST['_dgx_donate_employer_name'] ) ) {
		$post_data['EMPLOYERNAME'] = sanitize_text_field( $_POST['_dgx_donate_employer_name'] );
	} else {
		$post_data['EMPLOYERNAME'] = '';
	}
	if ( isset( $_POST['_dgx_donate_occupation'] ) ) {
		$post_data['OCCUPATION'] = sanitize_text_field( $_POST['_dgx_donate_occupation'] );
	} else {
		$post_data['OCCUPATION'] = '';
	}
	if ( isset( $_POST['_dgx_donate_uk_gift_aid'] ) ) {
		$post_data['UKGIFTAID'] = sanitize_text_field( $_POST['_dgx_donate_uk_gift_aid'] );
	} else {
		$post_data['UKGIFTAID'] = '';
	}
	if ( isset( $_POST['nonce'] ) ) {
		$post_data['NONCE'] = sanitize_text_field( $_POST['nonce'] );
	} else {
		$post_data['NONCE'] = '';
	}

	// pull override data from hidden form (might be modified by users with callbacks)
	if ( isset( $_POST['business'] ) ) {
		$post_data['BUSINESS'] = sanitize_text_field( $_POST['business'] );
	} else {
		$post_data['BUSINESS'] = '';
	}
	if ( isset( $_POST['return'] ) ) {
		$post_data['RETURN'] = sanitize_text_field( $_POST['return'] );
	} else {
		$post_data['RETURN'] = '';
	}
	if ( isset( $_POST['notify_url'] ) ) {
		$post_data['NOTIFY_URL'] = sanitize_text_field( $_POST['notify_url'] );
	} else {
		$post_data['NOTIFY_URL'] = '';
	}
	if ( isset( $_POST['item_name'] ) ) {
		$post_data['ITEM_NAME'] = sanitize_text_field( $_POST['item_name'] );
	} else {
		$post_data['ITEM_NAME'] = '';
	}

	// PAYPAL ENCODINGS
	if ( isset( $_POST['cmd'] ) ) {
		$post_data['CMD'] = sanitize_text_field( $_POST['cmd'] );
	} else {
		$post_data['CMD'] = '';
	}
	if ( isset( $_POST['p3'] ) ) {
		$post_data['P3'] = sanitize_text_field( $_POST['p3'] );
	} else {
		$post_data['P3'] = '';
	}
	if ( isset( $_POST['t3'] ) ) {
		$post_data['T3'] = sanitize_text_field( $_POST['t3'] );
	} else {
		$post_data['T3'] = '';
	}
	if ( isset( $_POST['a3'] ) ) {
		;
		$post_data['A3'] = sanitize_text_field( $_POST['a3'] );
	} else {
		$post_data['A3'] = '';
	}

	// Resolve the donation amount
	// fix bug where no radio buttons don't show donation amount
	// todo - OTHER is not set when Giving Level Manager has radio buttons turned off
	// original code
	if ( strcasecmp( sanitize_key($_POST['_dgx_donate_amount']), 'OTHER' ) == 0 ) {
		$post_data['AMOUNT'] = floatval( $_POST['_dgx_donate_user_amount'] );
	} else {
		$post_data['AMOUNT'] = floatval( $_POST['_dgx_donate_amount'] );
	}
	if ( $post_data['AMOUNT'] < 1.00 ) {
		$post_data['AMOUNT'] = 1.00;
	}

	if ( 'US' == $post_data['HONOREECOUNTRY'] ) {
		$post_data['PROVINCE'] = '';
	} elseif ( 'CA' == $post_data['HONOREECOUNTRY'] ) {
		$post_data['HONOREESTATE'] = '';
	} else {
		$post_data['HONOREESTATE']    = '';
		$post_data['HONOREEPROVINCE'] = '';
	}

	// If no country entered, pull in the default
	if ( $post_data['COUNTRY'] == '' ) {
		$post_data['COUNTRY'] = get_option( 'dgx_donate_default_country' );
	}

	if ( 'US' == $post_data['COUNTRY'] ) {
		$post_data['PROVINCE'] = '';
	} elseif ( 'CA' == $post_data['COUNTRY'] ) {
		$post_data['STATE'] = '';
	} else {
		$post_data['STATE']    = '';
		$post_data['PROVINCE'] = '';
	}

	$gateway = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $gateway == false ) {
		$gateway = 'PayPal';
	}
	$post_data['PAYMENTMETHOD'] = $gateway;
	$post_data['SDVERSION']     = dgx_donate_get_version();

	// Sanitize the data (remove leading, trailing spaces quotes, brackets)
	foreach ( $post_data as $key => $value ) {
		$temp              = trim( $value );
		$temp              = str_replace( '"', '', $temp );
		$temp              = wp_strip_all_tags( $temp );
		$post_data[ $key ] = $temp;
	}
	// account for different permalink styles
	$success_url = $post_data['SUCCESSURL'];
	$qmark       = strpos( $success_url, '?' );
	if ( $qmark === false ) {
		$success_url .= '?thanks=true';
		$success_url .= '&sessionid=' . $post_data['SESSIONID'];
	} else {
		$success_url .= '&thanks=true';
		$success_url .= '&sessionid=' . $post_data['SESSIONID'];
	}
	$post_data['RETURN'] = $success_url;
	dgx_donate_debug_log( "Success URL: $success_url" );

	return $post_data;
}

function seamless_donations_obscurify_donor_name( $post_data ) {
	$obscurify = get_option( 'dgx_donate_log_obscure_name' ); // false if not set
	if ( $obscurify == '1' ) {
		// obscurify for privacy
		$donor_name = strtolower( $post_data['FIRSTNAME'] . $post_data['LASTNAME'] );
		$donor_name = seamless_donations_obscurify_string( $donor_name, '*', false );
	} else {
		$donor_name = $post_data['FIRSTNAME'] . ' ' . $post_data['LASTNAME'];
	}

	return $donor_name;
}

function seamless_donations_build_donation_description( $post_data ) {
	// build the description
	$desc  = 'Donation by ';
	$donor = $post_data['FIRSTNAME'] . ' ' . $post_data['LASTNAME'];
	if ( isset( $post_data['ANONYMOUS'] ) ) {
		if ( $post_data['ANONYMOUS'] == 'on' ) {
			$donor = 'Anonymous';
		}
	}
	$desc .= $donor;
	if ( isset( $post_data['ORGANIZATION'] ) ) {
		if ( $post_data['ORGANIZATION'] != '' ) {
			$desc .= ' to ' . $post_data['ORGANIZATION'];
		}
	}
	if ( isset( $post_data['DESIGNATEDFUND'] ) ) {
		$fund_id = $post_data['DESIGNATEDFUND'];
		$fund    = get_post( $fund_id );
		if ( $fund != null ) {
			$fund_title = $fund->post_title;
			if ( $fund_title != '' ) {
				$desc .= ' (' . $fund_title . ')';
			}
		}
	}
	if ( isset( $post_data['HONOREENAME'] ) ) {
		if ( $post_data['HONOREENAME'] != '' ) {
			$honor = false;
			if ( isset( $post_data['MEMORIALGIFT'] ) ) {
				if ( $post_data['MEMORIALGIFT'] == 'on' ) {
					$desc .= ' in memory of';
					$honor = true;
				}
			}
			if ( ! $honor ) {
				if ( isset( $post_data['TRIBUTEGIFT'] ) ) {
					if ( $post_data['TRIBUTEGIFT'] == 'on' ) {
						$desc .= ' in honor of';
						$honor = true;
					}
				}
			}
			if ( $honor ) {
				$desc .= ' ' . $post_data['HONOREENAME'];
			}
		}
	}
	$desc = sanitize_text_field( $desc );

	return $desc;
}

function seamless_donations_init_payment_gateways() {
	$payment_gateway = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_gateway == 'STRIPE' ) {
		if ( ! is_admin() ) {
			// we only need to run this on client-facing pages
			$gateway_mode = get_option( 'dgx_donate_stripe_server' );
			if ( $gateway_mode == 'LIVE' ) {
				$stripe_api_key    = get_option( 'dgx_donate_live_stripe_api_key' );
				$stripe_secret_key = get_option( 'dgx_donate_live_stripe_secret_key' );
			} else {
				$stripe_api_key    = get_option( 'dgx_donate_test_stripe_api_key' );
				$stripe_secret_key = get_option( 'dgx_donate_test_stripe_secret_key' );
			}
			seamless_donations_init_stripe( $stripe_api_key );
		}
	}
}

function seamless_donations_provisionally_process_gateway_result() {
	// this doesn't call legacy paypal because legacy paypal is triggered by the
	// whole IPN processing thing and paypal-ipn.php
	if ( isset( $_GET['thanks'] ) ) {
		$gateway = get_option( 'dgx_donate_payment_processor_choice' );
		if ( $gateway == 'STRIPE' ) {
			$result = seamless_donations_stripe_check_for_successful_transaction();
		}
		if ($gateway == 'PAYPAL2022') {
			// the following routine runs the full donation recording process
			$result = seamless_donations_paypal2022_check_for_successful_transaction();
		}
	}
}

function seamless_donations_process_confirmed_purchase( $gateway, $currency, $donation_session_id, $transaction_id, $transaction_data, $send_email=true ) {
	dgx_donate_debug_log( $gateway . ' TRANSACTION VERIFIED for session ID ' . $donation_session_id );

	// Check if we've already logged a transaction with this same transaction id
	$donation_id = seamless_donations_get_donations_by_meta( '_dgx_donate_transaction_id', $transaction_id, 1 );

	if ( count( $donation_id ) == 0 ) {
		// We haven't seen this transaction ID already

		// See if a donation for this session ID already exists
		$donation_id = seamless_donations_get_donations_by_meta( '_dgx_donate_session_id', $donation_session_id, 1 );

		if ( count( $donation_id ) == 0 ) {
			// We haven't seen this session ID already

			// Retrieve the data from audit db table
			$donation_form_data = seamless_donations_get_audit_option( $donation_session_id );

			if ( ! empty( $donation_form_data ) ) {
				// Create a donation record

				dgx_donate_debug_log( 'Creating donation from transaction audit data in 4.x mode.' );
				$donation_id = seamless_donations_create_donation_from_transaction_audit_table(
					$donation_form_data
				);

				dgx_donate_debug_log(
					"Created donation {$donation_id} for session ID {$donation_session_id}"
				);
			} else {
				// We have a session_id but no transient (the admin might have
				// deleted all previous donations in a recurring donation for
				// some reason) - so we will have to create a donation record
				// from the data supplied by PayPal

				$donation_id = seamless_donations_create_donation_from_paypal_data();
				dgx_donate_debug_log(
					"Created donation {$donation_id} " .
					'from PayPal data (no audit db data found) in 4.x mode.'
				);
			}
		} else {
			// We have seen this session ID already, create a new donation record for this new transaction

			// But first, flatten the array returned by get_donations_by_meta for _dgx_donate_session_id
			$donation_id = $donation_id[0];

			$old_donation_id = $donation_id;

			$donation_id = seamless_donations_create_donation_from_donation( $old_donation_id );

			dgx_donate_debug_log(
				"Created donation {$donation_id} (recurring donation, donor data copied from donation {$old_donation_id}"
			);
		}
	} else {
		// We've seen this transaction ID already - ignore it
		$donation_id = '';
		dgx_donate_debug_log( "Transaction ID {$transaction_id} already handled - ignoring" );
	}

	if ( ! empty( $donation_id ) ) {
		// Update the raw gateway data
		update_post_meta( $donation_id, '_dgx_donate_transaction_id', $transaction_id );
		update_post_meta( $donation_id, '_dgx_donate_payment_processor', $gateway );
		if ( $gateway == 'STRIPE' ) {
			$stripe_session_id  = $transaction_data->id;
			$stripe_customer_id = $transaction_data->customer;
			update_post_meta( $donation_id, '_dgx_donate_stripe_session_id', $stripe_session_id );
			update_post_meta( $donation_id, '_dgx_donate_stripe_customer_id', $stripe_customer_id );
		}
		if ( $gateway == 'PAYPAL' ) {
			update_post_meta( $donation_id, '_dgx_donate_transaction_id', $transaction_data->transaction_id );
			update_post_meta( $donation_id, '_dgx_donate_paypal_account_id', $transaction_data->paypal_account_id );
		}
		if ($gateway == 'PAYPAL2022') {
			$paypal_transaction_id = $transaction_data["transaction_id"];
			$paypal_account_id = $transaction_data["paypal_account_id"];
			update_post_meta( $donation_id, '_dgx_donate_transaction_id', $paypal_transaction_id);
			update_post_meta( $donation_id, '_dgx_donate_paypal2022_account_id', $paypal_account_id );
		}

		update_post_meta( $donation_id, '_dgx_donate_payment_processor_data', $transaction_data );

		dgx_donate_debug_log( "Payment currency = {$currency}" );
		update_post_meta( $donation_id, '_dgx_donate_donation_currency', $currency );

		// @todo - send different notification for recurring?

		if($send_email) {
			// Send admin notification
			dgx_donate_send_donation_notification( $donation_id );
			// Send donor notification
			dgx_donate_send_thank_you_email( $donation_id );
		}
	}
}
