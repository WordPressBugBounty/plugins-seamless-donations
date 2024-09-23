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

function seamless_donations_sanitize_key_array( $array ) {
	foreach ( $array as $key => $value ) {
		$array[ $key ] = sanitize_key( $value );
	}
	return $array;
}

function seamless_donations_sanitize_key_preserve_case( $key ) {
	if ( is_scalar( $key ) ) {
		$sanitized_key = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key );
		return $sanitized_key;
	}
	return '';
}

function seamless_donations_get_security_status() {
	$gateway = get_option( 'dgx_donate_payment_processor_choice' );
	$status  = array();

	$required_curl_version = '7.34.0';
	$required_ssl_version  = '1.0.1';
	$required_tls_version  = '1.2';

	$status['file_get_contents_enabled'] = false; // determines if file_get_contents can be used to check the SSL page
	$status['curl_enabled']              = false; // determines if the cURL library was found and enabled
	$status['ssl_version_ok']            = false; // determines if the current SSL version is high enough
	$status['curl_version_ok']           = false; // determine if the current cURL version is high enough
	$status['tls_version_ok']            = false;
	$status['https_ipn_works']           = false; // determines if the SSL IPN is functional
	$status['http_ipn_works']            = false; // determines if the basic IPN is functional
	$status['curl_version']              = 'N/A';
	$status['ssl_version']               = 'N/A';
	$status['required_curl_version']     = $required_curl_version;
	$status['required_ssl_version']      = $required_ssl_version;
	$status['required_tls_version']      = $required_tls_version;
	$status['ipn_domain_ok']             = false;
	$status['ipn_domain_ip']             = 'N/A';
	$status['ipn_domain_url']            = 'N/A';
	$status['cache_causing_failure']     = false;
	$status['payment_ready_ok']          = false;

	$https_ipn_url = seamless_donations_get_paypal_notification_url();
	$https_ipn_url = str_ireplace( 'http://', 'https://', $https_ipn_url ); // force https check
	// $https_ipn_url .= '?status_check=true';

	// determine availability and version compatibility
	// all these calls are stfu'd because we have no idea what they'll do across the interwebs
	// this code specifically uses curl to see if curl works, because it's needed for PayPal calls
	// it's to help diagnose user issues
	if ( @function_exists( 'curl_init' ) ) {
		$status['curl_enabled'] = true;

		$ch = @curl_init();
		if ( $ch != false ) {
			$version                = @curl_version();
			$curl_compare           = @seamless_donations_version_compare( $version['version'], $required_curl_version );
			$ssl_compare            = @seamless_donations_version_compare(
				$version['ssl_version'],
				$required_ssl_version
			);
			$status['curl_version'] = $version['version'];
			$status['ssl_version']  = $version['ssl_version'];

			if ( $curl_compare != '<' ) {
				$status['curl_version_ok'] = true;
			}
			if ( $ssl_compare != '<' ) {
				$status['ssl_version_ok'] = true;
			}

			@curl_close( $ch );
		}
	}

	if ( @ini_get( 'allow_url_fopen' ) ) {
		$status['file_get_contents_enabled'] = true;
		// $test_result = @file_get_contents($https_ipn_url);
		// if ($test_result != false) {
		// $status['https_ipn_works'] = true;
		// }
	}

	if ( $status['curl_version_ok'] ) {
		// code inspired by https://gist.github.com/olivierbellone/5fbe074004059c1be5cc81408b72c7b3
		$response              = wp_remote_get( 'https://www.howsmyssl.com/a/check' );
		$data                  = wp_remote_retrieve_body( $response );
		$json                  = json_decode( $data );
		$status['tls_version'] = $json->tls_version;
		if ( $status['tls_version'] == '' ) {
			$status['tls_version'] = 'N/A';
		} else {
			$tls_compare = @seamless_donations_version_compare( $status['tls_version'], $status['required_tls_version'] );
			if ( $tls_compare != '<' ) {
				$status['tls_version_ok'] = true;
			}
		}
	}

	// check to see if domain for IPN is local or externally accessible
	$url_parts                = wp_parse_url( $https_ipn_url );
	$status['ipn_domain_url'] = $url_parts['host'];
	$ip_address               = @gethostbyname( $status['ipn_domain_url'] );
	if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE ) ) {
		$status['ipn_domain_ok'] = true;
		$status['ipn_domain_ip'] = $ip_address;
	} else {
		$status['ipn_domain_ip'] = 'N/A';
	}

	// check to see cache status
	$cache_status = get_option( 'dgx_donate_caching_causing_failure' );
	if ( $cache_status != false ) {
		$status['cache_causing_failure'] = true;
	}

	// tabulate checks to determine good or bad
	if ( $gateway == 'PAYPAL' ) {
		if ( ! $status['file_get_contents_enabled'] || ! $status['curl_enabled'] || ! $status['tls_version_ok'] ||
		// !$status['curl_version_ok'] || !$status['https_ipn_works'] or !$status['ipn_domain_ok']) {
			! $status['curl_version_ok'] || ! $status['ipn_domain_ok'] || $status['cache_causing_failure'] ) {
			$status['payment_ready_ok'] = false;
		} else {
			$status['payment_ready_ok'] = true;
		}
	}
	if ( $gateway == 'STRIPE' ) {
		if ( ! $status['file_get_contents_enabled'] || ! $status['curl_enabled'] || ! $status['tls_version_ok'] ||
			! $status['curl_version_ok'] || $status['cache_causing_failure'] ) {
			$status['payment_ready_ok'] = false;
		} else {
			$status['payment_ready_ok'] = true;
		}
	}

	return $status;
}

