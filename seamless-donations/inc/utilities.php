<?php

/*
 Seamless Donations by David Gewirtz, adopted from Allen Snook

 Lab Notes: http://zatzlabs.com/lab-notes/
 Plugin Page: http://zatzlabs.com/seamless-donations/
 Contact: http://zatzlabs.com/contact-us/

 Copyright (c) 2015-2023 by David Gewirtz
 */

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// quick array name-of function
// from http://php.net/manual/en/function.key.php
function seamless_donations_name_of( array $a, $pos ) {
	$temp = array_slice( $a, $pos, 1, true );

	return key( $temp );
}

// from http://www.w3schools.com/php/filter_validate_url.asp
// returns a clean URL or false
// use === false to check it
function seamless_donations_validate_url( $url ) {
	// Remove all illegal characters from a url
	$url = filter_var( $url, FILTER_SANITIZE_URL );

	// Validate url
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
		return $url;
	} else {
		return false;
	}
}

function seamless_donations_time_elapsed_string( $secs ) {
	$bit = array(
		'y' => $secs / 31556926 % 12,
		'w' => $secs / 604800 % 52,
		'd' => $secs / 86400 % 7,
		'h' => $secs / 3600 % 24,
		'm' => $secs / 60 % 60,
		's' => $secs % 60,
	);

	foreach ( $bit as $k => $v ) {
		if ( $v > 0 ) {
			$ret[] = $v . $k;
		}
	}

	return join( ' ', $ret );
}

function seamless_donations_obscurify_string( $s, $char = '*', $inner_obscure = true ) {
	$length = strlen( $s );
	if ( $length > 6 ) {
		$segment_size = intval( $length / 3 );
		$seg1         = substr( $s, 0, $segment_size );
		$seg2         = substr( $s, $segment_size, $segment_size );
		$seg3         = substr( $s, $segment_size * 2, $length - ( $segment_size * 2 ) );

		if ( $inner_obscure ) {
			$seg2 = str_repeat( $char, $segment_size );
		} else {
			$seg1 = str_repeat( $char, $segment_size );
			$seg3 = str_repeat( $char, strlen( $seg3 ) );
		}

		$s = $seg1 . $seg2 . $seg3;
	}

	return $s;
}

function seamless_donations_obscurify_stripe_key( $key ) {
	$left  = substr( $key, 0, 8 );
	$right = substr( $key, -3, 3 );
	return $left . '******************************' . $right;
}

function seamless_donations_version_compare( $ver1, $ver2 ) {
	// returns > if 1 > 2, = if 1 = 2, < if 1 < 2
	// 4.0.2 is greater than 3.1.1, but 4.0 and 4.0.0 are equal

	$p    = '/[^0-9.]/i'; // remove all alphanumerics
	$ver1 = preg_replace( $p, '', $ver1 );
	$ver2 = preg_replace( $p, '', $ver2 );

	$v1 = explode( '.', $ver1 );
	$v2 = explode( '.', $ver2 );

	// make the two arrays counts match
	$most = max(
		array(
			count( $v1 ),
			count( $v2 ),
		)
	);
	$v1   = array_pad( $v1, $most, '0' );
	$v2   = array_pad( $v2, $most, '0' );

	for ( $i = 0; $i < count( $v1 ); ++$i ) {
		if ( intval( $v1[ $i ] ) > intval( $v2[ $i ] ) ) {
			return '>';
		}
		if ( intval( $v1[ $i ] ) < intval( $v2[ $i ] ) ) {
			return '<';
		}
	}

	return '=';
}

// PRE 5.0 - obsolete with 5.0
// This function builds both options and settings based on passed arrays
// The $options_array is an array that would be passed to the addSettingsField method
// If $settings_array is passed (not false), it will create a section and add the options to that section

function seamless_donations_process_add_settings_fields_with_options(
	$options_array, $apf_object, $settings_array = array() ) {
	if ( count( $settings_array ) > 0 ) {
		$apf_object->addSettingSections( $settings_array );
		$section_id = $settings_array['section_id'];
	}

	for ( $i = 0; $i < count( $options_array ); ++$i ) {
		// read in stored options
		// by using this approach, we don't need to special-case for
		// fields and field types that don't save option data
		$option = $options_array[ $i ]['field_id'];

		$stored_option = get_option( $option, false );
		if ( $stored_option != false ) {
			$options_array[ $i ]['default'] = $stored_option;
		}

		// build up the settings field display
		if ( count( $settings_array ) > 0 ) {
			$apf_object->addSettingFields( $section_id, $options_array[ $i ] );
		} else {
			$apf_object->addSettingFields( $options_array[ $i ] );
		}
	}
}

// scans the admin UI sections, looks for a 'submit' type field named 'submit' that has a non-null value
// this is admittedly less efficient than just picking values out of the array, but it makes for
// considerably easier-to-read code for admin form processing. Given that admin submits are relatively
// rare and the array scan is short, it's a fair trade-off for more maintainable code
function seamless_donations_get_submitted_admin_section( $_the_array ) {
	$slug = sanitize_key( $_POST['page_slug'] );
	for ( $i = 0; $i < count( $_the_array ); ++$i ) {
		$key = seamless_donations_name_of( $_the_array, $i );
		if ( strpos( $key, $slug ) === 0 ) { // key begins with slug
			if ( isset( $_the_array[ $key ]['submit'] ) ) {
				if ( $_the_array[ $key ]['submit'] != null ) {
					return ( $key );
				}
			}
		}
	}

	return false;
}

function seamless_donations_get_guid( $namespace = '' ) {
	$ver = 'SDS02-'; // Session IDs now have versioning SD=Seamless Donations, S=Server, 01=first version
	// version moved to SDS02 to account for audit table and donation table scans before committing to an ID

	// based on post by redtrader http://php.net/manual/en/function.uniqid.php#107512
	$guid  = '';
	$uid   = uniqid( '', true );
	$data  = $namespace;
	$data .= isset( $_SERVER['REQUEST_TIME'] ) ? sanitize_text_field( $_SERVER['REQUEST_TIME'] ) : '';
	$data .= isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
	$data .= isset( $_SERVER['LOCAL_ADDR'] ) ? sanitize_text_field( $_SERVER['LOCAL_ADDR'] ) : '';
	$data .= isset( $_SERVER['LOCAL_PORT'] ) ? sanitize_text_field( $_SERVER['LOCAL_PORT'] ) : '';
	$data .= isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
	$data .= isset( $_SERVER['REMOTE_PORT'] ) ? sanitize_text_field( $_SERVER['REMOTE_PORT'] ) : '';
	$hash  = strtoupper( hash( 'ripemd128', $uid . $guid . md5( $data ) ) );
	$guid  = substr( $hash, 0, 8 ) . '-' . substr( $hash, 8, 4 ) . '-' . substr( $hash, 12, 4 ) . '-' .
		substr( $hash, 16, 4 ) . '-' .
		substr( $hash, 20, 12 );

	$session_id = $ver . $guid;
	// first pass of force-unique is to scan the audit table
	$loop = 0;
	while (	seamless_donations_get_audit_option($session_id) != false ) {
		$session_id = $ver . $guid . '-' . str_pad(strval($loop),7,"0",STR_PAD_LEFT);
		++$loop;
	}

	// next, we scan donations
	$donation_id = seamless_donations_get_donations_by_meta( '_dgx_donate_session_id', $session_id, 1 );
	while ( count( $donation_id ) != 0 ) {
		$session_id = $ver . $guid . '-' . str_pad(strval($loop),7,"0",STR_PAD_LEFT);
		++$loop;
		$donation_id = seamless_donations_get_donations_by_meta( '_dgx_donate_session_id', $session_id, 1 );
	}

	return $session_id;
}

