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

// Most of these functions are not turned on except when I'm trying to work through code
// and figure out what is breaking or why.

function debug_test_block() {
	// This is triggered by the Run Debug Test Block menu item in Debug Mode
	// It kicks off at the end of Seamless Donations init

	// TODO The plan is to figure out how to create numbered plans and subscriptions
	// TODO Then issue orders that include subscription ids
	// TODO First, we want to see if there's a plan that exists
	return true;
	$foo = seamless_donations_paypal2022_poll_last_months_transactions();

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
	if ( $access_token != false ) {
		// transaction from single payment 33707466XL4276509

		$count = seamless_donations_paypal2022_rest_list_transactions_page_count( $access_token, 30, 0 );
		seamless_donations_paypal2022_poll_last_months_transactions();

		$trans_details = seamless_donations_paypal2022_rest_get_transaction_details( $access_token, '3U180221UF493484U', 4, 0 );
		$order_details = seamless_donations_paypal2022_rest_get_order_details( $access_token, '33707466XL4276509' );

		// product id PROD-4W247824L33975440
		// sub id I-C3C0RL7B8PAH
		// sub id I-DRYDBMY9XN3S - daily
		// transaction id 33C169423K299433F from subscriptions
		//
		// subscription id I-K2LD44S3361K
		//  recurring payment id I-K2LD44S3361K
		//  individual transaction id found in web interface 83A4984074509535B
		//  order id returned on call 54757635W6160934N

		//		$return = seamless_donations_paypal2022_rest_get_transaction_details( $access_token, '33C169423K299433F' );
		// $sub_list["transactions"][0]["id"] 3U180221UF493484U
		$transaction_list = seamless_donations_paypal2022_rest_list_transactions( $access_token, 1, 0 );
		$json_list        = json_encode( $transaction_list );
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
					$foo = 'a';
				}
			}
		}

		seamless_donations_get_donations_by_meta( '_dgx_donate_transaction_id', $meta_value, - 1 );
		seamless_donations_get_donations_by_meta( '_dgx_donate_payment_processor', 'PAYPAL2022', - 1 );

		$sub_list  = seamless_donations_paypal2022_rest_get_subscription_transactions( $access_token, 'I-YFRUJNGE41MM', 30, 0 );
		$plan_list = seamless_donations_paypal2022_rest_get_plan_list( $access_token ); // what it says, just a list of plans

		$sub_details = seamless_donations_paypal2022_rest_get_subscription_details( $access_token, 'I-YFRUJNGE41MM' );
		// transaction from subscription 3U180221UF493484U
		$trans_details = seamless_donations_paypal2022_rest_get_transaction_details( $access_token, '3U180221UF493484U', 4, 1 );
		$order_details = seamless_donations_paypal2022_rest_get_order_details( $access_token, '3U180221UF493484U' );

		$foo = 2 + 4;
	}
}

function seamless_donations_debug_init() {
	// any startup code goes here
	// seamless_donations_sd5107_update_audit_table();
	// seamless_donations_update_audit_email('test@test.com', 'boo');

	//seamless_donations_get_donation_id_from_session_id( 'SDS01-DF900BDA-5E82-BF14-9162-331160271D13' );

	// seamless_donations_search_paypal_transactions(5);
	// seamless_donations_paypal_get_subscription('I-1UGU6CDAFCUT');

	// seamless_donations_get_time_between_dates('2021-05-26 00:00:00','2021-05-24 00:00:00');
	// seamless_donations_update_audit_email('david@zatz.com', '12345');
	// seamless_donations_update_audit_email('david@zatz.com', '67890');
	// seamless_donations_update_audit_email('david@zatz.com', '12345');
	// seamless_donations_update_audit_email('david@zatz.com', '99202');
}

function seamless_donations_send_to_wp_debug_log( $log ) {
	// Sends to the main WordPress debug log file, if enabled
	if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
	} else {
		error_log( $log );
	}
}

