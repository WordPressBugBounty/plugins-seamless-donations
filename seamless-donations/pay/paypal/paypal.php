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

function seamless_donations_get_paypal_notification_url() {
	// old style
	// $notify_url = plugins_url('/pay/paypalstd/ipn.php', __FILE__);
	// $notify_url = plugins_url('/pay/paypalstd/try.php', dirname(dirname(__FILE__)));

	$last_char = substr( site_url(), strlen( site_url() ) - 1, 1 );
	if ( $last_char == '/' ) {
		$append_me = '';
	} else {
		$append_me = '/';
	}
	$notify_url = site_url() . $append_me . '?PAYPALIPN=1';

	return $notify_url;
}

function seamless_donations_build_paypal_query_string( $post_data, $notify_url ) {
	// new posting code
	// Build the PayPal query string
	dgx_donate_debug_log( 'Building PayPal query string...' );
	$post_args = '?';

	$post_args .= 'first_name=' . urlencode( $post_data['FIRSTNAME'] ) . '&';
	$post_args .= 'last_name=' . urlencode( $post_data['LASTNAME'] ) . '&';
	$post_args .= 'address1=' . urlencode( $post_data['ADDRESS'] ) . '&';
	$post_args .= 'address2=' . urlencode( $post_data['ADDRESS2'] ) . '&';
	$post_args .= 'city=' . urlencode( $post_data['CITY'] ) . '&';
	$post_args .= 'zip=' . urlencode( $post_data['ZIP'] ) . '&';

	if ( 'US' == $post_data['COUNTRY'] ) {
		$post_args .= 'state=' . urlencode( $post_data['STATE'] ) . '&';
	} else {
		if ( 'CA' == $post_data['COUNTRY'] ) {
			$post_args .= 'state=' . urlencode( $post_data['PROVINCE'] ) . '&';
		}
	}

	$post_args .= 'country=' . urlencode( $post_data['COUNTRY'] ) . '&';
	$post_args .= 'email=' . urlencode( $post_data['EMAIL'] ) . '&';
	$post_args .= 'custom=' . urlencode( $post_data['SESSIONID'] ) . '&';
	$post_args .= 'invoice=' . urlencode( $post_data['SESSIONID'] ) . '&';

	// fill in repeating data, overriding if necessary
	dgx_donate_debug_log( 'Checking for repeat. REPEAT value is [' . $post_data['REPEATING'] . '].' );
	if ( $post_data['REPEATING'] == '' ) {
		if ( $post_data['CMD'] == '' ) {
			$post_data['CMD'] = '_donations';
		}
		$post_args .= 'amount=' . urlencode( $post_data['AMOUNT'] ) . '&';
		$post_args .= 'cmd=' . urlencode( $post_data['CMD'] ) . '&';
	} else {
		if ( $post_data['CMD'] == '' ) {
			$post_data['CMD'] = '_xclick-subscriptions';
		}
		if ( $post_data['P3'] == '' ) {
			$post_data['P3'] = '1';
		}
		if ( $post_data['T3'] == '' ) {
			$post_data['T3'] = 'M';
		}

		$post_args .= 'cmd=' . urlencode( $post_data['CMD'] ) . '&';
		$post_args .= 'p3=' . urlencode( $post_data['P3'] ) . '&';  // 1, M = monthly
		$post_args .= 't3=' . urlencode( $post_data['T3'] ) . '&';
		$post_args .= 'src=1&sra=1&'; // repeat until cancelled, retry on failure
		$post_args .= 'a3=' . urlencode( $post_data['AMOUNT'] ) . '&';
		$log_msg    = 'Enabling repeating donation, cmd=' . $post_data['CMD'];
		$log_msg   .= ', p3=' . $post_data['P3'] . ', t3=' . $post_data['T3'];
		$log_msg   .= ', a3=' . $post_data['AMOUNT'];
		dgx_donate_debug_log( $log_msg );
	}

	$paypal_mode = get_option( 'dgx_donate_paypal_server' );
	if ( $paypal_mode == false ) {
		$paypal_mode = 'SANDBOX';
	}
	if ( $paypal_mode == 'LIVE' ) {
		$paypal_email = get_option( 'dgx_donate_paypal_email_live' );
	} else {
		$paypal_email = get_option( 'dgx_donate_paypal_email_sandbox' );
	}
	$currency_code = get_option( 'dgx_donate_currency' );

	// fill in the rest of the form data, overriding if necessary
	if ( $post_data['BUSINESS'] == '' ) {
		$post_data['BUSINESS'] = $paypal_email;
	}
	if ( $post_data['NOTIFY_URL'] == '' ) {
		$post_data['NOTIFY_URL'] = $notify_url;
	}
	dgx_donate_debug_log( "Computed RETURN value: '" . $post_data['RETURN'] . "'" );

	$post_args .= 'business=' . urlencode( $post_data['BUSINESS'] ) . '&';
	$post_args .= 'return=' . urlencode( $post_data['RETURN'] ) . '&';
	$post_args .= 'notify_url=' . urlencode( $post_data['NOTIFY_URL'] ) . '&';
	$post_args .= 'item_name=' . urlencode( $post_data['ITEM_NAME'] ) . '&';
	$post_args .= 'quantity=' . urlencode( '1' ) . '&';
	$post_args .= 'currency_code=' . urlencode( $currency_code ) . '&';
	$post_args .= 'no_note=' . urlencode( '1' ) . '&';
	$post_args .= 'bn=' . urlencode( 'SeamlessDonations_SP' ) . '&';
	$post_args  = apply_filters( 'seamless_donations_paypal_checkout_data', $post_args );

	dgx_donate_debug_log( 'Returning PayPal query string.' );
	return $post_args;
}

function seamless_donations_redirect_to_paypal( $post_args, $paypal_server ) {
	dgx_donate_debug_log( 'Redirecting to PayPal... now!' );
	if ( $paypal_server == 'SANDBOX' ) {
		$form_action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	} else {
		$form_action = 'https://www.paypal.com/cgi-bin/webscr';
	}

	wp_redirect( $form_action . $post_args );
	exit;
}