function seamless_donations_display_tls_status( $status ) {
	$gateway = get_option( 'dgx_donate_payment_processor_choice' );

	$msg  = '';
	$msg .= '<TABLE id="seamless_donations_tls_table">';

	if ( $status['cache_causing_failure'] ) {
		$msg     .= '<TR>';
			$msg .= '<TD>' . seamless_donations_display_fail() . '</TD><TD>';
			$msg .= ' Caching</TD><TD>Enabled on donation form';
			$msg .= '</TD><TD>';
			$msg .= '<i>Cache is causing donation session IDs to be non-unique.</i>';
			$msg .= '</TD>';
		$msg     .= '</TR>';
	}

	$msg .= '<TR>';
	if ( ! $status['file_get_contents_enabled'] ) {
		$msg .= '<TD>' . seamless_donations_display_fail() . '</TD><TD>';
		$msg .= ' allow_url_fopen</TD><TD>Not enabled';
		$msg .= '</TD><TD>';
		$msg .= '<i>This option in PHP.INI must be enabled.</i>';
		$msg .= '</TD>';
	} else {
		$msg .= '<TD>' . seamless_donations_display_pass() . '</TD><TD>';
		$msg .= ' allow_url_fopen</TD><TD>Enabled';
		$msg .= '</TD><TD>';
		$msg .= '</TD>';
	}
	$msg .= '</TR>';

	$msg .= '<TR>';
	if ( ! $status['curl_enabled'] ) {
		$msg .= '<TD>' . seamless_donations_display_fail() . '</TD><TD>';
		$msg .= ' cURL</TD><TD>Not enabled';
		$msg .= '</TD><TD>';
		$msg .= '</TD>';
	} else {
		$msg .= '<TD>' . seamless_donations_display_pass() . '</TD><TD>';
		$msg .= ' cURL</TD><TD>Enabled';
		$msg .= '</TD><TD>';
		$msg .= '</TD>';
		$msg .= '</TR>';

		$msg .= '<TR>';
		if ( ! $status['curl_version_ok'] ) {
			$msg .= '<TD>' . seamless_donations_display_fail() . '</TD><TD>';
			$msg .= ' cURL Version</TD><TD' . $status['curl_version'];
			$msg .= '</TD><TD>';
			$msg .= '<i>Required version is ' . $status['required_curl_version'] . ' or greater</i>';
			$msg .= '</TD>';
		} else {
			$msg .= '<TD>' . seamless_donations_display_pass() . '</TD><TD>';
			$msg .= ' cURL Version</TD><TD>' . $status['curl_version'];
			$msg .= '</TD><TD>';
			$msg .= '</TD>';
			$msg .= '</TR>';

			$msg .= '<TR>';
			if ( ! $status['tls_version_ok'] ) {
				$msg .= '<TD>' . seamless_donations_display_fail() . '</TD><TD>';
				$msg .= ' TLS Version</TD><TD>' . $status['tls_version'];
				$msg .= '</TD><TD>';
				$msg .= '<i>Required version is ' . $status['required_tls_version'] . ' or greater</i>';
				$msg .= '</TD>';
			} else {
				$msg .= '<TD>' . seamless_donations_display_pass() . '</TD><TD>';
				$msg .= ' TLS Version</TD><TD>' . $status['tls_version'];
				$msg .= '</TD><TD>';
				$msg .= '</TD>';
			}
			$msg .= '</TR>';
		}
	}

	if ( $gateway == 'PAYPAL' ) {
		$msg .= '<TR>';
		if ( ! $status['ipn_domain_ok'] ) {
			$msg .= '<TD>';
			$msg .= seamless_donations_display_fail();
			$msg .= '</TD><TD>';
			$msg .= ' ' . $status['ipn_domain_url'] . '</TD><TD>Unreachable';
			$msg .= '</TD><TD>';
			$msg .= '<i>This domain is not reachable from the public Internet.</i>';
			$msg .= '</TD>';
		} else {
			$msg .= '<TD>' . seamless_donations_display_pass() . '</TD><TD>';
			$msg .= ' ' . $status['ipn_domain_url'] . '</TD><TD>' . $status['ipn_domain_ip'];
			$msg .= '</TD><TD>';
			$msg .= '</TD>';
		}
		$msg .= '</TR>';
	}

	$msg .= '</TABLE>';

	return $msg;
}