function seamless_donations_get_browser_name() {
	// $path = plugin_dir_path( __FILE__ );
	// $path = dirname( dirname( dirname( dirname( $path ) ) ) ); // up the path (probably a better way)
	// $path .= '/wp-admin/includes/dashboard.php';
	// from https://artisansweb.net/detect-browser-php-javascript/
	$arr_browsers = array(
		'Opera',
		'Edge',
		'Chrome',
		'Safari',
		'Firefox',
		'MSIE',
		'Trident',
	);

	$user_browser = 'undefined';
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );

		$user_browser = '';
		foreach ( $arr_browsers as $browser ) {
			if ( strpos( $agent, $browser ) !== false ) {
				$user_browser = $browser;
				break;
			}
		}

		switch ( $user_browser ) {
			case 'MSIE':
				$user_browser = 'Internet Explorer';
				break;

			case 'Trident':
				$user_browser = 'Internet Explorer';
				break;

			case 'Edge':
				$user_browser = 'Microsoft Edge';
				break;
		}
	}

	return $user_browser;

	// $path = get_home_path();
	// $path .= 'wp-admin/includes/dashboard.php';
	//
	// require_once( $path );
	// $browser_data = wp_check_browser_version();
	//
	// isset( $browser_data['name'] ) ? $browser_name = $browser_data['name'] : $browser_name = '';
	// isset( $browser_data['version'] ) ? $browser_version = $browser_data['version'] : $browser_version = '';

	// return $browser_name . ' ' . $browser_version;
}

function seamless_donations_array_size( $array ) {
	// particularly for non-countable arrays
	$count = 0;
	if ( is_array( $array ) ) {
		foreach ( $array as $value ) {
			++$count;
		}
	}
	return $count;
}

function seamless_donations_get_ISO8601_date_x_days_ago( $dddays_ago, $paypal_sanitize = false ) {
	// https://www.php.net/manual/en/datetime.formats.relative.php
	// https://www.php.net/manual/en/function.date.php
	// '-1 day'
	// '-7 days'
	// '-30 days'
	// '-90 days'

	$relative_format    = '-' . trim( $dddays_ago ) . ' days';
	$utc_ISO8601_date   = date( DATE_ISO8601, strtotime( $relative_format ) );
	$local_date         = get_date_from_gmt( $utc_ISO8601_date );
	$local_ISO8601_date = date( DATE_ISO8601, strtotime( $local_date ) );

	// if($paypal_sanitize) {
	// for some reason, PayPal doesn't like dates ending in +0000. It prefers -0000
	// $date = str_replace("+0000", "-0000", $date);
	// }

	return $local_ISO8601_date;
}

function seamless_donations_get_seconds_between_dates( $date1, $date2 ) {
	$start  = new DateTime( $date1 );
	$end    = new DateTime( $date2 );
	$output = abs( $end->getTimestamp() - $start->getTimestamp() );

	return $output;
}

function seamless_donations_force_unset_array_by_index( $array, $index ) {
	$new_array = array();
	$size      = seamless_donations_array_size( $array );
	$count     = 0;
	for ( $i = 0; $i < $size; ++$i ) {
		if ( $index != $i ) {
			$new_array[ $count ] = $array[ $i ];
			++$count;
		}
	}
	return $new_array;
}

function seamless_donations_reindex_array( $array ) {
	$new_array = array();
	$size      = seamless_donations_array_size( $array );
	$count     = 0;
	foreach ( $array as $item ) {
		$new_array[ $count ] = $item;
		++$count;
	}
	return $new_array;
}

// https://gist.github.com/wpscholar/0deadce1bbfa4adb4e4c
function seamless_donations_insert_array_after( array $array, $key, array $new ) {
	$keys = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

// PRE 5.0, needs conversion to 5.0
// This function builds both options and settings based on passed arrays
// The $options_array is an array that would be passed to the addSettingsField method
// If $settings_array is passed (not false), it will create a section and add the options to that section
function seamless_donations_process_add_settings_fields_with_options5(
	$options_array, $apf_object, $settings_array = array()
) {
	if ( count( $settings_array ) > 0 ) {
		$apf_object->addSettingSections( $settings_array );
		$section_id = $settings_array['section_id'];
	}

	for ( $i = 0; $i < count( $options_array ); ++$i ) {
		// read in stored options
		// by using this approach, we don't need to special-case for
		// fields and field types that don't save option data
		$option = $options_array[ $i ]['field_id'];

		$stored_option = get_option( $option, false );
		if ( $stored_option != false ) {
			$options_array[ $i ]['default'] = $stored_option;
		}

		// build up the settings field display
		if ( count( $settings_array ) > 0 ) {
			$apf_object->addSettingFields( $section_id, $options_array[ $i ] );
		} else {
			$apf_object->addSettingFields( $options_array[ $i ] );
		}
	}
}

function seamless_donations_is_referred_by_page( $page ) {
	// takes the value of $args['option_key']) from calling function as parameter
	// this is the name of the admin page we're checking
	// good for seeing if self-referring, if user was redirected from the current page
	if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
		return false;
	}
	$referring_page = esc_url_raw( $_SERVER['HTTP_REFERER'] );

	$parts_list = parse_url( $referring_page );

	if ( isset( $parts_list['query'] ) ) {
		$query = $parts_list['query'];
	} else {
		$query = '';
	}

	// we could split the string to parse away the page= but why bother?
	if ( $query != 'page=' . $page ) {
		return false;
	} else {
		return true;
	}
}

function seamless_donations_cpt_list_type() {
	$request    = esc_url_raw( $_SERVER['REQUEST_URI'] );
	$parts_list = parse_url( $request );
	parse_str( $parts_list['query'], $query_parts );
	if ( isset( $query_parts['post_type'] ) ) {
		$post_type = strtolower( $query_parts['post_type'] );

		return $post_type;
	} else {
		return '';
	}
}

// label display functions

function seamless_donations_get_feature_promo( $desc, $url, $upgrade = 'UPGRADE', $break = '<BR>' ) {
	$feature_desc = htmlspecialchars( $desc );

	$promo  = $break;
	$promo .= '<span style="background-color:DarkGoldenRod; color:white;font-style:normal;text-weight:bold">';
	$promo .= '&nbsp;' . $upgrade . ':&nbsp;';
	$promo .= '</span>';
	$promo .= '<span style="color:DarkGoldenRod;font-style:normal;">';
	$promo .= '&nbsp;' . $feature_desc . ' ';
	$promo .= '<A target="_blank" HREF="' . $url . '">Learn more.</A>';
	$promo .= '</span>';

	return $promo;
}

function seamless_donations_display_label( $before = '&nbsp;', $message = 'BETA', $after = '', $background = '' ) {
	if ( $background == '' ) {
		$background = 'darkgrey';
	}
	$label  = $before . '<span style="background-color:' . $background . '; color:white;font-style:normal;text-weight:bold">';
	$label .= '&nbsp;' . $message . '&nbsp;';
	$label .= '</span>' . $after;

	return $label;
}

function seamless_donations_display_fail() {
	return seamless_donations_display_label( '&nbsp;', 'FAIL', '', 'red' );
}

function seamless_donations_display_pass() {
	return seamless_donations_display_label( '&nbsp;', 'PASS', '', 'green' );
}

// *** DATABASE REBUILD ***

function seamless_donations_rebuild_funds_index() {
	// first clear out the donations meta items
	$args        = array(
		'post_type'   => 'funds',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);
	$posts_array = get_posts( $args );

	// loop through a list of funds
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// extract the fund id from the donation and fund records
		$fund_id = $posts_array[ $i ]->ID;
		delete_post_meta( $fund_id, '_dgx_donate_donor_donations' );
		delete_post_meta( $fund_id, '_dgx_donate_fund_total' );
	}

	// then loop through the donations

	$args = array(
		'post_type'   => 'donation',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);

	$posts_array = get_posts( $args );

	// loop through a list of donations with funds attached
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// extract the fund id from the donation and fund records
		$donation_id = $posts_array[ $i ]->ID;
		$fund_name   = get_post_meta( $donation_id, '_dgx_donate_designated_fund', true );

		if ( $fund_name != '' ) {
			// todo need additional code to go in and reconstruct ids based on possible new names
			$fund    = get_page_by_title( $fund_name, 'OBJECT', 'funds' );
			$fund_id = $fund->ID;

			// update the donation record with the fund id -- also link the funds to the donations
			update_post_meta( $donation_id, '_dgx_donate_designated_fund_id', $fund_id );

			// update the donations list to point to this donation id
			seamless_donations_add_donation_id_to_fund( $fund_id, $donation_id );

			// update the donation total for the fund
			seamless_donations_add_donation_amount_to_fund_total( $donation_id, $fund_id );
		}
	}
}

