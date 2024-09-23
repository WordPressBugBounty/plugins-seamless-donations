<?php
/**
 *
 * Seamless Donations by David Gewirtz, adopted from Allen Snook
 *
 * Lab Notes: http://zatzlabs.com/lab-notes/
 * Plugin Page: http://zatzlabs.com/seamless-donations/
 * Contact: http://zatzlabs.com/contact-us/
 *
 * Copyright (c) 2015-2022 by David Gewirtz
 *
 * @package WordPress
 */

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cmb2_admin_init', 'seamless_donations_admin_logs_menu' );

// LOGS - MENU ////
function seamless_donations_admin_logs_menu() {
	$args = array(
		'id'           => 'seamless_donations_tab_logs_page',
		'title'        => 'Seamless Donations - Logs',
		// page title
		'menu_title'   => 'Logs',
		// title on left sidebar
		'tab_title'    => 'Logs',
		// title displayed on the tab
		'object_types' => array( 'options-page' ),
		'option_key'   => 'seamless_donations_tab_logs',
		'parent_slug'  => 'seamless_donations_tab_main',
		'tab_group'    => 'seamless_donations_tab_set',
		'save_button'  => 'Delete Log',
	);

	// 'tab_group' property is supported in > 2.4.0.
	if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
		$args['display_cb'] = 'seamless_donations_cmb2_options_display_with_tabs';
	}

	do_action( 'seamless_donations_tab_logs_before', $args );

	// call on button hit for page save
	add_action( 'admin_post_seamless_donations_tab_logs', 'seamless_donations_tab_logs_process_buttons' );

	// clear previous error messages if coming from another page
	seamless_donations_clear_cmb2_submit_button_messages( $args['option_key'] );

	$args        = apply_filters( 'seamless_donations_tab_logs_menu', $args );
	$log_options = new_cmb2_box( $args );

	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'seamless_donations_tab_logs' ) {
		$log_options = seamless_donations_admin_give_banner($log_options);
		seamless_donations_admin_logs_section_ssl( $log_options );
		seamless_donations_admin_logs_section_data( $log_options );
		seamless_donations_admin_logs_cron_section_data( $log_options );
		seamless_donations_admin_logs_paypal22audit_section_data( $log_options );
		seamless_donations_admin_logs_options_explorer_section_data( $log_options );
		seamless_donations_admin_logs_stripe_event_section_data( $log_options );
		seamless_donations_admin_logs_transaction_history_session_data( $log_options );
	}
	do_action( 'seamless_donations_tab_logs_after', $log_options );
}

// LOGS - SECTION - DATA ////
function seamless_donations_admin_logs_section_ssl( $section_options ) {
	$debug_mode = get_option( 'dgx_donate_debug_mode' );

	if ( $debug_mode == 'OFF' ) {
		$gateway = get_option( 'dgx_donate_payment_processor_choice' );

		// the following code is indicative of a minor architectural flaw in Seamless Donations
		// in that all admin pages are always instantiated. The approach doesn't seem to cause
		// too much of a load, except for the following, which calls the IPN processor.
		// This poorly optimized approach is being left in because callbacks might have been
		// used by user code that expected this behavior and changing it could cause breakage
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_REQUEST['page'] ) && sanitize_key( $_REQUEST['page'] ) == 'seamless_donations_tab_logs' ) {
			$security     = seamless_donations_get_security_status();
			$section_desc = seamless_donations_display_tls_status( $security );
			$section_desc .= '<BR>Get comprehensive SSL report for ';
			$section_desc .= '<A target="_blank" HREF="https://www.ssllabs.com/ssltest/analyze.html?d=';
			$section_desc .= $security['ipn_domain_url'] . '">' . $security['ipn_domain_url'] . '</A>.';
			if ( $gateway == 'PAYPAL' ) {
				$section_desc .= ' Review up-to-the-minute system operation status for PayPal ';
				$section_desc .= '<A target="_blank" HREF="https://www.paypal-status.com/product/sandbox">Sandbox</A> and ';
				$section_desc .= '<A target="_blank" HREF="https://www.paypal-status.com/product/production">Live</A> ';
				$section_desc .= 'servers.';
			}
			if ( $gateway == 'STRIPE' ) {
				$section_desc .= ' Review up-to-the-minute system operation status for ';
				$section_desc .= '<A target="_blank" HREF="https://status.stripe.com/">Stripe servers</A>.';
			}
		}

		$section_options->add_field(
			array(
				'name'        => __( 'Payment Processor Compatibility', 'cmb2' ),
				'id'          => 'seamless_donations_log_ssl',
				'type'        => 'title',
				'after_field' => $section_desc,

			)
		);

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_settings_reset_tests',
				'button_text'        => 'Reset Tests',
				'button_success_msg' => __( 'Tests Reset.', 'seamless-donations' ),
				'button_error_msg'   => '',
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_logs_section_ssl', $section_options );
	}
}

