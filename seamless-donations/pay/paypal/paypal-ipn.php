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

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function seamless_donations_paypal_check_for_ipn() {
	// this is called every time Seamless Donations loads. Check if it's an IPN request
	if ( ! isset( $_GET['PAYPALIPN'] ) ) {
		//$ipn        = sanitize_text_field( $_GET['PAYPALIPN'] );
		$debug_mode = get_option( 'dgx_donate_debug_mode' );
		if ( $debug_mode == 'INSERTTRACE' ) {
			dgx_donate_debug_log( 'GET PAYPALIPN not set' );
		}
		if ( ! isset( $_POST['PAYPALIPN'] ) ) {
			//$ipn        = sanitize_text_field( $_POST['PAYPALIPN'] );
			$debug_mode = get_option( 'dgx_donate_debug_mode' );
			if ( $debug_mode == 'INSERTTRACE' ) {
				dgx_donate_debug_log( 'POST PAYPALIPN not set' );
			}
			return true;
		}
	}

	dgx_donate_debug_log( 'Getting ready to call 5.1 PayPal IPN code' );
	$seamless_donations_ipn_responder = new SeamlessDonationsPayPalIPNHandler();
	dgx_donate_debug_log( 'Returned from 5.1 PayPal IPN code' );

	/**
	 * We cannot send nothing, so send back just a simple content-type message
	 */
	status_header( 200 );
	nocache_headers();
	echo 'Seamless Donations IPN processing URL confirmed.';
	exit();
}

function seamless_donations_paypal_ipn_rewrite() {
	// flush_rewrite_rules();
	// $donate_page = get_option('dgx_donate_form_url');
	// runs rewrite only after an Seamless Donations form executed once
	// if ($donate_page != false) {
	// $rewrite_rules   = get_option('dgx_donate_rewrite_rules');
	// $current_version = get_option('dgx_donate_active_version');
	// if ($rewrite_rules == false) {
	// do the rewrite
	// update_option('dgx_donate_rewrite_rules', $current_version);
	// } else {
	// if ($rewrite_rules != $current_version) {
	// do the rewrite
	// update_option('dgx_donate_rewrite_rules', $current_version);
	// }
	// }
	// }
}

class SeamlessDonationsPayPalIPNHandler {

	var $chat_back_url  = '';
	var $host_header    = '';
	var $post_data      = array();
	var $session_id     = '';
	var $transaction_id = '';

	public function __construct() {
		// Grab all the post data
		$post = file_get_contents( 'php://input' );
		parse_str( $post, $data );
		$this->post_data = $data;

		$debug_mode = get_option( 'dgx_donate_debug_mode' );
		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'PayPal IPN debug mode is turned ON.' );
			// if (isset($_POST)) {
			// dgx_donate_debug_log('$_POST array size: ' . count($_POST));
			// } else {
			// dgx_donate_debug_log('$_POST not set.');
			// }
			// if (isset($_GET)) {
			// dgx_donate_debug_log('$_GET array size: ' . count($_GET));
			// } else {
			// dgx_donate_debug_log('$_GET not set.');
			// }
			seamless_donations_post_array_to_log();
			seamless_donations_force_a_backtrace_to_log();