function seamless_donations_recalculate_fund_total( $fund_id ) {
	$fund_total = 0.0;

	$donations_list       = get_post_meta( $fund_id, '_dgx_donate_donor_donations', true );
	$donations_list_array = explode( ',', $donations_list );

	for ( $i = 0; $i < count( $donations_list_array ); ++$i ) {
		if ( $donations_list_array[ $i ] != '' ) {
			$donation_id     = $donations_list_array[ $i ];
			$donation_amount = get_post_meta( $donation_id, '_dgx_donate_amount', true );
			if ( $donation_amount != '' ) {
				$donation_amount = floatval( $donation_amount );
				$fund_total     += $donation_amount;
			}
		}
	}
	$fund_total = strval( $fund_total );
	update_post_meta( $fund_id, '_dgx_donate_fund_total', $fund_total );
}

function seamless_donations_rebuild_donor_index() {
	// first clear out the donations meta items
	$args        = array(
		'post_type'   => 'donor',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);
	$posts_array = get_posts( $args );

	// loop through a list of donors
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// extract the donor id from the donation and fund records
		$donor_id = $posts_array[ $i ]->ID;
		delete_post_meta( $donor_id, '_dgx_donate_donor_donations' );
		delete_post_meta( $donor_id, '_dgx_donate_donor_total' );
	}

	// then loop through the donations

	$args = array(
		'post_type'   => 'donation',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);

	$posts_array = get_posts( $args );

	// loop through a list of donations with funds attached
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// extract the donor id from the donation and donor records
		$donation_id = $posts_array[ $i ]->ID;
		$first       = get_post_meta( $donation_id, '_dgx_donate_donor_first_name', true );
		$last        = get_post_meta( $donation_id, '_dgx_donate_donor_last_name', true );

		// now move that data into a donor post type
		$donor_name = sanitize_text_field( $first . ' ' . $last );

		if ( $donor_name != '' ) {
			// this code, like in funds, assumes the names haven't been changed.
			// todo need additional code to go in and reconstruct ids based on possible new names
			$donor    = get_page_by_title( $donor_name, 'OBJECT', 'donor' );
			$donor_id = $donor->ID;

			// update the donation record with the donor id -- also link the donor to the donations
			update_post_meta( $donation_id, '_dgx_donate_donor_id', $donor_id );

			// update the donations list to point to this donation id
			seamless_donations_add_donation_id_to_donor( $donation_id, $donor_id );

			// update the donation total for the donor
			seamless_donations_add_donation_amount_to_donor_total( $donation_id, $donor_id );
		}
	}
}

function seamless_donations_rebuild_donor_anon_flag() {
	// first clear out the donations meta items
	$args        = array(
		'post_type'   => 'donor',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);
	$posts_array = get_posts( $args );

	// loop through a list of donors
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// set all donors to anonymous = no
		$donor_id = $posts_array[ $i ]->ID;
		update_post_meta( $donor_id, '_dgx_donate_anonymous', 'no' );
	}

	// then loop through the donations

	$args = array(
		'post_type'   => 'donation',
		'post_status' => 'publish',
		'nopaging'    => 'true',
	);

	$posts_array = get_posts( $args );

	// loop through a list of donations
	for ( $i = 0; $i < count( $posts_array ); ++$i ) {
		// extract the donor id from the donation and donor records
		$donation_id = $posts_array[ $i ]->ID;
		$first       = get_post_meta( $donation_id, '_dgx_donate_donor_first_name', true );
		$last        = get_post_meta( $donation_id, '_dgx_donate_donor_last_name', true );
		$anon        = get_post_meta( $donation_id, '_dgx_donate_anonymous', true );

		// now move that data into a donor post type
		$donor_name = sanitize_text_field( $first . ' ' . $last );

		if ( $anon == 'on' ) {
			// this code, like in funds, assumes the names haven't been changed.
			// todo need additional code to go in and reconstruct ids based on possible new names
			$donor    = get_page_by_title( $donor_name, 'OBJECT', 'donor' );
			$donor_id = $donor->ID;

			update_post_meta( $donor_id, '_dgx_donate_anonymous', 'yes' );
		}
	}
}

function seamless_donations_recalculate_donor_total( $donor_id ) {
	$donor_total = 0.0;

	$donations_list       = get_post_meta( $donor_id, '_dgx_donate_donor_donations', true );
	$donations_list_array = explode( ',', $donations_list );

	for ( $i = 0; $i < count( $donations_list_array ); ++$i ) {
		if ( $donations_list_array[ $i ] != '' ) {
			$donation_id     = $donations_list_array[ $i ];
			$donation_amount = get_post_meta( $donation_id, '_dgx_donate_amount', true );
			if ( $donation_amount != '' ) {
				$donation_amount = floatval( $donation_amount );
				$donor_total    += $donation_amount;
			}
		}
	}
	$donor_total = strval( $donor_total );
	update_post_meta( $donor_id, '_dgx_donate_fund_total', $donor_total );
}

// *** EDD LICENSING ***

function seamless_donations_store_url() {
	return 'https://zatzlabs.com';
}

function seamless_donations_telemetry_url() {
	return 'https://zatzlabs.com';
}

function seamless_donations_get_license_key( $item ) {
	$license_key   = '';
	$license_array = unserialize( get_option( 'dgxdonate_licenses' ) );
	if ( isset( $license_array[ $item ] ) ) {
		$license_key = $license_array[ $item ];
	}

	return $license_key;
}

function seamless_donations_confirm_license_key( $key ) {
	if ( $key == '' ) {
		return false;
	}

	return true;
}

function seamless_donations_edd_activate_license( $product, $license, $url ) {
	dgx_donate_debug_log( '----------------------------------------' );
	dgx_donate_debug_log( 'LICENSE ACTIVATION STARTED' );

	// retrieve the license from the database
	$license = trim( $license );
	dgx_donate_debug_log( 'Product: ' . $product );
	dgx_donate_debug_log( 'License key: ' . seamless_donations_obscurify_string( $license ) );

	// Call the custom API.
	$response = wp_remote_get(
		add_query_arg(
			array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( $product ),
				// the name of our product in EDD
			),
			$url
		),
		array(
			'timeout'   => 15,
			'sslverify' => false,
		)
	);

	// make sure the response came back okay
	if ( is_wp_error( $response ) ) {
		dgx_donate_debug_log( 'Response error detected: ' . $response->get_error_message() );

		return false;
	}

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "active" or "inactive" <-- "valid"
	if ( isset( $license_data->license ) && $license_data->license == 'active' || $license_data->license == 'valid' ) {
		dgx_donate_debug_log( 'License check value: ' . $license_data->license );
		dgx_donate_debug_log( 'License check returning valid.' );

		return 'valid';
	}

	dgx_donate_debug_log( 'License check returning invalid.' );

	return 'invalid';
}

function seamless_donations_edd_deactivate_license( $product, $license, $url ) {
	dgx_donate_debug_log( '----------------------------------------' );
	dgx_donate_debug_log( 'LICENSE DEACTIVATION STARTED' );

	// retrieve the license from the database

	$license = trim( $license );
	dgx_donate_debug_log( 'Product: ' . $product );
	dgx_donate_debug_log( 'License key: ' . seamless_donations_obscurify_string( $license ) );

	// Call the custom API.
	$response = wp_remote_get(
		add_query_arg(
			array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_name'  => urlencode( $product ),
				// the name of our product in EDD
			),
			$url
		),
		array(
			'timeout'   => 15,
			'sslverify' => false,
		)
	);

	// make sure the response came back okay
	if ( is_wp_error( $response ) ) {
		dgx_donate_debug_log( 'Response error detected: ' . $response->get_error_message() );

		return false;
	}

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "active" or "inactive" <-- "valid"
	if ( isset( $license_data->license ) && $license_data->license == 'deactivated' ) {
		dgx_donate_debug_log( 'License check value: ' . $license_data->license );
		dgx_donate_debug_log( 'License check returning deactivated.' );

		return 'deactivated';
	}

	dgx_donate_debug_log( 'License check returning invalid.' );

	return 'invalid';
}