// LOGS - SECTION - DATA ////
function seamless_donations_admin_logs_section_data( $section_options ) {
	$debug_mode = get_option( 'dgx_donate_debug_mode' );

	$section_options->add_field(
		array(
			'name'    => __( 'Log Data', 'cmb2' ),
			'id'      => 'seamless_donations_log_data',
			'type'    => 'title',
			'default' => 'log data',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_logs_section_data', $section_options );

	if ( $debug_mode != 'HIDELOG' ) {
		$debug_log_content = get_option( 'dgx_donate_log' );
		$log_data          = '';
		if ( empty( $debug_log_content ) ) {
			$log_data = esc_html__( 'The log is empty.', 'seamless-donations' );
		} else {
			foreach ( $debug_log_content as $debug_log_entry ) {
				if ( $log_data != '' ) {
					$log_data .= "\n";
				}
				if ( $debug_mode == 'RAWLOG' ) {
					$log_data .= $debug_log_entry;
				} else {
					$log_data .= esc_html( $debug_log_entry );
				}
			}
		}
	} else {
		$log_data = 'Log data hidden so you can clear a stuck log.';
	}

	if ( $debug_mode == 'VERBOSE' ) {
		// we're in debug, so we'll return lots of log info

		$display_options = array(
			__( 'Seamless Donations Log Data', 'seamless-donations' ) => $log_data,
			// Removes the default data by passing an empty value below.
			'Admin Page Framework'                                    => '',
			'Browser'                                                 => '',
		);
	} else {
		$display_options = array(
			__( 'Seamless Donations Log Data', 'seamless-donations' ) => $log_data,
			// Removes the default data by passing an empty value below.
			'Admin Page Framework'                                    => '',
			'WordPress'                                               => '',
			'PHP'                                                     => '',
			'Server'                                                  => '',
			'PHP Error Log'                                           => '',
			'MySQL'                                                   => '',
			'MySQL Error Log'                                         => '',
			'Browser'                                                 => '',
		);
	}

	$section_options->add_field(
		array(
			'name'    => __( 'System Information', 'cmb2' ),
			'id'      => 'seamless_donations_system_information',
			'type'    => 'textarea_code',
			'default' => $log_data,
		)
	);

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_settings_logs_delete',
			'button_text'        => 'Delete Log',
			'button_success_msg' => __( 'Log deleted.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);

	$section_options = apply_filters( 'seamless_donations_tab_logs_section_data_options', $section_options );
}

// CRON LOGS - SECTION - DATA ////
function seamless_donations_admin_logs_cron_section_data( $section_options ) {
	$gateway = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $gateway == 'STRIPE' or $gateway == 'PAYPAL2022' ) {
		$section_options->add_field(
			array(
				'name'    => __( 'Cron Log Data', 'cmb2' ),
				'id'      => 'seamless_donations_cron_log_data',
				'type'    => 'title',
				'default' => 'log data',
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_cron_logs_section_data', $section_options );

		$debug_log_option  = get_option( 'dgx_donate_debug_mode' );
		$debug_log_content = get_option( 'dgx_donate_cron_log' );
		$log_data          = '';

		if ( empty( $debug_log_content ) ) {
			$log_data = esc_html__( 'The log is empty.', 'seamless-donations' );
		} else {
			foreach ( $debug_log_content as $debug_log_entry ) {
				if ( $log_data != '' ) {
					$log_data .= "\n";
				}
				if ( $debug_log_option == 'RAWLOG' ) {
					$log_data .= $debug_log_entry;
				} else {
					$log_data .= esc_html( $debug_log_entry );
				}
			}
		}

		$cron_desc = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
		             'If you want more control over your cron, consider installing ' .
		             '<A target="_blank" HREF="https://wordpress.org/plugins/wp-crontrol/">' .
		             'WP Crontrol</A> from the WordPress.org plugin repository.';

		$section_options->add_field(
			array(
				'name'    => __( 'Cron Log', 'cmb2' ),
				'id'      => 'seamless_donations_cron_log_data_field',
				'type'    => 'textarea_code',
				'default' => $log_data,
				'desc'    => $cron_desc,
			)
		);

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_settings_cron_logs_delete',
				'button_text'        => 'Delete Cron Log',
				'button_success_msg' => __( 'Cron log deleted.', 'seamless-donations' ),
				'button_error_msg'   => '',
			)
		);

		$section_options = apply_filters( 'seamless_donations_tab_cron_logs_section_data_options', $section_options );
	}
}

// CRON LOGS - SECTION - DATA ////
function seamless_donations_admin_logs_paypal22audit_section_data( $section_options ) {
	$debug_log_option = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_log_option == 'PAYPAL22AUDIT' ) {
		$gateway = get_option( 'dgx_donate_payment_processor_choice' );
		if ( $gateway == 'PAYPAL2022' ) {
			$section_options->add_field(
				array(
					'name'    => __( 'PayPal Checkout Audit Data', 'cmb2' ),
					'id'      => 'seamless_donations_paypal22_audit_data',
					'type'    => 'title',
					'default' => 'audit data',
				)
			);
			$section_options = apply_filters( 'seamless_donations_admin_logs_paypal22audit_section_data', $section_options );

			$log_data = '';

			delete_option( 'dgx_donate_audit_log' );

			dgx_donate_audit_log( "Audit log 1" );
			dgx_donate_audit_log( "Audit log 2" );
			dgx_donate_audit_log( "Audit log 3" );

			seamless_donations_paypal2022_audit_last_months_transactions();

			$debug_log_content = get_option( 'dgx_donate_audit_log' );

			foreach ( $debug_log_content as $debug_log_entry ) {
				if ( $log_data != '' ) {
					$log_data .= "\n";
				}
				if ( $debug_log_option == 'RAWLOG' ) {
					$log_data .= $debug_log_entry;
				} else {
					$log_data .= esc_html( $debug_log_entry );
				}
			}

			$audit_desc = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
			              'This is a debug field for testing PayPal Checkout audit data. ';

			$section_options->add_field(
				array(
					'name'    => __( 'Audit Log', 'cmb2' ),
					'id'      => 'seamless_donations_paypal22_audit_data_field',
					'type'    => 'textarea_code',
					'default' => $log_data,
					'desc'    => $audit_desc,
				)
			);

			seamless_donations_display_cmb2_submit_button(
				$section_options,
				array(
					'button_id'          => 'dgx_donate_button_settings_audit_logs_delete',
					'button_text'        => 'Get Audit Data (not enabled)',
					'button_success_msg' => __( 'Audit data retrieved.', 'seamless-donations' ),
					'button_error_msg'   => __( 'Audit data retrieval failed.', 'seamless-donations' ),
				)
			);

			$section_options = apply_filters( 'seamless_donations_tab_paypal22_audit_logs_section_data_options', $section_options );
		}
	}
	if ( $debug_log_option == 'AUDITRECONCILE' or $debug_log_option == 'AUDITRECONCILEVERBOSE' ) {
		$gateway = get_option( 'dgx_donate_payment_processor_choice' );

		$section_options->add_field(
			array(
				'name'    => __( 'Audit Table Reconcilliation', 'cmb2' ),
				'id'      => 'seamless_donations_paypal22_audit_data',
				'type'    => 'title',
				'default' => 'audit data',
			)
		);
		$section_options = apply_filters( 'seamless_donations_admin_logs_paypal22audit_section_data', $section_options );

		$log_data = '';

		$debug_log_content = get_option( 'dgx_donate_audit_log' );

		foreach ( $debug_log_content as $debug_log_entry ) {
			if ( $log_data != '' ) {
				$log_data .= "\n";
			}
			if ( $debug_log_option == 'RAWLOG' ) {
				$log_data .= $debug_log_entry;
			} else {
				$log_data .= esc_html( $debug_log_entry );
			}
		}

		$audit_desc = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
		              'This is a debug field for reconcilling audit table data. ';

		$section_options->add_field(
			array(
				'name'    => __( 'Audit Reconcilliation', 'cmb2' ),
				'id'      => 'seamless_donations_paypal22_audit_data_field',
				'type'    => 'textarea_code',
				'default' => $log_data,
				'desc'    => $audit_desc,
			)
		);

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_settings_audit_reconcilliation',
				'button_text'        => 'Reconcile Audit Table',
				'button_success_msg' => __( 'Audit data retrieved.', 'seamless-donations' ),
				'button_error_msg'   => __( 'Audit data retrieval failed.', 'seamless-donations' ),
			)
		);

		$section_options = apply_filters( 'seamless_donations_tab_paypal22_audit_logs_section_data_options', $section_options );
	}
}