			seamless_donations_server_global_to_log( 'PHP_SELF', true );
			seamless_donations_server_global_to_log( 'REQUEST_METHOD', true );
			seamless_donations_server_global_to_log( 'HTTP_REFERER', true );
			seamless_donations_server_global_to_log( 'HTTPS', true );
			seamless_donations_server_global_to_log( 'REQUEST_URI', true );
			seamless_donations_server_global_to_log( 'QUERY_STRING', true );
			seamless_donations_server_global_to_log( 'DOCUMENT_ROOT', true );
			seamless_donations_server_global_to_log( 'HTTP_ACCEPT', true );
			seamless_donations_server_global_to_log( 'HTTP_HOST', true );
			seamless_donations_server_global_to_log( 'HTTP_USER_AGENT', true );
			seamless_donations_server_global_to_log( 'REMOTE_ADDR', true );
			seamless_donations_server_global_to_log( 'REMOTE_HOST', true );
		}

		// Set up for production or test
		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'Before configure_for_production_or_test' );
		}
		$this->configure_for_production_or_test();

		// Extract the session and transaction IDs from the POST
		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'Before get_ids_from_post' );
		}
		$this->get_ids_from_post();

		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'Before checking session_id for not empty' );
		}

		// the session_id is essentially the nonce when this whole thing comes back
		// from PayPal. Note that this entire IPN mechanism is being replaced
		if ( ! empty( $this->session_id ) ) {
			dgx_donate_debug_log( '----------------------------------------' );
			dgx_donate_debug_log( 'PROCESSING PAYPAL IPN TRANSACTION (HTTPS)' );
			dgx_donate_debug_log( 'PROCESSING REST-A' );
			dgx_donate_debug_log( 'Seamless Donations Version: ' . dgx_donate_get_version() );
			$dev_build = seamless_donations_get_development_build();
			if ( $dev_build != '' ) {
				dgx_donate_debug_log( 'Build: ' . $dev_build );
			}

			$response = $this->reply_to_paypal();

			if ( 'VERIFIED' == $response ) {
				// function call in paypal code
				dgx_donate_debug_log( 'Reply to PayPal response: VERIFIED' );
				if ( $this->post_data['payment_status'] == 'Completed' ) {
					seamless_donations_process_confirmed_purchase(
						'PAYPAL',
						$this->post_data['mc_currency'],
						$this->session_id,
						$this->transaction_id,
						$this->post_data
					);
				}
				// $this->handle_verified_ipn();
			} elseif ( 'INVALID' == $response ) {
				dgx_donate_debug_log( 'Reply to PayPal response: INVALID' );
				$this->handle_invalid_ipn();
			} else {
				dgx_donate_debug_log( 'Reply to PayPal response: UNSPECIFIED' );
				$this->handle_unrecognized_ipn( $response );
			}

			do_action( 'seamless_donations_paypal_ipn_processing_complete', $this->session_id, $this->transaction_id );
			dgx_donate_debug_log( 'IPN processing complete.' );
		} else {
			if ( ! isset( $_GET['status_check'] ) ) {
				dgx_donate_debug_log( 'Null IPN (Empty session id).  Nothing to do.' );
			}
		}
		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'Exiting construct.' );
		} else {
			if ( $debug_mode == 'PAYPALIPN' ) {
				dgx_donate_debug_log( 'Session ID not processed because empty' );
			}
		}
		if ( $debug_mode == 'PAYPALIPN' ) {
			dgx_donate_debug_log( 'Leaving __construct' );
		}
	}

	function configure_for_production_or_test( $tls_or_ssl_or_curl = 'tls' ) {
		if ( 'SANDBOX' == get_option( 'dgx_donate_paypal_server' ) ) {
			$this->host_header = "Host: www.sandbox.paypal.com\r\n";
			switch ( $tls_or_ssl_or_curl ) {
				case 'tls':
					$this->chat_back_url = 'tls://www.sandbox.paypal.com';
					break;
				case 'ssl':
					$this->chat_back_url = 'ssl://www.sandbox.paypal.com:443/';
					break;
				case 'curl':
					$this->chat_back_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
					break;
			}
		} else {
			$this->host_header = "Host: www.paypal.com\r\n";
			switch ( $tls_or_ssl_or_curl ) {
				case 'tls':
					$this->chat_back_url = 'tls://www.paypal.com';
					break;
				case 'ssl':
					$this->chat_back_url = 'ssl://www.paypal.com:443/';
					break;
				case 'curl':
					$this->chat_back_url = 'https://www.paypal.com/cgi-bin/webscr';
					break;
			}
		}
	}

	function get_ids_from_post() {
		$this->session_id     = isset( $this->post_data['custom'] ) ? $this->post_data['custom'] : '';
		$this->transaction_id = isset( $this->post_data['txn_id'] ) ? $this->post_data['txn_id'] : '';
	}

	public function reply_to_paypal() {
		// based on https://github.com/paypal/ipn-code-samples/blob/master/php/PaypalIPN.php
		$required_curl_version = '7.34.0';
		dgx_donate_debug_log( 'Preparing reply to PayPal using 5.1.1 PayPal IPN chatback code...' );
		if ( ! isset( $_POST ) || sizeof( $_POST ) == 0 ) {
			dgx_donate_debug_log( 'No _POST data found...' );
			return 'INVALID DATA';
		}

		dgx_donate_debug_log( 'Preparing request array...' );
		 $request_data       = $this->post_data;
		$request_data['cmd'] = '_notify-validate';
		$req                 = http_build_query( $request_data );

		// Post the data back to PayPal, using curl. Throw exceptions if errors occur.
		dgx_donate_debug_log( 'IPN chatback attempt via cURL...' );
		$this->configure_for_production_or_test( 'curl' );
		$ch           = curl_init( $this->chat_back_url );
		$version      = curl_version();
		$curl_compare = seamless_donations_version_compare( $version['version'], $required_curl_version );

		if ( $curl_compare == '<' ) {
			curl_close( $ch );
			$ch = false; // kill the curl call
		}
		if ( $ch != false ) {
			curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $req );
			curl_setopt( $ch, CURLOPT_SSLVERSION, 6 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

			// dgx_donate_debug_log("__DIR__ : " . __DIR__);
			// This is often required if the server is missing a global cert bundle, or is using an outdated one.
			// if ($this->use_local_certs) {
			// curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cert/cacert.pem");
			// }
			curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'User-Agent: PHP-IPN-Verification-Script',
					'Connection: Close',
				)
			);
			$res = curl_exec( $ch );
			if ( ! ( $res ) ) {
				dgx_donate_debug_log(
					'IPN failed: unable to establish network chatback connection to PayPal via cURL'
				);
				dgx_donate_debug_log( 'IPN cURL error: ' . curl_error( $ch ) );
				$version = curl_version();
				dgx_donate_debug_log(
					'cURL version: ' . $version['version'] . ' OpenSSL version: ' .
					$version['ssl_version']
				);
				// https://curl.haxx.se/docs/manpage.html#--tlsv12
				// https://en.wikipedia.org/wiki/Comparison_of_TLS_implementations
				dgx_donate_debug_log( 'PayPal requires TLSv1.2, which requires cURL 7.34.0 and OpenSSL 1.0.1.' );
				dgx_donate_debug_log( 'See https://en.wikipedia.org/wiki/Comparison_of_TLS_implementations' );
				dgx_donate_debug_log( 'for minimum versions for other implementations.' );
			}

			$info      = curl_getinfo( $ch );
			$http_code = $info['http_code'];
			if ( $http_code != 200 ) {
				dgx_donate_debug_log( "IPN failed: PayPal responded with http code $http_code" );
			}

			curl_close( $ch );
			dgx_donate_debug_log( 'IPN chatback attempt via cURL completed. Checking response...' );
			dgx_donate_debug_log( "IPN result value = $res..." );

		} else {
			dgx_donate_debug_log(
				'Unable to complete chatback attempt. SSL incompatible. Consider enabling cURL library.'
			);
			dgx_donate_debug_log( 'See https://en.wikipedia.org/wiki/Comparison_of_TLS_implementations' );
			dgx_donate_debug_log( 'for minimum versions for other implementations.' );
			$res = 'INVALID';
		}
		return $res;
	}

	function handle_invalid_ipn() {
		dgx_donate_debug_log( "IPN failed (INVALID) for sessionID {$this->session_id}" );
	}

	function handle_unrecognized_ipn( $paypal_response ) {
		dgx_donate_debug_log( "IPN failed (unrecognized response) for sessionID {$this->session_id}" );
		dgx_donate_debug_log( '==> ' . $paypal_response );
	}
}