function dgx_donate_paypalstd_get_current_url() {
	if ( isset( $_SERVER['HTTPS'] ) && sanitize_key( $_SERVER['HTTPS'] ) == 'on' ) {
		$http = 'https';
	} else {
		$http = 'http';
	}

	$currentUrl = $http . '://' . sanitize_text_field( $_SERVER['SERVER_NAME'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );

	return $currentUrl;
}

/******************************************************************************************************/
function dgx_donate_get_giving_levels() {
	$builtinGivingLevels = array(
		1000,
		500,
		100,
		50,
		10,
		5,
	);

	// leaving as-is for compatibility
	$givingLevels = apply_filters( 'dgx_donate_giving_levels', $builtinGivingLevels );

	// check if filter results are unusable
	if ( count( $givingLevels ) == 0 ) {
		$givingLevels = array( 1000 ); // default = just $1000
	}

	return $givingLevels;
}

/******************************************************************************************************/
function dgx_donate_is_valid_giving_level( $amount ) {
	$givingLevels = dgx_donate_get_giving_levels();

	if ( in_array( $amount, $givingLevels ) ) {
		return true;
	}

	return false;
}

/******************************************************************************************************/
function dgx_donate_enable_giving_level( $amount ) {
	if ( dgx_donate_is_valid_giving_level( $amount ) ) {
		$key = dgx_donate_get_giving_level_key( $amount );
		update_option( $key, 'yes' );
	}
}

/******************************************************************************************************/
function dgx_donate_disable_giving_level( $amount ) {
	if ( dgx_donate_is_valid_giving_level( $amount ) ) {
		$key = dgx_donate_get_giving_level_key( $amount );
		delete_option( $key );
	}
}

/******************************************************************************************************/
function dgx_donate_is_giving_level_enabled( $amount ) {
	$levelEnabled = false;

	if ( dgx_donate_is_valid_giving_level( $amount ) ) {
		$key   = dgx_donate_get_giving_level_key( $amount );
		$value = get_option( $key );
		if ( ! empty( $value ) ) {
			$levelEnabled = true;
		}
	}

	return $levelEnabled;
}

function dgx_donate_get_giving_level_key( $amount ) {
	$key = 'dgx_donate_giving_level_' . $amount;

	return $key;
}

/******************************************************************************************************/
function dgx_donate_display_thank_you() {
	$output       = '<p>';
	$thankYouText = get_option( 'dgx_donate_thanks_text' );
	$thankYouText = nl2br( $thankYouText );
	$output      .= $thankYouText;
	$output      .= '</p>';

	return $output;
}

function seamless_donations_display_cancel_page( $cancel_code ) {
	if ( $cancel_code == 'true' ) {
		$output  = '<div id="seamless-donations-donation-cancelled"><p>';
		$output .= 'Donation was cancelled.';
		$output .= '</p>';
	} else {
		$output  = '<div id="seamless-donations-payment-error"><p>';
		$output .= 'A processing error occurred. See log for details.';
		$output .= '</p>';
	}
	return $output;
}

/******************************************************************************************************/
function dgx_donate_get_meta_map() {
	return array(
		'SESSIONID'        => '_dgx_donate_session_id',
		'AMOUNT'           => '_dgx_donate_amount',
		'REPEATING'        => '_dgx_donate_repeating',
		'DESIGNATED'       => '_dgx_donate_designated',
		'DESIGNATEDFUND'   => '_dgx_donate_designated_fund',
		'TRIBUTEGIFT'      => '_dgx_donate_tribute_gift',
		'MEMORIALGIFT'     => '_dgx_donate_memorial_gift',
		'HONOREENAME'      => '_dgx_donate_honoree_name',
		'HONORBYEMAIL'     => '_dgx_donate_honor_by_email',
		'HONOREEEMAILNAME' => '_dgx_donate_honoree_email_name',
		'HONOREEEMAIL'     => '_dgx_donate_honoree_email',
		'HONOREEPOSTNAME'  => '_dgx_donate_honoree_post_name',
		'HONOREEADDRESS'   => '_dgx_donate_honoree_address',
		'HONOREECITY'      => '_dgx_donate_honoree_city',
		'HONOREESTATE'     => '_dgx_donate_honoree_state',
		'HONOREEPROVINCE'  => '_dgx_donate_honoree_province',
		'HONOREECOUNTRY'   => '_dgx_donate_honoree_country',
		'HONOREEZIP'       => '_dgx_donate_honoree_zip',
		'FIRSTNAME'        => '_dgx_donate_donor_first_name',
		'LASTNAME'         => '_dgx_donate_donor_last_name',
		'PHONE'            => '_dgx_donate_donor_phone',
		'EMAIL'            => '_dgx_donate_donor_email',
		'ADDTOMAILINGLIST' => '_dgx_donate_add_to_mailing_list',
		'ADDRESS'          => '_dgx_donate_donor_address',
		'ADDRESS2'         => '_dgx_donate_donor_address2',
		'CITY'             => '_dgx_donate_donor_city',
		'STATE'            => '_dgx_donate_donor_state',
		'PROVINCE'         => '_dgx_donate_donor_province',
		'COUNTRY'          => '_dgx_donate_donor_country',
		'ZIP'              => '_dgx_donate_donor_zip',
		'INCREASETOCOVER'  => '_dgx_donate_increase_to_cover',
		'ANONYMOUS'        => '_dgx_donate_anonymous',
		'PAYMENTMETHOD'    => '_dgx_donate_payment_method',
		'EMPLOYERMATCH'    => '_dgx_donate_employer_match',
		'EMPLOYERNAME'     => '_dgx_donate_employer_name',
		'OCCUPATION'       => '_dgx_donate_occupation',
		'UKGIFTAID'        => '_dgx_donate_uk_gift_aid',
	);
}

/******************************************************************************************************/
function dgx_donate_create_empty_donation_record() {
	dgx_donate_debug_log( 'Creating donation record...' );
	$post_type = 'donation';
	dgx_donate_debug_log( "...of type $post_type" );

	// Get all the dates - timezone fix thanks to pkwooster
	dgx_donate_debug_log( '== getting date data...' );
	$gmt_offset = -get_option( 'gmt_offset' );
	dgx_donate_debug_log( '== getting time zone...' );
	$php_time_zone = date_default_timezone_get();
	if ( $gmt_offset > 0 ) {
		$time_zone = 'Etc/GMT+' . $gmt_offset;
	} else {
		$time_zone = 'Etc/GMT' . $gmt_offset;
	}
	date_default_timezone_set( $time_zone );

	dgx_donate_debug_log( '== setting date formats...' );
	$year           = date( 'Y' );
	$month          = date( 'm' );
	$day            = date( 'd' );
	$year_month_day = date( 'Y-m-d' );
	$time           = date( 'g:i:s A' );
	$date_time      = date( 'Y-m-d H:i:s' );
	dgx_donate_debug_log( "==== Year: $year, Month: $month, Day: $day, Year-Month-Day: $year_month_day" );
	dgx_donate_debug_log( "==== Time: $time, Date-Time: $date_time" );

	// set the PHP timezone back the way it was
	dgx_donate_debug_log( "== setting default time zone format...$php_time_zone" );
	date_default_timezone_set( $php_time_zone );

	// the title is Lastname, Firstname (YYYY-MM-dd)
	$post_title = $date_time;

	$new_donation = array(
		'post_title'   => $post_title,
		'post_content' => '',
		'post_status'  => 'publish',
		'post_date'    => $date_time,
		'post_author'  => '1',
		'post_type'    => $post_type,
	);

	dgx_donate_debug_log( '== Preparing to create donation record...' );
	dgx_donate_debug_log( "==== Post_title: $post_title" );
	dgx_donate_debug_log( "==== Date_time: $date_time" );
	dgx_donate_debug_log( "==== Post_type: $post_type" );

	$post_author = get_user_by( 'id', '1' );
	if ( empty( $post_author ) ) {
		dgx_donate_debug_log( 'User ID QUOTE-1 not found (empty)' );
	}
	if ( $post_author === false ) {
		dgx_donate_debug_log( 'User ID QUOTE-1 not found (false)' );
	}
	$post_author = get_user_by( 'id', 1 );
	if ( empty( $post_author ) ) {
		dgx_donate_debug_log( 'User ID NUMERIC-1 not found (empty)' );
	}
	if ( $post_author === false ) {
		dgx_donate_debug_log( 'User ID NUMERIC-1 not found (false)' );
	}

	// seamless_donations_send_to_wp_debug_log("SD: Inserting empty donation record...");
	$debug_mode = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_mode == 'INSERTTRACE' ) {
		dgx_donate_debug_log( '++++++ Beginning insert trace' );
		$donation_id = dgx_donate_debug_wp_insert_post( $new_donation, true );
	} else {
		if ( $debug_mode == 'DUMPINSERT' ) {
			dgx_donate_debug_log( '=== New Donation array:' );
			$user_id = get_user_by( 'id', 1 );
			if ( $user_id === false ) {
				dgx_donate_debug_log( '=== User ID 1: not found' );
			} else {
				dgx_donate_debug_log( '=== User ID 1: found' );
			}
			seamless_donations_printr_to_log( $new_donation );
		}
		dgx_donate_debug_log( '=== Processing standard insert' );
		$donation_id = wp_insert_post( $new_donation, true );

	}
	// seamless_donations_send_to_wp_debug_log("SD: Returned from inserting empty donation record.");
	if ( is_wp_error( $donation_id ) ) {
		dgx_donate_debug_log( '==== Errors were found...' );
		$errors = $donation_id->get_error_messages();
		foreach ( $errors as $error ) {
			dgx_donate_debug_log( '==== Errors were found: ' . $error );
		}
		dgx_donate_debug_log( '==== Error list complete.' );
	}
	dgx_donate_debug_log( '== Donation record created, donation ID: ' . $donation_id );

	// Save some meta
	update_post_meta( $donation_id, '_dgx_donate_year', $year );
	update_post_meta( $donation_id, '_dgx_donate_month', $month );
	update_post_meta( $donation_id, '_dgx_donate_day', $day );
	update_post_meta( $donation_id, '_dgx_donate_time', $time );
	dgx_donate_debug_log( '== Post meta updated, returning donation ID: ' . $donation_id );

	return $donation_id;
}