function seamless_donations_admin_logs_options_explorer_section_data( $section_options ) {
	$handler_function = 'seamless_donations_tab_logs_preload'; // setup the preload handler function
	$debug_mode       = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_mode == 'OPTIONSEXPLORER' ) {
		$section_options->add_field(
			array(
				'name'    => __( 'Seamless Donations Options Explorer', 'cmb2' ),
				'id'      => 'seamless_donations_options_explorer_log_data',
				'type'    => 'title',
				'default' => 'log data',
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_options_explorer_section_data', $section_options );

		$options     = wp_load_alloptions();
		$dgx_options = array();
		foreach ( $options as $slug => $value ) {
			if ( substr( $slug, 0, 3 ) == 'dgx' ) {
				$dgx_options[ $slug ] = $value;
			}
		}

		$log_data = '';
		if ( empty( $dgx_options ) ) {
			$log_data = esc_html__( 'The options list is empty.', 'seamless-donations' );
		} else {
			foreach ( $dgx_options as $slug => $value ) {
				if ( $log_data != '' ) {
					$log_data .= "\n";
				}
				if ( is_serialized( $value ) ) {
					if ( $slug == 'dgxdonate_licenses' ) {
						$license_array = unserialize( $value );
						if ( is_serialized( $license_array ) ) {
							$license_array = unserialize( $license_array );
						}
						$num   = count( $license_array );
						$value = '';
						for ( $i = 0; $i < $num; ++ $i ) {
							$product = seamless_donations_name_of( $license_array, $i );
							$license = $license_array[ $product ];
							if ( $value != '' ) {
								$value .= ', ';
							}
							$value .= $product . ': ' . $license;
						}
					} else {
						$value = ' [serialized]';
					}
				}
				switch ( $slug ) {
					case 'dgx_donate_test_stripe_secret_key':
					case 'dgx_donate_test_stripe_api_key':
					case 'dgx_donate_live_stripe_secret_key':
					case 'dgx_donate_live_stripe_api_key':
						$value = substr( $value, 0, 10 ) . '*****************';
						break;
					default:
				}
				$log_data .= $slug . ': ' . esc_html( $value );
			}
		}

		$section_options->add_field(
			array(
				'name'    => __( 'Options Values', 'cmb2' ),
				'id'      => 'seamless_donations_options_explorer_values',
				'type'    => 'textarea_code',
				'default' => $log_data,
			)
		);
	}
}

function seamless_donations_admin_logs_transaction_history_session_data( $section_options ) {
	global $wpdb;
	$handler_function = 'seamless_donations_tab_logs_preload'; // setup the preload handler function
	$debug_mode       = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_mode == 'AUDITSUMMARY' ) {
		$section_options->add_field(
			array(
				'name'    => __( 'Seamless Donations TRANSACTION HISTORY (AUDIT TABLE)', 'cmb2' ),
				'id'      => 'seamless_donations_options_transaction_history_data',
				'type'    => 'title',
				'default' => 'log data',
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_transaction_history_section_data', $section_options );

		$table_name  = $wpdb->prefix . 'seamless_donations_audit';
		$option_name = trim( 'option_name' );

		if ( empty( $option_name ) ) {
			return false;
		}

		$log_data = '';

		// https://developer.wordpress.org/reference/classes/wpdb/#select-a-row
		$query         = 'SELECT * FROM `' . $table_name . '` ORDER BY option_id DESC';
		$results_array = $wpdb->get_results( $query, 'ARRAY_A' );

		$rows = $wpdb->num_rows;
		if ( $rows == 0 ) {
			$log_data = esc_html__( 'The transactions table is empty.', 'seamless-donations' );
		} else {
			for ( $i = 0; $i < $rows; ++ $i ) {
				$option_id    = $results_array[ $i ]['option_id'];
				$option_name  = $results_array[ $i ]['option_name'];
				$option_value = $results_array[ $i ]['option_value'];
				$created_on   = $results_array[ $i ]['created_on'];
				$changed_on   = $results_array[ $i ]['changed_on'];
				$value_array  = unserialize( $option_value );
				$first_name   = $value_array['FIRSTNAME'];
				$last_name    = $value_array['LASTNAME'];
				$amount       = $value_array['AMOUNT'];

				if ( $log_data != '' ) {
					$log_data .= "\n";
				}

				$log_data .= "$option_name: $created_on, $changed_on (" . esc_html( $first_name ) . ' ' . esc_html( $last_name ) . ' - ' . $amount . ')';

				$section_options->add_field(
					array(
						'name'    => __( 'Options Values', 'cmb2' ),
						'id'      => 'seamless_donations_options_explorer_values',
						'type'    => 'textarea_code',
						'default' => $log_data,
					)
				);
			}
		}
	}
}

// STRIPE EVENT LOGS - SECTION - DATA ////
function seamless_donations_admin_logs_stripe_event_section_data( $section_options ) {
	$handler_function = 'seamless_donations_tab_logs_preload'; // setup the preload handler function
	$debug_mode       = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_mode == 'STRIPEEVENT' ) {
		$gateway = get_option( 'dgx_donate_payment_processor_choice' );
		if ( $gateway == 'STRIPE' ) {
			$section_options->add_field(
				array(
					'name'    => __( 'Scan Stripe Event History', 'cmb2' ),
					'id'      => 'seamless_donations_stripeevent_log_data',
					'type'    => 'title',
					'default' => 'log data',
				)
			);
			$section_options = apply_filters( 'seamless_donations_tab_stripe_event_logs_section_data', $section_options );

			$debug_log_option  = get_option( 'dgx_donate_debug_mode' );
			$debug_log_content = get_option( 'dgx_donate_cron_log' );
			$log_data          = '';

			if ( empty( $debug_log_content ) ) {
				$log_data = esc_html__( 'The log is empty.', 'seamless-donations' );
			} else {
				foreach ( $debug_log_content as $debug_log_entry ) {
					if ( $log_data != '' ) {
						$log_data .= "\n";
					}
					if ( $debug_log_option == 'RAWLOG' ) {
						$log_data .= $debug_log_entry;
					} else {
						$log_data .= esc_html( $debug_log_entry );
					}
				}
			}

			$time_options = array(
				'1'  => '1 Day',
				'7'  => '7 Days',
				'14' => '14 Days',
				'30' => '30 Days',
				'60' => '60 Days',
				'90' => '90 Days',
			);

			$section_options->add_field(
				array(
					'name'    => __( 'Event Scan Period', 'seamless-donations' ),
					'id'      => 'dgx_donate_stripe_event_scan_period',
					'type'    => 'select',
					'default' => '1',
					'options' => $time_options,
				)
			);
			seamless_donations_preload_cmb2_field_filter( 'dgx_donate_stripe_event_scan_period', $handler_function );

			$event_options = array(
				'ALL'                        => 'All Events',
				'checkout.session.completed' => 'Session Completed Events Only',
			);

			$section_options->add_field(
				array(
					'name'    => __( 'Events To Scan', 'seamless-donations' ),
					'id'      => 'dgx_donate_stripe_event_type_to_scan',
					'type'    => 'select',
					'default' => 'checkout.session.completed',
					'options' => $event_options,
				)
			);
			seamless_donations_preload_cmb2_field_filter( 'dgx_donate_stripe_event_type_to_scan', $handler_function );

			$json_dump = get_option( 'dgx_donate_stripe_event_json_dump' );
			if ( $json_dump == false ) {
				$json_dump = 'No events found.';
			}

			// https://codemirror.net/doc/manual.html#api
			$section_options->add_field(
				array(
					'name'       => __( 'Stripe Session Data', 'cmb2' ),
					'id'         => 'seamless_donations_stripe_event_log_data_field',
					'type'       => 'textarea_code',
					'attributes' => array(
						'readonly'        => 'readonly',
						'data-codeeditor' => json_encode(
							array(
								'codemirror' => array(
									'mode'     => 'json',
									'readOnly' => 'nocursor',
								),
							)
						),
					),
					'default'    => $json_dump,
				)
			);

			seamless_donations_display_cmb2_submit_button(
				$section_options,
				array(
					'button_id'          => 'dgx_donate_button_settings_stripe_event',
					'button_text'        => 'Scan Stripe Event Log',
					'button_success_msg' => __( 'Stripe event log scanned.', 'seamless-donations' ),
					'button_error_msg'   => '',
				)
			);

			$section_options = apply_filters( 'seamless_donations_tab_stripe_event_logs_section_data_options', $section_options );
		}
	}
}

// LOGS - PROCESS ////
function seamless_donations_tab_logs_process_buttons() {
	// This is a callback that has to be passed the full array for consideration
	// phpcs:ignore WordPress.Security.NonceVerification
	$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_logs', $_POST );

	if ( isset( $_POST['dgx_donate_button_settings_reset_tests'], $_POST['dgx_donate_button_settings_reset_tests_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_reset_tests_nonce'], 'dgx_donate_button_settings_reset_tests' ) ) {
			wp_die( 'Security violation detected [A006]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		delete_option( 'dgx_donate_caching_causing_failure' );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_reset_tests' );
	}
	if ( isset( $_POST['dgx_donate_button_settings_logs_delete'], $_POST['dgx_donate_button_settings_logs_delete_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_logs_delete_nonce'], 'dgx_donate_button_settings_logs_delete' ) ) {
			wp_die( 'Security violation detected [A007]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		delete_option( 'dgx_donate_log' );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_logs_delete' );
	}

	if ( isset( $_POST['dgx_donate_button_settings_cron_logs_delete'], $_POST['dgx_donate_button_settings_cron_logs_delete_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_cron_logs_delete_nonce'], 'dgx_donate_button_settings_cron_logs_delete' ) ) {
			wp_die( 'Security violation detected [A008]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		delete_option( 'dgx_donate_cron_log' );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_cron_logs_delete' );
	}
	// dgx_donate_button_settings_audit_reconcilliation
	if ( isset( $_POST['dgx_donate_button_settings_audit_reconcilliation'], $_POST['dgx_donate_button_settings_audit_reconcilliation_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_audit_reconcilliation_nonce'], 'dgx_donate_button_settings_audit_reconcilliation' ) ) {
			wp_die( 'Security violation detected [A022]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		seamless_donations_debug_display_audit_data_reconcilliation_in_log();
		seamless_donations_flag_cmb2_submit_button_success( 'seamless_donations_debug_reconcile_audit_data' );
	}

	if ( isset( $_POST['dgx_donate_button_settings_stripe_event'], $_POST['dgx_donate_button_settings_stripe_event_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_stripe_event_nonce'], 'dgx_donate_button_settings_stripe_event' ) ) {
			wp_die( 'Security violation detected [A009]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_stripe_event_scan_period'] ) ) {
			update_option( 'dgx_donate_stripe_event_scan_period', sanitize_text_field( $_POST['dgx_donate_stripe_event_scan_period'] ) );
		}
		if ( isset( $_POST['dgx_donate_stripe_event_type_to_scan'] ) ) {
			update_option( 'dgx_donate_stripe_event_type_to_scan', sanitize_text_field( $_POST['dgx_donate_stripe_event_type_to_scan'] ) );
		}
		$days      = intval( get_option( 'dgx_donate_stripe_event_scan_period' ) );
		$json_dump = seamless_donations_stripe_get_event_history_json( $days );
		update_option( 'dgx_donate_stripe_event_json_dump', $json_dump );
	}
}

// SETTINGS OPTIONS - PRELOAD DATA
function seamless_donations_tab_logs_preload( $data, $object_id, $args, $field ) {
	// preload function to ensure compatibility with pre-5.0 settings data

	// find out what field we're setting
	$field_id = $args['field_id'];

	// Pull from existing Seamless Donations data formats
	switch ( $field_id ) {
		// defaults
		case 'dgx_donate_stripe_event_scan_period':
			return ( get_option( 'dgx_donate_stripe_event_scan_period' ) );
		case 'dgx_donate_stripe_event_type_to_scan':
			return ( get_option( 'dgx_donate_stripe_event_type_to_scan' ) );
	}

	return ''; // shouldn't ever be reached, but IDE likes it
}