function dgx_donate_debug_log( $message ) {
	$max_log_line_count = 500;
	$debug_log          = get_option( 'dgx_donate_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	$timestamp   = current_time( 'mysql' );
	$debug_log[] = $timestamp . ' ' . $message;

	if ( count( $debug_log ) > $max_log_line_count ) {
		$debug_log = array_slice( $debug_log, - $max_log_line_count, 0 );
	}

	update_option( 'dgx_donate_log', $debug_log );
}

function dgx_donate_cron_log( $message ) {
	$max_log_line_count = 200;
	$debug_log          = get_option( 'dgx_donate_cron_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	$timestamp   = current_time( 'mysql' );
	$debug_log[] = $timestamp . ' ' . $message;

	if ( count( $debug_log ) > $max_log_line_count ) {
		$debug_log = array_slice( $debug_log, - $max_log_line_count, 0 );
	}

	update_option( 'dgx_donate_cron_log', $debug_log );
}

function dgx_donate_audit_log( $message ) {
	$max_log_line_count = 2000;
	$debug_log          = get_option( 'dgx_donate_audit_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	$timestamp   = current_time( 'mysql' );
	$debug_log[] = $timestamp . ' ' . $message;

	if ( count( $debug_log ) > $max_log_line_count ) {
		$debug_log = array_slice( $debug_log, - $max_log_line_count, 0 );
	}

	update_option( 'dgx_donate_audit_log', $debug_log );
}

function seamless_donations_debug_alert( $a ) {
	echo '<script>';
	echo 'alert("' . esc_html( $a ) . '");';
	echo '</script>';
}

function seamless_donations_debug_log( $a ) {
	echo '<script>';
	echo 'console.log("' . esc_html( $a ) . '");';
	echo '</script>';
}

// based on http://php.net/manual/en/function.var-dump.php notes by edwardzyang
function seamless_donations_var_dump_to_string( $mixed = NULL ) {
	ob_start();
	var_dump( $mixed );
	$content = ob_get_contents();
	ob_end_clean();
	$content = html_entity_decode( $content );

	return $content;
}

// differs from above because (a) to log, and (b) no html_entity_decode
function seamless_donations_var_dump_to_log( $mixed = NULL ) {
	$debug_log = get_option( 'dgx_donate_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	ob_start();
	var_dump( $mixed );
	$message = ob_get_contents();
	ob_end_clean();

	$debug_log[] = $message;

	update_option( 'dgx_donate_log', $debug_log );
}

function seamless_donations_printr_to_log( $mixed = NULL ) {
	$debug_log = get_option( 'dgx_donate_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	$message = print_r( $mixed, true );

	$debug_log[] = $message;

	update_option( 'dgx_donate_log', $debug_log );
}

function seamless_donations_post_array_to_log() {
	$debug_log = get_option( 'dgx_donate_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	$timestamp = current_time( 'mysql' );

	// this is a debug routine and each key is sanitized. It only runs if I need to see
	// what was passed in the POST array. Not a risk to users.
	// Some $_POST and $_SERVER references are NOT unescaped or unsanitized variables.
	// They're text constants dumped into a debug information string
	foreach ( $_POST as $key => $value ) {
		$debug_log[] = $timestamp . ' $_POST[' . sanitize_key( $key ) . ']: ' . sanitize_text_field( $value );
	}

	update_option( 'dgx_donate_log', $debug_log );
}

function seamless_donations_server_global_to_log( $arg, $show_always = false ) {
	if ( isset( $_SERVER[ $arg ] ) ) {
		dgx_donate_debug_log( '$_SERVER[' . sanitize_key( $arg ) . ']: ' . sanitize_text_field( $_SERVER[ $arg ] ) );
	} else {
		if ( $show_always ) {
			dgx_donate_debug_log( '$_SERVER[' . sanitize_key( $arg ) . ']: not set' );
		}
	}
}

function seamless_donations_backtrace_to_log() {
	$debug_log = get_option( 'dgx_donate_log' );

	if ( empty( $debug_log ) ) {
		$debug_log = array();
	}

	ob_start();
	debug_print_backtrace();
	$message = ob_end_clean();

	$debug_log[] = $message;

	update_option( 'dgx_donate_log', $debug_log );
}

function seamless_donations_force_a_backtrace_to_log() {
	seamless_donations_backtrace_to_log();
}

function seamless_donations_function_exists_to_log( $fname ) {
	if ( function_exists( $fname ) ) {
		dgx_donate_debug_log( 'Function exists: ' . $fname );
	} else {
		dgx_donate_debug_log( 'Function does not exist: ' . $fname );
	}
}

function seamless_donations_dump_hook_to_log( $hook ) {
	if ( isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
		dgx_donate_debug_log( '>> DUMPING HOOK ' . $hook );
		$a = seamless_donations_pretty( $GLOBALS['wp_filter'][ $hook ] );
		dgx_donate_debug_log( $a );
	}
}

// https://medium.com/@mglaving/how-i-pretty-print-objects-in-php-1ac76e1fbde
function seamless_donations_pretty( $var ) {
	return gettype( $var ) . ' ' . json_encode(
			$var,
			JSON_UNESCAPED_SLASHES |
			JSON_UNESCAPED_UNICODE |
			JSON_PRETTY_PRINT |
			JSON_PARTIAL_OUTPUT_ON_ERROR |
			JSON_INVALID_UTF8_SUBSTITUTE
		);
}

function seamless_donations_trace_insert_callbacks() {
	/*
	apply_filters('wp_insert_post_empty_content', $maybe_empty, $postarr))
	apply_filters('wp_insert_post_parent', $post_parent, $post_ID, $new_postarr, $postarr);
	apply_filters('add_trashed_suffix_to_trashed_posts', true, $post_name, $post_ID);
	apply_filters('wp_insert_attachment_data', $data, $postarr, $unsanitized_postarr);
	apply_filters('wp_insert_post_data', $data, $postarr, $unsanitized_postarr);
	do_action('pre_post_update', $post_ID, $data);
	do_action('edit_attachment', $post_ID);
	do_action('attachment_updated', $post_ID, $post_after, $post_before);
	do_action('add_attachment', $post_ID);
	//do_action("edit_post_{$post->post_type}", $post_ID, $post);
	do_action('post_updated', $post_ID, $post_after, $post_before);
	//do_action("save_post_{$post->post_type}", $post_ID, $post, $update);
	do_action('save_post', $post_ID, $post, $update);  ///// @@@ BUG HERE @BUG
	do_action('wp_insert_post', $post_ID, $post, $update);
	 */

	// add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 )
	dgx_donate_debug_log( 'Adding hook trace actions...' );
	add_action( 'pre_post_update', 'seamless_donations_trace_action_pre_post_update' );
	add_action( 'edit_attachment', 'seamless_donations_trace_action_edit_attachment' );
	add_action( 'attachment_updated', 'seamless_donations_trace_action_attachment_updated' );
	add_action( 'add_attachment', 'seamless_donations_trace_action_add_attachment' );
	add_action( 'post_updated', 'seamless_donations_trace_action_post_updated' );
	add_action( 'save_post', 'seamless_donations_trace_action_save_post' );
	add_action( 'wp_insert_post', 'seamless_donations_trace_action_wp_insert_post' );
}

function seamless_donations_trace_action_pre_post_update( $post_ID, $data ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: pre_post_update' );
}

function seamless_donations_trace_action_edit_attachment( $post_ID ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: edit_attachment' );
}

function seamless_donations_trace_action_attachment_updated( $post_ID, $post_after, $post_before ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: attachment_updated' );
}

function seamless_donations_trace_action_add_attachment( $post_ID ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: add_attachment' );
}

function seamless_donations_trace_action_post_updated( $post_ID, $post_after, $post_before ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: post_updated' );
}

function seamless_donations_trace_action_save_post( $post_id, $post, $update ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: save_post' );
}

function seamless_donations_trace_action_wp_insert_post( $post_id, $post, $update ) {
	dgx_donate_debug_log( '>>> ACTION CALLED: wp_insert_post' );
}