function seamless_donations_get_donations_by_meta( $meta_key, $meta_value, $count ) {
	$post_type    = 'donation';
	$donation_ids = array();

	if ( ! empty( $meta_value ) ) {
		$args = array(
			'numberposts' => $count,
			'post_type'   => $post_type,
			'meta_key'    => $meta_key,
			'meta_value'  => $meta_value,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
		);

		$my_donations = get_posts( $args );

		foreach ( $my_donations as $donation ) {
			$donation_ids[] = $donation->ID;
		}
	}

	return $donation_ids;
}

/* ***************** TEMP CODE FOR TESTING ******************** */
function dgx_donate_debug_wp_insert_post( $postarr, $wp_error = false, $fire_after_hooks = true ) {
	global $wpdb;
	dgx_donate_debug_log( '+++ ENTERING wp_insert_post +++' );
	// Capture original pre-sanitized array for passing into filters.
	$unsanitized_postarr = $postarr;

	dgx_donate_debug_log( '+++ get_current_user_id...' );
	$user_id = get_current_user_id();
	dgx_donate_debug_log( "+++ User id: $user_id" );

	$defaults = array(
		'post_author'           => $user_id,
		'post_content'          => '',
		'post_content_filtered' => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => 'draft',
		'post_type'             => 'post',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_parent'           => 0,
		'menu_order'            => 0,
		'guid'                  => '',
		'import_id'             => 0,
		'context'               => '',
		'post_date'             => '',
		'post_date_gmt'         => '',
	);

	dgx_donate_debug_log( '+++ wp_parse_args...' );
	$postarr = wp_parse_args( $postarr, $defaults );

	dgx_donate_debug_log( '+++ unset A...' );
	unset( $postarr['filter'] );

	dgx_donate_debug_log( '+++ sanitize_post...' );
	$postarr = sanitize_post( $postarr, 'db' );

	// Are we updating or creating?
	dgx_donate_debug_log( '+++ updating or creating?' );
	$post_ID = 0;
	$update  = false;
	$guid    = $postarr['guid'];

	if ( ! empty( $postarr['ID'] ) ) {
		dgx_donate_debug_log( '+++ postarr[ID] not empty...' );
		$update = true;

		// Get the post ID and GUID.
		$post_ID = $postarr['ID'];
		dgx_donate_debug_log( '+++ getting post...' );
		$post_before = get_post( $post_ID );

		if ( is_null( $post_before ) ) {
			dgx_donate_debug_log( '+++ post is null...' );
			if ( $wp_error ) {
				dgx_donate_debug_log( '+++ there is a wp_error, returning...' );
				return new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) );
			}
			dgx_donate_debug_log( '+++ no error, returning 0...' );
			return 0;
		}

		dgx_donate_debug_log( '+++ getting post fields....' );
		$guid            = get_post_field( 'guid', $post_ID );
		$previous_status = get_post_field( 'post_status', $post_ID );
	} else {
		dgx_donate_debug_log( '+++ postarr[ID] empty...' );
		$previous_status = 'new';
		$post_before     = null;
	}

	dgx_donate_debug_log( '+++ setting post_type...' );
	$post_type = empty( $postarr['post_type'] ) ? 'post' : $postarr['post_type'];

	$post_title   = $postarr['post_title'];
	$post_content = $postarr['post_content'];
	$post_excerpt = $postarr['post_excerpt'];

	if ( isset( $postarr['post_name'] ) ) {
		$post_name = $postarr['post_name'];
	} elseif ( $update ) {
		// For an update, don't modify the post_name if it wasn't supplied as an argument.
		$post_name = $post_before->post_name;
	}

	dgx_donate_debug_log( '+++ processing maybe_empty...' );
	$maybe_empty = 'attachment' !== $post_type
		&& ! $post_content && ! $post_title && ! $post_excerpt
		&& post_type_supports( $post_type, 'editor' )
		&& post_type_supports( $post_type, 'title' )
		&& post_type_supports( $post_type, 'excerpt' );

	/**
	 * Filters whether the post should be considered "empty".
	 *
	 * The post is considered "empty" if both:
	 * 1. The post type supports the title, editor, and excerpt fields
	 * 2. The title, editor, and excerpt fields are all empty
	 *
	 * Returning a truthy value from the filter will effectively short-circuit
	 * the new post being inserted and return 0. If $wp_error is true, a WP_Error
	 * will be returned instead.
	 *
	 * @param bool $maybe_empty Whether the post should be considered "empty".
	 * @param array $postarr    Array of post data.
	 * @since 3.3.0
	 */
	dgx_donate_debug_log( '+++ before apply filters wp_insert_post_empty_content...' );
	if ( apply_filters( 'wp_insert_post_empty_content', $maybe_empty, $postarr ) ) {
		dgx_donate_debug_log( '+++ inside apply filters wp_insert_post_empty_content...' );
		if ( $wp_error ) {
			dgx_donate_debug_log( '+++ inside apply filters wp_insert_post_empty_content...error empty content' );
			return new WP_Error( 'empty_content', __( 'Content, title, and excerpt are empty.' ) );
		} else {
			dgx_donate_debug_log( '+++ inside apply filters wp_insert_post_empty_content...returning 0' );
			return 0;
		}
	}

	dgx_donate_debug_log( '+++ preparing post_status variables...' );
	$post_status = empty( $postarr['post_status'] ) ? 'draft' : $postarr['post_status'];

	if ( 'attachment' === $post_type && ! in_array(
		$post_status,
		array(
			'inherit',
			'private',
			'trash',
			'auto-draft',
		),
		true
	) ) {
		$post_status = 'inherit';
	}

	if ( ! empty( $postarr['post_category'] ) ) {
		// Filter out empty terms.
		$post_category = array_filter( $postarr['post_category'] );
	}

	// Make sure we set a valid category.
	if ( empty( $post_category ) || 0 === count( $post_category ) || ! is_array( $post_category ) ) {
		// 'post' requires at least one category.
		if ( 'post' === $post_type && 'auto-draft' !== $post_status ) {
			dgx_donate_debug_log( '+++ before getting default_category option' );
			$post_category = array( get_option( 'default_category' ) );
		} else {
			$post_category = array();
		}
	}

	/*
	 * Don't allow contributors to set the post slug for pending review posts.
	 *
	 * For new posts check the primitive capability, for updates check the meta capability.
	 */
	dgx_donate_debug_log( '+++ getting post object type....' );
	$post_type_object = get_post_type_object( $post_type );

	dgx_donate_debug_log( '+++ getting current_user_can...' );
	if ( ! $update && 'pending' === $post_status && ! current_user_can( $post_type_object->cap->publish_posts ) ) {
		$post_name = '';
	} elseif ( $update && 'pending' === $post_status && ! current_user_can( 'publish_post', $post_ID ) ) {
		$post_name = '';
	}

	/*
	 * Create a valid post name. Drafts and pending posts are allowed to have
	 * an empty post name.
	 */
	dgx_donate_debug_log( '+++ Before if empty post name...' );
	if ( empty( $post_name ) ) {
		dgx_donate_debug_log( '+++ in empty post name...' );
		if ( ! in_array(
			$post_status,
			array(
				'draft',
				'pending',
				'auto-draft',
			),
			true
		) ) {
			$post_name = sanitize_title( $post_title );
		} else {
			$post_name = '';
		}
	} else {
		dgx_donate_debug_log( '+++ post name is not empty....' );
		// On updates, we need to check to see if it's using the old, fixed sanitization context.
		$check_name = sanitize_title( $post_name, '', 'old-save' );

		if ( $update && strtolower( urlencode( $post_name ) ) == $check_name && get_post_field( 'post_name', $post_ID ) == $check_name ) {
			$post_name = $check_name;
		} else { // new post, or slug has changed.
			$post_name = sanitize_title( $post_name );
		}
	}
	dgx_donate_debug_log( '+++ after sanitizing title' );

	/*
	 * Resolve the post date from any provided post date or post date GMT strings;
	 * if none are provided, the date will be set to now.
	 */
	dgx_donate_debug_log( '+++ resolving post date....' );
	$post_date = wp_resolve_post_date( $postarr['post_date'], $postarr['post_date_gmt'] );
	if ( ! $post_date ) {
		dgx_donate_debug_log( '+++ invalid post date' );
		if ( $wp_error ) {
			dgx_donate_debug_log( '+++ invalid post date error' );
			return new WP_Error( 'invalid_date', __( 'Invalid date.' ) );
		} else {
			dgx_donate_debug_log( '+++ invalid post date returning 0' );
			return 0;
		}
	}

	dgx_donate_debug_log( '+++ before data manipulation block...' );
	if ( empty( $postarr['post_date_gmt'] ) || '0000-00-00 00:00:00' === $postarr['post_date_gmt'] ) {
		if ( ! in_array( $post_status, get_post_stati( array( 'date_floating' => true ) ), true ) ) {
			$post_date_gmt = get_gmt_from_date( $post_date );
		} else {
			$post_date_gmt = '0000-00-00 00:00:00';
		}
	} else {
		$post_date_gmt = $postarr['post_date_gmt'];
	}

	if ( $update || '0000-00-00 00:00:00' === $post_date ) {
		$post_modified     = current_time( 'mysql' );
		$post_modified_gmt = current_time( 'mysql', 1 );
	} else {
		$post_modified     = $post_date;
		$post_modified_gmt = $post_date_gmt;
	}

	if ( 'attachment' !== $post_type ) {
		$now = gmdate( 'Y-m-d H:i:s' );

		if ( 'publish' === $post_status ) {
			if ( strtotime( $post_date_gmt ) - strtotime( $now ) >= MINUTE_IN_SECONDS ) {
				$post_status = 'future';
			}
		} elseif ( 'future' === $post_status ) {
			if ( strtotime( $post_date_gmt ) - strtotime( $now ) < MINUTE_IN_SECONDS ) {
				$post_status = 'publish';
			}
		}
	}
	dgx_donate_debug_log( '+++ after date manipulation block' );

	// Comment status.
	if ( empty( $postarr['comment_status'] ) ) {
		if ( $update ) {
			$comment_status = 'closed';
		} else {
			$comment_status = get_default_comment_status( $post_type );
		}
	} else {
		$comment_status = $postarr['comment_status'];
	}
	dgx_donate_debug_log( '+++ after comment_status' );

	// These variables are needed by compact() later.
	$post_content_filtered = $postarr['post_content_filtered'];
	$post_author           = isset( $postarr['post_author'] ) ? $postarr['post_author'] : $user_id;
	$ping_status           = empty( $postarr['ping_status'] ) ? get_default_comment_status( $post_type, 'pingback' ) : $postarr['ping_status'];
	$to_ping               = isset( $postarr['to_ping'] ) ? sanitize_trackback_urls( $postarr['to_ping'] ) : '';
	$pinged                = isset( $postarr['pinged'] ) ? $postarr['pinged'] : '';
	$import_id             = isset( $postarr['import_id'] ) ? $postarr['import_id'] : 0;
	dgx_donate_debug_log( '+++ after setting variables' );

	/*
	 * The 'wp_insert_post_parent' filter expects all variables to be present.
	 * Previously, these variables would have already been extracted
	 */
	if ( isset( $postarr['menu_order'] ) ) {
		$menu_order = (int) $postarr['menu_order'];
	} else {
		$menu_order = 0;
	}

	$post_password = isset( $postarr['post_password'] ) ? $postarr['post_password'] : '';
	if ( 'private' === $post_status ) {
		$post_password = '';
	}

	if ( isset( $postarr['post_parent'] ) ) {
		$post_parent = (int) $postarr['post_parent'];
	} else {
		$post_parent = 0;
	}

	dgx_donate_debug_log( '+++ after setting post_parent variables, before array_merge....' );
	$new_postarr = array_merge(
		array(
			'ID' => $post_ID,
		),
		compact(
			array_diff(
				array_keys( $defaults ),
				array(
					'context',
					'filter',
				)
			)
		)
	);
	dgx_donate_debug_log( '+++ after array merge' );
	/**
	 * Filters the post parent -- used to check for and prevent hierarchy loops.
	 *
	 * @param int $post_parent   Post parent ID.
	 * @param int $post_ID       Post ID.
	 * @param array $new_postarr Array of parsed post data.
	 * @param array $postarr     Array of sanitized, but otherwise unmodified post data.
	 * @since 3.1.0
	 */
	dgx_donate_debug_log( '+++ before filter wp_insert_post_parent' );
	$post_parent = apply_filters( 'wp_insert_post_parent', $post_parent, $post_ID, $new_postarr, $postarr );
	dgx_donate_debug_log( '+++ after filter wp_insert_post_parent' );
	/*
	 * If the post is being untrashed and it has a desired slug stored in post meta,
	 * reassign it.
	 */
	dgx_donate_debug_log( '+++ Attempting to untrash....' );
	if ( 'trash' === $previous_status && 'trash' !== $post_status ) {
		$desired_post_slug = get_post_meta( $post_ID, '_wp_desired_post_slug', true );

		if ( $desired_post_slug ) {
			delete_post_meta( $post_ID, '_wp_desired_post_slug' );
			$post_name = $desired_post_slug;
		}
	}
	dgx_donate_debug_log( '+++ Checking for trasn slug...' );
	// If a trashed post has the desired slug, change it and let this post have it.
	if ( 'trash' !== $post_status && $post_name ) {
		/**
		 * Filters whether or not to add a `__trashed` suffix to trashed posts that match the name of the updated post.
		 *
		 * @param bool $add_trashed_suffix Whether to attempt to add the suffix.
		 * @param string $post_name        The name of the post being updated.
		 * @param int $post_ID             Post ID.
		 * @since 5.4.0
		 */
		dgx_donate_debug_log( '+++ Before applying trash filters' );
		$add_trashed_suffix = apply_filters( 'add_trashed_suffix_to_trashed_posts', true, $post_name, $post_ID );
		dgx_donate_debug_log( '+++ After applying trash filters' );
		if ( $add_trashed_suffix ) {
			dgx_donate_debug_log( '+++ adding trashed suffix A' );
			wp_add_trashed_suffix_to_post_name_for_trashed_posts( $post_name, $post_ID );
		}
	}
	dgx_donate_debug_log( '+++ adding trashed suffix B' );
	// When trashing an existing post, change its slug to allow non-trashed posts to use it.
	if ( 'trash' === $post_status && 'trash' !== $previous_status && 'new' !== $previous_status ) {
		$post_name = wp_add_trashed_suffix_to_post_name_for_post( $post_ID );
	}

	dgx_donate_debug_log( '+++ getting unique post slug' );
	$post_name = wp_unique_post_slug( $post_name, $post_ID, $post_status, $post_type, $post_parent );

	// Don't unslash.
	dgx_donate_debug_log( "+++ don't unslash" );
	$post_mime_type = isset( $postarr['post_mime_type'] ) ? $postarr['post_mime_type'] : '';

	dgx_donate_debug_log( '+++ slash and compact' );
	// Expected_slashed (everything!).
	$data = compact( 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_content_filtered', 'post_title', 'post_excerpt', 'post_status', 'post_type', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type', 'guid' );

	$emoji_fields = array(
		'post_title',
		'post_content',
		'post_excerpt',
	);

	dgx_donate_debug_log( '+++ process emoji field' );
	foreach ( $emoji_fields as $emoji_field ) {
		if ( isset( $data[ $emoji_field ] ) ) {
			$charset = $wpdb->get_col_charset( $wpdb->posts, $emoji_field );

			if ( 'utf8' === $charset ) {
				$data[ $emoji_field ] = wp_encode_emoji( $data[ $emoji_field ] );
			}
		}
	}

	if ( 'attachment' === $post_type ) {
		/**
		 * Filters attachment post data before it is updated in or added to the database.
		 *
		 * @param array $data                An array of slashed, sanitized, and processed attachment post data.
		 * @param array $postarr             An array of slashed and sanitized attachment post data, but not processed.
		 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed attachment post data
		 *                                   as originally passed to wp_insert_post().
		 * @since 5.4.1 `$unsanitized_postarr` argument added.
		 *
		 * @since 3.9.0
		 */
		dgx_donate_debug_log( '+++ before insert attachment filter' );
		$data = apply_filters( 'wp_insert_attachment_data', $data, $postarr, $unsanitized_postarr );
		dgx_donate_debug_log( '+++ after insert attachment filter' );
	} else {
		/**
		 * Filters slashed post data just before it is inserted into the database.
		 *
		 * @param array $data                An array of slashed, sanitized, and processed post data.
		 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
		 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
		 *                                   originally passed to wp_insert_post().
		 * @since 5.4.1 `$unsanitized_postarr` argument added.
		 *
		 * @since 2.7.0
		 */
		dgx_donate_debug_log( '+++ before insert post data filter' );
		$data = apply_filters( 'wp_insert_post_data', $data, $postarr, $unsanitized_postarr );
		dgx_donate_debug_log( '+++ after insert post data filter' );
	}

	dgx_donate_debug_log( '+++ unslash it' );
	$data  = wp_unslash( $data );
	$where = array( 'ID' => $post_ID );

	if ( $update ) {
		/**
		 * Fires immediately before an existing post is updated in the database.
		 *
		 * @param int $post_ID Post ID.
		 * @param array $data  Array of unslashed post data.
		 * @since 2.5.0
		 */
		dgx_donate_debug_log( '+++ before pre_post_update action' );
		do_action( 'pre_post_update', $post_ID, $data );
		dgx_donate_debug_log( '+++ after pre_post update action' );
		if ( false === $wpdb->update( $wpdb->posts, $data, $where ) ) {
			dgx_donate_debug_log( "+++ the update didn't work" );
			if ( $wp_error ) {
				if ( 'attachment' === $post_type ) {
					$message = __( 'Could not update attachment in the database.' );
				} else {
					$message = __( 'Could not update post in the database.' );
				}
				dgx_donate_debug_log( '+++ Error:' . $message );
				return new WP_Error( 'db_update_error', $message, $wpdb->last_error );
			} else {
				dgx_donate_debug_log( '+++ Error: returning 0' );
				return 0;
			}
		}
	} else {
		// If there is a suggested ID, use it if not already present.
		dgx_donate_debug_log( '+++ checking for suggested ID' );
		if ( ! empty( $import_id ) ) {
			$import_id = (int) $import_id;
			dgx_donate_debug_log( '+++ ID from query' );
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $import_id ) ) ) {
				$data['ID'] = $import_id;
			}
		}

		dgx_donate_debug_log( '+++ before insert if' );
		if ( false === $wpdb->insert( $wpdb->posts, $data ) ) {
			if ( $wp_error ) {
				if ( 'attachment' === $post_type ) {
					$message = __( 'Could not insert attachment into the database.' );
				} else {
					$message = __( 'Could not insert post into the database.' );
				}
				dgx_donate_debug_log( '+++ insert error:' . $message );
				return new WP_Error( 'db_insert_error', $message, $wpdb->last_error );
			} else {
				dgx_donate_debug_log( '+++ Error: returning 0' );
				return 0;
			}
		}

		dgx_donate_debug_log( '+++ getting post id' );
		$post_ID = (int) $wpdb->insert_id;
		dgx_donate_debug_log( ( '+++ post id is ' . $post_ID ) );

		// Use the newly generated $post_ID.
		$where = array( 'ID' => $post_ID );
	}

	dgx_donate_debug_log( '+++ if data post name' );
	if ( empty( $data['post_name'] ) && ! in_array(
		$data['post_status'],
		array(
			'draft',
			'pending',
			'auto-draft',
		),
		true
	) ) {
		$data['post_name'] = wp_unique_post_slug( sanitize_title( $data['post_title'], $post_ID ), $post_ID, $data['post_status'], $post_type, $post_parent );

		$wpdb->update( $wpdb->posts, array( 'post_name' => $data['post_name'] ), $where );
		clean_post_cache( $post_ID );
	}

	dgx_donate_debug_log( '+++ if is object in taxonomy' );
	if ( is_object_in_taxonomy( $post_type, 'category' ) ) {
		wp_set_post_categories( $post_ID, $post_category );
	}

	dgx_donate_debug_log( '+++ if isset tags input' );
	if ( isset( $postarr['tags_input'] ) && is_object_in_taxonomy( $post_type, 'post_tag' ) ) {
		wp_set_post_tags( $post_ID, $postarr['tags_input'] );
	}

	// Add default term for all associated custom taxonomies.
	dgx_donate_debug_log( '+++ checking auto-draft' );
	if ( 'auto-draft' !== $post_status ) {
		foreach ( get_object_taxonomies( $post_type, 'object' ) as $taxonomy => $tax_object ) {
			if ( ! empty( $tax_object->default_term ) ) {
				// Filter out empty terms.
				if ( isset( $postarr['tax_input'][ $taxonomy ] ) && is_array( $postarr['tax_input'][ $taxonomy ] ) ) {
					$postarr['tax_input'][ $taxonomy ] = array_filter( $postarr['tax_input'][ $taxonomy ] );
				}

				// Passed custom taxonomy list overwrites the existing list if not empty.
				$terms = wp_get_object_terms( $post_ID, $taxonomy, array( 'fields' => 'ids' ) );
				if ( ! empty( $terms ) && empty( $postarr['tax_input'][ $taxonomy ] ) ) {
					$postarr['tax_input'][ $taxonomy ] = $terms;
				}

				if ( empty( $postarr['tax_input'][ $taxonomy ] ) ) {
					$default_term_id = get_option( 'default_term_' . $taxonomy );
					if ( ! empty( $default_term_id ) ) {
						$postarr['tax_input'][ $taxonomy ] = array( (int) $default_term_id );
					}
				}
			}
		}
	}

	dgx_donate_debug_log( '+++ new style custom taxonomies' );
	// New-style support for all custom taxonomies.
	if ( ! empty( $postarr['tax_input'] ) ) {
		foreach ( $postarr['tax_input'] as $taxonomy => $tags ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );

			if ( ! $taxonomy_obj ) {
				/* translators: %s: Taxonomy name. */
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html( 'Invalid taxonomy: %s.' ) ), '4.4.0' );
				continue;
			}

			// array = hierarchical, string = non-hierarchical.
			if ( is_array( $tags ) ) {
				$tags = array_filter( $tags );
			}

			if ( current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
				wp_set_post_terms( $post_ID, $tags, $taxonomy );
			}
		}
	}

	dgx_donate_debug_log( '+++ updating meta input....' );
	if ( ! empty( $postarr['meta_input'] ) ) {
		foreach ( $postarr['meta_input'] as $field => $value ) {
			update_post_meta( $post_ID, $field, $value );
		}
	}

	dgx_donate_debug_log( '+++ getting post field' );
	$current_guid = get_post_field( 'guid', $post_ID );

	// Set GUID.
	dgx_donate_debug_log( '+++ updating guid' );
	if ( ! $update && '' === $current_guid ) {
		$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_ID ) ), $where );
	}

	dgx_donate_debug_log( '+++ more attachment manipulation' );
	if ( 'attachment' === $postarr['post_type'] ) {
		if ( ! empty( $postarr['file'] ) ) {
			update_attached_file( $post_ID, $postarr['file'] );
		}

		if ( ! empty( $postarr['context'] ) ) {
			add_post_meta( $post_ID, '_wp_attachment_context', $postarr['context'], true );
		}
	}

	// Set or remove featured image.
	dgx_donate_debug_log( '+++ before checking featured image' );
	if ( isset( $postarr['_thumbnail_id'] ) ) {
		$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) || 'revision' === $post_type;

		if ( ! $thumbnail_support && 'attachment' === $post_type && $post_mime_type ) {
			if ( wp_attachment_is( 'audio', $post_ID ) ) {
				$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
			} elseif ( wp_attachment_is( 'video', $post_ID ) ) {
				$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
			}
		}

		if ( $thumbnail_support ) {
			$thumbnail_id = (int) $postarr['_thumbnail_id'];
			if ( -1 === $thumbnail_id ) {
				delete_post_thumbnail( $post_ID );
			} else {
				set_post_thumbnail( $post_ID, $thumbnail_id );
			}
		}
	}

	dgx_donate_debug_log( '+++ before cleaning post cache' );
	clean_post_cache( $post_ID );

	dgx_donate_debug_log( '+++ before getting post' );
	$post = get_post( $post_ID );

	dgx_donate_debug_log( '+++ before page template magic' );
	if ( ! empty( $postarr['page_template'] ) ) {
		$post->page_template = $postarr['page_template'];
		$page_templates      = wp_get_theme()->get_page_templates( $post );

		if ( 'default' !== $postarr['page_template'] && ! isset( $page_templates[ $postarr['page_template'] ] ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'invalid_page_template', __( 'Invalid page template.' ) );
			}

			update_post_meta( $post_ID, '_wp_page_template', 'default' );
		} else {
			update_post_meta( $post_ID, '_wp_page_template', $postarr['page_template'] );
		}
	}

	dgx_donate_debug_log( '+++ before post processing' );
	if ( 'attachment' !== $postarr['post_type'] ) {
		dgx_donate_debug_log( '+++ Before WordPress transition post status' );
		wp_transition_post_status( $data['post_status'], $previous_status, $post );
	} else {
		if ( $update ) {
			/**
			 * Fires once an existing attachment has been updated.
			 *
			 * @param int $post_ID Attachment ID.
			 * @since 2.0.0
			 */
			dgx_donate_debug_log( '+++ before edit_attacment action' );
			do_action( 'edit_attachment', $post_ID );
			dgx_donate_debug_log( '+++ after edit attachment action' );

			$post_after = get_post( $post_ID );

			/**
			 * Fires once an existing attachment has been updated.
			 *
			 * @param int $post_ID         Post ID.
			 * @param WP_Post $post_after  Post object following the update.
			 * @param WP_Post $post_before Post object before the update.
			 * @since 4.4.0
			 */
			dgx_donate_debug_log( '+++ before attachment updated action' );
			do_action( 'attachment_updated', $post_ID, $post_after, $post_before );
			dgx_donate_debug_log( '+++ after attachment updated action' );
		} else {
			/**
			 * Fires once an attachment has been added.
			 *
			 * @param int $post_ID Attachment ID.
			 * @since 2.0.0
			 */
			dgx_donate_debug_log( '+++> before add attachment action' );
			do_action( 'add_attachment', $post_ID );
			dgx_donate_debug_log( '+++ after add attachment action' );
		}

		dgx_donate_debug_log( '+++ returning post id:' . $post_ID );
		return $post_ID;
	}

	if ( $update ) {
		dgx_donate_debug_log( '+++ inside if update' );
		/**
		 * Fires once an existing post has been updated.
		 *
		 * The dynamic portion of the hook name, `$post->post_type`, refers to
		 * the post type slug.
		 *
		 * @param int $post_ID  Post ID.
		 * @param WP_Post $post Post object.
		 * @since 5.1.0
		 */
		dgx_donate_debug_log( '+++ before action edit post B' );
		do_action( "edit_post_{$post->post_type}", $post_ID, $post );
		dgx_donate_debug_log( '+++ after action edit post B' );

		/**
		 * Fires once an existing post has been updated.
		 *
		 * @param int $post_ID  Post ID.
		 * @param WP_Post $post Post object.
		 * @since 1.2.0
		 */
		dgx_donate_debug_log( '+++ before action edit post C' );
		do_action( 'edit_post', $post_ID, $post );
		dgx_donate_debug_log( '+++ after action edit post C' );

		$post_after = get_post( $post_ID );

		/**
		 * Fires once an existing post has been updated.
		 *
		 * @param int $post_ID         Post ID.
		 * @param WP_Post $post_after  Post object following the update.
		 * @param WP_Post $post_before Post object before the update.
		 * @since 3.0.0
		 */
		dgx_donate_debug_log( '+++ before action post updated B' );
		do_action( 'post_updated', $post_ID, $post_after, $post_before );
		dgx_donate_debug_log( '+++ after action post updated B' );
	}

	/**
	 * Fires once a post has been saved.
	 *
	 * The dynamic portion of the hook name, `$post->post_type`, refers to
	 * the post type slug.
	 *
	 * @param int $post_ID  Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update  Whether this is an existing post being updated.
	 * @since 3.7.0
	 */
	dgx_donate_debug_log( '+++ before action save post B' );
	do_action( "save_post_{$post->post_type}", $post_ID, $post, $update );
	dgx_donate_debug_log( '+++ after action save post B' );

	/**
	 * Fires once a post has been saved.
	 *
	 * @param int $post_ID  Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update  Whether this is an existing post being updated.
	 * @since 1.5.0
	 */

	// var_dump( $GLOBALS['wp_filter']['save_post'] );
	dgx_donate_debug_log( '+++ before action save post C' );
	seamless_donations_printr_to_log( $GLOBALS['wp_filter']['save_post'] );
	do_action( 'save_post', $post_ID, $post, $update );  // @@@ BUG HERE @BUG
	dgx_donate_debug_log( '+++ after action save post C' );

	/**
	 * Fires once a post has been saved.
	 *
	 * @param int $post_ID  Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update  Whether this is an existing post being updated.
	 * @since 2.0.0
	 */
	dgx_donate_debug_log( '+++ before action insert post D' );
	do_action( 'wp_insert_post', $post_ID, $post, $update );
	dgx_donate_debug_log( '+++ after action insert post D' );

	if ( $fire_after_hooks ) {
		dgx_donate_debug_log( '+++ Before wp_after_insert_post' );
		wp_after_insert_post( $post, $update, $post_before );
		dgx_donate_debug_log( '+++ after wp_after_insert_post' );
	}

	dgx_donate_debug_log( '++++++ Finishing insert, returning post id:' . $post_ID );
	return $post_ID;
}
