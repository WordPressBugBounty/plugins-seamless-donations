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
 * /
 */

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cmb2_admin_init', 'seamless_donations_admin_settings_menu' );

// SETTINGS - MENU ////
function seamless_donations_admin_settings_menu() {
	$args = array(
		'id'           => 'seamless_donations_tab_settings_page',
		'title'        => 'Seamless Donations - Settings',
		// page title
		'menu_title'   => 'Settings',
		// title on left sidebar
		'tab_title'    => 'Settings',
		// title displayed on the tab
		'object_types' => array( 'options-page' ),
		'option_key'   => 'seamless_donations_tab_settings',
		'parent_slug'  => 'seamless_donations_tab_main',
		'tab_group'    => 'seamless_donations_tab_set',
		'save_button'  => 'Save Settings',
	);

	// 'tab_group' property is supported in > 2.4.0.
	if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
		$args['display_cb'] = 'seamless_donations_cmb2_options_display_with_tabs';
	}

	do_action( 'seamless_donations_tab_settings_before', $args );

	// call on button hit for page save
	add_action( 'admin_post_seamless_donations_tab_settings', 'seamless_donations_tab_settings_process_buttons' );

	// clear previous error messages if coming from another page
	seamless_donations_clear_cmb2_submit_button_messages( $args['option_key'] );

	$args             = apply_filters( 'seamless_donations_tab_settings_menu', $args );
	$settings_options = new_cmb2_box( $args );

	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && sanitize_key( $_REQUEST['page'] ) == 'seamless_donations_tab_settings' ) {
		$settings_options = seamless_donations_admin_give_banner($settings_options);
		seamless_donations_admin_settings_basics_section_data( $settings_options );

		// do_action('seamless_donations_tab_settings_before_payments', $settings_options);

		do_action( 'seamless_donations_tab_settings_before_paypal', $settings_options );

		seamless_donations_admin_settings_paypal_section_data( $settings_options );
		seamless_donations_admin_settings_paypal2022_section_data( $settings_options );
		seamless_donations_admin_settings_stripe_section_data( $settings_options );

		// do_action('seamless_donations_tab_settings_before_host', $settings_options);

		seamless_donations_admin_settings_host_section_data( $settings_options );

		do_action( 'seamless_donations_tab_settings_before_tweaks', $settings_options );

		seamless_donations_admin_tweaks_section_data( $settings_options );
		seamless_donations_admin_debug_section_data( $settings_options );

		do_action( 'seamless_donations_tab_settings_after', $settings_options );
	}
}

// SETTINGS - SECTION - NOTIFICATION EMAIL ////
function seamless_donations_admin_settings_basics_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_settings_basics_section_data', $section_options );

	$section_desc = '<i>General settings for payments and processing.</i>';

	$section_options->add_field(
		array(
			'name'        => 'Basic Settings',
			'id'          => 'seamless_donations_admin_settings_section_emails',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_logs_section_data', $section_options );

	$section_options->add_field(
		array(
			'name' => 'Organization Name',
			'id'   => 'dgx_donate_organization_name',
			'type' => 'text',
			'desc' => __(
				'This is the name of the organization that will be shown in various places in Seamless Donations.',
				'seamless-donations'
			),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_organization_name', $handler_function );

	$email_desc = '<i>Enter one or more emails that should be notified when a new donation arrives. ';
	$email_desc .= 'You can separate multiple email addresses with commas.</i>';

	$section_options->add_field(
		array(
			'name' => 'Notification Email Address(es)',
			'id'   => 'dgx_donate_notify_emails',
			'type' => 'text',
			'desc' => $email_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_notify_emails', $handler_function );

	// update_option( 'dgx_donate_allow_legacy_paypal', 'no');
	$allow_legacy_paypal = get_option( 'dgx_donate_allow_legacy_paypal' );
	if ( $allow_legacy_paypal == 'yes' ) {
		$form_display_options = array(
			'PAYPAL2022' => 'PayPal Checkout',
			'STRIPE'     => 'Stripe',
			'PAYPAL'     => 'PayPal Legacy (no longer supported)',
		);
	} else {
		$form_display_options = array(
			'PAYPAL2022' => 'PayPal Checkout',
			'STRIPE'     => 'Stripe',
		);
	}

	//	$gateway_desc = 'Learn <A target="_blank" HREF="https://zatzlabs.com/finally-stripe-support-for-free-in-seamless-donations/">';
	//	$gateway_desc .= 'why we recommend Stripe over PayPal</A>. ';
	$gateway_desc = 'Read a <A target="_blank" HREF="https://memberful.com/blog/stripe-vs-paypal/">helpful comparison article</A> ';
	$gateway_desc .= 'between Stripe and PayPal.';

	$section_options->add_field(
		array(
			'name'    => __( 'Choose your payment processing system', 'seamless-donations' ),
			'id'      => 'dgx_donate_payment_processor_choice',
			'type'    => 'select',
			'default' => 'STRIPE',
			// the index key of the label array below
			'options' => $form_display_options,
			'desc'    => $gateway_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_payment_processor_choice', $handler_function );

	$section_options->add_field(
		array(
			'name'    => __( 'Donor Fee Payment', 'seamless-donations' ),
			'id'      => 'dgx_donate_donor_fee_payment',
			'type'    => 'select',
			'default' => 'NEVER',
			'options' => array( 'NEVER' => 'Donors Never Pay Gateway Fees' ),
			//'desc'    => $feature_desc,
		)
	);

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_settings_basics',
			'button_text'        => 'Save Basic Settings',
			'button_success_msg' => __( 'Basic settings saved.', 'seamless-donations' ),
			'button_error_msg'   => __( 'Please properly fill out the fields.', 'seamless-donations' ),
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_settings_email_section_data_options', $section_options );
}

// SETTINGS - SECTION - PAYPAL ORIGINAL ////
function seamless_donations_admin_settings_paypal_section_data( $section_options ) {
	// init values
	$payment_processor = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_processor == false || $payment_processor == 'PAYPAL' ) {
		$handler_function = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
		$section_options  = apply_filters( 'seamless_donations_tab_settings_paypal_section_data', $section_options );

		// Test email section
		$section_desc = 'Set up your PayPal deposit information. ';
		$section_desc .= 'Confused about setting up PayPal? ';
		$section_desc .= '<A HREF="https://youtu.be/n8z0ejIEowo"><span style="color:blue">';
		$section_desc .= 'Watch this video tutorial.</span></A>';
		$section_desc .= ' Having difficulties with compatibility? ';
		$section_desc .= '<A HREF="http://zatzlabs.com/if-paypal-suddenly-stopped-working-for-you-in-seamless-donations/"><span style="color:blue">';
		$section_desc .= 'Read this troubleshooting guide.</span></A>';

		$section_desc = '<DIV style="background-color:red;color:white;padding:10px">';
		$section_desc .= '<span style="font-size:200%;font-weight:bold">';
		$section_desc .= 'The PayPal Legacy gateway is no longer supported. Please use PayPal Checkout.';
		$section_desc .= '</span><br>';
		$section_desc .= '<span style="font-size:125%;font-weight:bold">';
		$section_desc .= 'PayPal is sunsetting support of the IPN mechanism, and Seamless Donations has been re-engineered to support the ';
		$section_desc .= 'PayPal Checkout gateway instead. Please follow ';
		$section_desc .= '<A HREF="https://zatzlabs.com/major-change-in-paypal-interface/" style="color:white">these instructions</a> ';
		$section_desc .= 'and switch this site to PayPal Checkout as soon as possible. Even after you switch to PayPal Checkout, ';
		$section_desc .= 'existing repeating IPN transactions <i>should</i> continue to process as long as the IPN mechanism still works.';
		$section_desc .= '</span>';
		$section_desc .= '</div>';

		$form_display_options = array(
			'LIVE'    => 'Live (Production Server)',
			'SANDBOX' => 'Sandbox (Test Server)',
		);

		$section_options->add_field(
			array(
				'name'        => 'PayPal Legacy Gateway Settings',
				'id'          => 'seamless_donations_admin_settings_section_paypal',
				'type'        => 'title',
				'after_field' => $section_desc,
			)
		);

		$https_ipn_url = seamless_donations_get_paypal_notification_url();
		$https_ipn_url = str_ireplace( 'http://', 'https://', $https_ipn_url ); // force https check

		$section_options = apply_filters(
			'seamless_donations_admin_settings_section_paypal',
			$section_options
		);

		$status    = seamless_donations_get_security_status();
		$admin_url = get_admin_url();
		$logs_url  = $admin_url . 'admin.php?page=seamless_donations_tab_logs';
		if ( $status['payment_ready_ok'] ) {
			$status_msg     = seamless_donations_display_pass();
			$status_note    = 'Your server is ready';
			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details. ';
		} else {
			$status_msg     = seamless_donations_display_fail();
			$status_note    = 'Some server features incompatible';
			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details to discuss with your hosting provider. ';
		}
		$section_options->add_field(
			array(
				'name'        => __( 'PayPal SSL security compatibility', 'seamless-donations' ),
				'id'          => 'settings_processor_tls_status',
				'type'        => 'text',
				'default'     => $status_note,
				'save_field'  => false,
				'attributes'  => array(
					'readonly' => 'readonly',
					'disabled' => 'disabled',
				),
				'description' => $status_msg . $status_details,
			)
		);

		$section_options->add_field(
			array(
				'name' => __( 'PayPal Live Email', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_email_live',
				'type' => 'text_email',
				'desc' => __(
					'The email address PayPal uses to receive payments in Live mode.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_email_live', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'PayPal Sandbox Email', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_email_sandbox',
				'type' => 'text_email',
				'desc' => __(
					'The email address PayPal uses to receive payments in Sandbox mode',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_email_sandbox', $handler_function );

		$section_options->add_field(
			array(
				'name'    => __( 'PayPal Interface Mode', 'seamless-donations' ),
				'id'      => 'dgx_donate_paypal_server',
				'type'    => 'select',
				'default' => 'LIVE',
				// the index key of the label array below
				'options' => $form_display_options,
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_server', $handler_function );

		$sandbox_paypal_ipn_url     = 'https://www.sandbox.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify';
		$production_paypal_ipn_url  = 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify';
		$production_ipn_history_url = 'https://www.paypal.com/us/cgi-bin/webscr?cmd=_display-ipns-history';
		$sandbox_ipn_history_url    = 'https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_display-ipns-history';
		if ( get_option( 'dgx_donate_paypal_server' ) == 'LIVE' ) {
			$helpful_url = $production_paypal_ipn_url;
			$history_url = $production_ipn_history_url;
		} else {
			$helpful_url = $sandbox_paypal_ipn_url;
			$history_url = $sandbox_ipn_history_url;
		}
		$ipn_url_msg = '<br>Click <a href="' . $helpful_url . '" target="_blank">here</a> to change your IPN in PayPal. ';
		$ipn_url_msg .= 'Click <a href="' . $history_url . '" target="_blank">here</a> to view your IPN transaction history in PayPal.';
		$ipn_url_msg .= '<br>(be sure to log into either your sandbox or production account before clicking the above links)';

		$section_options->add_field(
			array(
				'name'        => __( 'PayPal IPN URL (https)', 'seamless-donations' ),
				'id'          => 'settings_paypal_ipn_https_url',
				'type'        => 'text',
				'default'     => $https_ipn_url,
				'save_field'  => false,
				'attributes'  => array(
					'readonly' => 'readonly',
					'disabled' => 'disabled',
				),
				'description' =>
					'This is the SSL-compliant URL you should use with PayPal once you have a valid SSL certificate installed. ' . $ipn_url_msg,
			)
		);

		$after_text = __( 'Enable obsolete legacy SSL mode', 'seamless-donations' ) . seamless_donations_display_label( '&nbsp;', 'LEGACY' ) . '<br>';
		$after_text .= __( '<span style=\'color:red\'><i>YOU SHOULD NOT USE THIS UNLESS TOLD TO BY THE DEVELOPER. ', 'seamless-donations' );
		$after_text .= __( 'This is an SSL mode that is obsolete. ', 'seamless-donations' );
		$after_text .= __( 'This remains as an option here in Settings solely for the case where an older site needs ', 'seamless-donations' );
		$after_text .= __( 'to turn this feature off.</i></span>', 'seamless-donations' );

		$section_options->add_field(
			array(
				'name'  => __( 'PayPal SSL mode (old)', 'seamless-donations' ),
				'id'    => 'dgx_donate_obsolete_legacy_ssl_mode',
				'type'  => 'checkbox',
				'after' => $after_text,
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_obsolete_legacy_ssl_mode', $handler_function );

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_settings_paypal_settings',
				'button_text'        => 'Save PayPal Settings',
				'button_success_msg' => __( 'PayPal settings updated.', 'seamless-donations' ),
				'button_error_msg'   => __( 'Please enter a valid PayPal email address.', 'seamless-donations' ),
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_settings_paypal_section_data_options', $section_options );
	}
}

// SETTINGS - SECTION - PAYPAL 2022 ////
function seamless_donations_admin_settings_paypal2022_section_data( $section_options ) {
	// init values
	$payment_processor = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_processor == false || $payment_processor == 'PAYPAL2022' ) {
		$handler_function = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
		$section_options  = apply_filters( 'seamless_donations_tab_settings_paypal_section_data', $section_options );

		// Test email section
		$section_desc = 'Set up your PayPal deposit information. ';
		$section_desc .= 'Confused about setting up PayPal? ';
		$section_desc .= '<A HREF="https://zatzlabs.com/major-change-in-paypal-interface/"><span style="color:blue">';
		$section_desc .= 'Read this Lab Note.</span></A>';

		$form_display_options = array(
			'LIVE'    => 'Live (Production Server)',
			'SANDBOX' => 'Sandbox (Test Server)',
		);

		$section_options->add_field(
			array(
				'name'        => 'PayPal Checkout Settings',
				'id'          => 'seamless_donations_admin_settings_section_paypal',
				'type'        => 'title',
				'after_field' => $section_desc,
			)
		);

		//		$https_ipn_url = seamless_donations_get_paypal_notification_url();
		//		$https_ipn_url = str_ireplace( 'http://', 'https://', $https_ipn_url ); // force https check

		$section_options = apply_filters(
			'seamless_donations_admin_settings_section_paypal',
			$section_options
		);

		//		$status    = seamless_donations_get_security_status();
		//		$admin_url = get_admin_url();
		//		$logs_url  = $admin_url . 'admin.php?page=seamless_donations_tab_logs';
		//		if ( $status['payment_ready_ok'] ) {
		//			$status_msg     = seamless_donations_display_pass();
		//			$status_note    = 'Your server is ready';
		//			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details. ';
		//		} else {
		//			$status_msg     = seamless_donations_display_fail();
		//			$status_note    = 'Some server features incompatible';
		//			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details to discuss with your hosting provider. ';
		//		}
		//		$section_options->add_field(
		//			array(
		//				'name'        => __( 'PayPal SSL security compatibility', 'seamless-donations' ),
		//				'id'          => 'settings_processor_tls_status',
		//				'type'        => 'text',
		//				'default'     => $status_note,
		//				'save_field'  => false,
		//				'attributes'  => array(
		//					'readonly' => 'readonly',
		//					'disabled' => 'disabled',
		//				),
		//				'description' => $status_msg . $status_details,
		//			)
		//		);

		$section_options->add_field(
			array(
				'name'    => __( 'PayPal Interface Mode', 'seamless-donations' ),
				'id'      => 'dgx_donate_paypal_server',
				'type'    => 'select',
				'default' => 'LIVE',
				// the index key of the label array below
				'options' => $form_display_options,
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_server', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Live Merchant ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_merchant_live',
				'type' => 'text',
				'desc' => __(
					'Your Merchant ID from the Live mode. Log into PayPal and click ' .
					'<A HREF="https://www.paypal.com/businessmanage/account/aboutBusiness" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_merchant_live', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Live Client ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_client_live',
				'type' => 'textarea_small',
				'desc' => __(
					'Your Client ID from the Live mode. Log into PayPal and click ' .
					'<A HREF="https://developer.paypal.com/dashboard/applications/live" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_client_live', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Live Secret ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_secret_live',
				'type' => 'textarea_small',
				'desc' => __(
					'Your Secret ID from the Live mode. Log into PayPal and click ' .
					'<A HREF="https://developer.paypal.com/dashboard/applications/live" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_secret_live', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Sandbox Merchant ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_merchant_sandbox',
				'type' => 'text',
				'desc' => __(
					'Your Merchant ID from the Sandbox mode. Log into your PayPal Sandbox account and click ' .
					'<A HREF="https://www.sandbox.paypal.com/businessmanage/account/aboutBusiness" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_merchant_sandbox', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Sandbox Client ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_client_sandbox',
				'type' => 'textarea_small',
				'desc' => __(
					'Your Client ID from the Sandbox mode. Log into your PayPal Sandbox account and click ' .
					'<A HREF="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_client_sandbox', $handler_function );

		$section_options->add_field(
			array(
				'name' => __( 'Sandbox Secret ID', 'seamless-donations' ),
				'id'   => 'dgx_donate_paypal_secret_sandbox',
				'type' => 'textarea_small',
				'desc' => __(
					'Your Secret ID from the Sandbox mode. Log into your PayPal Sandbox account and click ' .
					'<A HREF="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank">here</A>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_secret_sandbox', $handler_function );

		//		$section_options->add_field(
		//			array(
		//				'name' => __( 'PayPal Live Email', 'seamless-donations' ),
		//				'id'   => 'dgx_donate_paypal_email_live',
		//				'type' => 'text_email',
		//				'desc' => __(
		//					'The email address PayPal uses to receive payments in Live mode.',
		//					'seamless-donations'
		//				),
		//			)
		//		);
		//		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_email_live', $handler_function );
		//
		//		$section_options->add_field(
		//			array(
		//				'name' => __( 'PayPal Sandbox Email', 'seamless-donations' ),
		//				'id'   => 'dgx_donate_paypal_email_sandbox',
		//				'type' => 'text_email',
		//				'desc' => __(
		//					'The email address PayPal uses to receive payments in Sandbox mode',
		//					'seamless-donations'
		//				),
		//			)
		//		);
		//		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_paypal_email_sandbox', $handler_function );

		//		$sandbox_paypal_ipn_url     = 'https://www.sandbox.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify';
		//		$production_paypal_ipn_url  = 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify';
		//		$production_ipn_history_url = 'https://www.paypal.com/us/cgi-bin/webscr?cmd=_display-ipns-history';
		//		$sandbox_ipn_history_url    = 'https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_display-ipns-history';
		//		if ( get_option( 'dgx_donate_paypal_server' ) == 'LIVE' ) {
		//			$helpful_url = $production_paypal_ipn_url;
		//			$history_url = $production_ipn_history_url;
		//		} else {
		//			$helpful_url = $sandbox_paypal_ipn_url;
		//			$history_url = $sandbox_ipn_history_url;
		//		}
		//		$ipn_url_msg = '<br>Click <a href="' . $helpful_url . '" target="_blank">here</a> to change your IPN in PayPal. ';
		//		$ipn_url_msg .= 'Click <a href="' . $history_url . '" target="_blank">here</a> to view your IPN transaction history in PayPal.';
		//		$ipn_url_msg .= '<br>(be sure to log into either your sandbox or production account before clicking the above links)';
		//
		//		$section_options->add_field(
		//			array(
		//				'name'        => __( 'PayPal IPN URL (https)', 'seamless-donations' ),
		//				'id'          => 'settings_paypal_ipn_https_url',
		//				'type'        => 'text',
		//				'default'     => $https_ipn_url,
		//				'save_field'  => false,
		//				'attributes'  => array(
		//					'readonly' => 'readonly',
		//					'disabled' => 'disabled',
		//				),
		//				'description' =>
		//					'This is the SSL-compliant URL you should use with PayPal once you have a valid SSL certificate installed. ' . $ipn_url_msg,
		//			)
		//		);
		//
		//		$after_text = __( 'Enable obsolete legacy SSL mode', 'seamless-donations' ) . seamless_donations_display_label( '&nbsp;', 'LEGACY' ) . '<br>';
		//		$after_text .= __( '<span style=\'color:red\'><i>YOU SHOULD NOT USE THIS UNLESS TOLD TO BY THE DEVELOPER. ', 'seamless-donations' );
		//		$after_text .= __( 'This is an SSL mode that is obsolete. ', 'seamless-donations' );
		//		$after_text .= __( 'This remains as an option here in Settings solely for the case where an older site needs ', 'seamless-donations' );
		//		$after_text .= __( 'to turn this feature off.</i></span>', 'seamless-donations' );
		//
		//		$section_options->add_field(
		//			array(
		//				'name'  => __( 'PayPal SSL mode (old)', 'seamless-donations' ),
		//				'id'    => 'dgx_donate_obsolete_legacy_ssl_mode',
		//				'type'  => 'checkbox',
		//				'after' => $after_text,
		//			)
		//		);
		//		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_obsolete_legacy_ssl_mode', $handler_function );

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_settings_paypal2022_settings',
				'button_text'        => 'Save PayPal Settings',
				'button_success_msg' => __( 'PayPal settings updated.', 'seamless-donations' ),
				'button_error_msg'   => __( 'Please enter a valid PayPal ID.', 'seamless-donations' ),
			)
		);
		$section_options = apply_filters( 'seamless_donations_tab_settings_paypal2022_section_data_options', $section_options );
	}
}

// SETTINGS - SECTION - STRIPE ////
function seamless_donations_admin_settings_stripe_section_data( $section_options ) {
	// init values
	$payment_processor = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_processor == 'STRIPE' ) {
		$handler_function = 'seamless_donations_admin5_settings_preload';

		$section_desc = 'Enables the ability to process Stripe payments.';
		$section_desc .= ' Get <A HREF="https://dashboard.stripe.com/test/apikeys">Stripe API keys</A>.';
		$section_desc .= ' If your keys are ever compromised, <A HREF="https://stripe.com/docs/keys#revoking-keys">here\'s how to update them</A>.';
		$section_desc .= '<BR><A HREF="https://dashboard.stripe.com/settings/branding">Customize the look and feel</A> of your Stripe checkout page.';
		$section_desc .= seamless_donations_display_label( '&nbsp;', 'BETA' );

		$form_display_options = array(
			'LIVE'    => 'Live (Production Server)',
			'SANDBOX' => 'Sandbox (Test Server)',
		);

		$section_options->add_field(
			array(
				'name'        => 'Stripe Payments',
				'id'          => 'seamless_donations_stripe_admin_settings',
				'type'        => 'title',
				'after_field' => $section_desc,
			)
		);

		$status    = seamless_donations_get_security_status();
		$admin_url = get_admin_url();
		$logs_url  = $admin_url . 'admin.php?page=seamless_donations_tab_logs';
		if ( $status['payment_ready_ok'] ) {
			$status_msg     = seamless_donations_display_pass();
			$status_note    = 'Your server is ready';
			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details. ';
		} else {
			$status_msg     = seamless_donations_display_fail();
			$status_note    = 'Some server features incompatible';
			$status_details = ' See <A HREF="' . $logs_url . '">payment processor compatibility summary</A> for details to discuss with your hosting provider. ';
		}
		$section_options->add_field(
			array(
				'name'        => __( 'Stripe SSL security compatibility', 'seamless-donations' ),
				'id'          => 'settings_stripe_tls_status',
				'type'        => 'text',
				'default'     => $status_note,
				'save_field'  => false,
				'attributes'  => array(
					'readonly' => 'readonly',
					'disabled' => 'disabled',
				),
				'description' => $status_msg . $status_details,
			)
		);

		$section_options->add_field(
			array(
				'name'    => __( 'Stripe Interface Mode', 'seamless-donations' ),
				'id'      => 'dgx_donate_stripe_server',
				'type'    => 'select',
				'default' => 'LIVE',
				// the index key of the label array below
				'options' => $form_display_options,
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_stripe_server', $handler_function );

		$section_options->add_field(
			array(
				'name' => 'Live Stripe Publishable Key',
				'id'   => 'dgx_donate_live_stripe_api_key',
				'type' => 'text',
				'desc' => __(
					'This is the Stripe API key associated with your Stripe account used for live transactions. It must begin with <b>pk_live_</b>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_live_stripe_api_key', $handler_function );

		$section_options->add_field(
			array(
				'name' => 'Live Stripe Secret Key',
				'id'   => 'dgx_donate_live_stripe_secret_key',
				'type' => 'text',
				'desc' => __(
					'This is the Stripe secret key associated with your Stripe account used for live transactions. It must begin with <b>sk_live_</b>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_live_stripe_secret_key', $handler_function );

		$section_options->add_field(
			array(
				'name' => 'Test Stripe Publishable Key',
				'id'   => 'dgx_donate_test_stripe_api_key',
				'type' => 'text',
				'desc' => __(
					'This is the Stripe API key associated with your Stripe account used for test transactions. It must begin with <b>pk_test_</b>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_test_stripe_api_key', $handler_function );

		$section_options->add_field(
			array(
				'name' => 'Test Stripe Secret Key',
				'id'   => 'dgx_donate_test_stripe_secret_key',
				'type' => 'text',
				'desc' => __(
					'This is the Stripe secret key associated with your Stripe account used for test transactions. It must begin with <b>sk_test_</b>.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_test_stripe_secret_key', $handler_function );

		$stripe_billing_address_options = array(
			'auto'     => 'Auto',
			'required' => 'Required',
		);
		$section_options->add_field(
			array(
				'name'    => 'Stripe Billing Address Mode',
				'id'      => 'dgx_donate_stripe_billing_address',
				'type'    => 'select',
				'default' => 'false',
				'options' => $stripe_billing_address_options,
				'desc'    => __(
					'If set to Required, Stripe will require the donor to enter billng information, even if the billing address was already entered on the donation form.',
					'seamless-donations'
				),
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_stripe_billing_address', $handler_function );

		seamless_donations_display_cmb2_submit_button(
			$section_options,
			array(
				'button_id'          => 'dgx_donate_button_stripe_settings',
				'button_text'        => 'Update Stripe API Key',
				'button_success_msg' => __( 'Stripe settings saved.', 'seamless-donations' ),
				'button_error_msg'   => __( 'Please enter Stripe keys.', 'seamless-donations' ),
			)
		);

		return $section_options;
	}
}

// SETTINGS - SECTION - HOST COMPATIBILITY ////
function seamless_donations_admin_settings_host_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_settings_host_section_data', $section_options );

	$section_desc = 'Options that can help increase compatibility with your hosting provider.';
	$section_desc .= ' Details on what these options do can be found in ';
	$section_desc .= "<A HREF='http://zatzlabs.com/all-hosts-are-different/'>this Lab Note</A>.";

	$section_options->add_field(
		array(
			'name'        => 'Host Compatibility Options',
			'id'          => 'seamless_donations_admin_settings_section_hosts',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$form_ignore_nonce_desc = __( 'Ignore form nonce value', 'seamless-donations' ) . seamless_donations_display_label();
	$form_ignore_nonce_desc .= '<BR><I>';
	$form_ignore_nonce_desc .= 'This may help for sites/hosts that that report permission denied after form submitted. ';
	$form_ignore_nonce_desc .= "<br><span style='color:red'>Warning: This could compromise form processing security ";
	$form_ignore_nonce_desc .= 'or reliability. Be sure to perform sandbox tests after enabling this option.</span>';
	$form_ignore_nonce_desc .= '</I>';

	$section_options->add_field(
		array(
			'name'  => __( 'Form Nonces', 'seamless-donations' ),
			'id'    => 'dgx_donate_ignore_form_nonce',
			'type'  => 'checkbox',
			'after' => $form_ignore_nonce_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_ignore_form_nonce', $handler_function );

	$form_transaction_desc = __( 'Generate unique transaction IDs in browser', 'seamless-donations' );
	$form_transaction_desc .= seamless_donations_display_label();
	$form_transaction_desc .= '<BR><I>';
	$form_transaction_desc .= 'This may help for sites/hosts that cache transaction IDs. ';
	$form_transaction_desc .= 'Rather than generating the unique transaction ID in PHP on the server, ';
	$form_transaction_desc .= "this uses the device's native JavaScript.";
	$form_transaction_desc .= "<br><span style='color:red'>Warning: This could be unpredictable, ";
	$form_transaction_desc .= "depending on the age and compatibility of your user's device.</span>";
	$form_transaction_desc .= '</I>';

	$section_options->add_field(
		array(
			'name'  => __( 'Browser-based IDs', 'seamless-donations' ),
			'id'    => 'dgx_donate_browser_uuid',
			'type'  => 'checkbox',
			'after' => $form_transaction_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_browser_uuid', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_settings_host_options',
			'button_text'        => 'Save Host Options',
			'button_success_msg' => __( 'Host compatibility settings updated.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_settings_host_section_data_options', $section_options );
}

// SETTINGS - SECTION - TWEAKS ////
function seamless_donations_admin_tweaks_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_settings_tweaks_section_data', $section_options );

	// Test email section
	$section_desc = 'Options that can tweak your settings. Starting with one, undoubtedly more to come.';

	$section_options->add_field(
		array(
			'name'        => 'Setting Tweaks',
			'id'          => 'seamless_donations_admin_settings_section_tweaks',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$compact_desc = __(
		'Enable compact menu (tucks Donors, Funds, and Donations under Seamless Donations menu)',
		'seamless-donations'
	);

	$section_options->add_field(
		array(
			'name'  => __( 'Compact Menus', 'seamless-donations' ),
			'id'    => 'dgx_donate_compact_menus',
			'type'  => 'checkbox',
			'after' => $compact_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_compact_menus', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_settings_tweaks',
			'button_text'        => 'Save Tweaks',
			'button_success_msg' => __( 'Host compatibility settings updated.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_settings_tweaks_section_data_options', $section_options );
}

// SETTINGS - SECTION - DEBUG ////
function seamless_donations_admin_debug_section_data( $section_options ) {
	// init values
	$handler_function  = 'seamless_donations_admin5_settings_preload'; // setup the preload handler function
	$payment_processor = get_option( 'dgx_donate_payment_processor_choice' );

	$mode_options = array(
		'OFF'                   => 'Debug Mode Off',
		//		'AUDITSUMMARY'    => 'Dump Audit Table Summary',
		//		'DUMPINSERT'      => 'Dump Donation Insert Parameters',
		//		'VERBOSE'         => 'Expanded Log Messages',
				'HIDELOG'         => 'Hide Log Data',
		//		'OPTIONSEXPLORER' => 'Options Explorer',
		//		'PAYPALIPN'       => 'PayPal Enable IPN Verbosity',
		//		'PENDING'         => 'PayPal Show Pending Transactions',
		//		'PAYPALAUDIT'     => 'PayPal Transaction Audit (31 days)',
		'PAYPAL22AUDIT'         => 'PayPal Checkout Transaction Audit (31 days)',
		'AUDITRECONCILE'        => 'Audit Table Reconcilliation Check',
		'AUDITRECONCILEVERBOSE' => 'Audit Table Reconcilliation (Verbose)',
		'PREFILL'               => 'Prefill Donation Form',
		'BLOCK'                 => 'Run Debug Test Block',
		//		'STRIPEEVENT'     => 'Stripe Event History',
		//		'RAWLOG'          => 'Show Raw Log Data',
		//		'INSERTTRACE'     => 'Trace Insert Post',
		//		'INSERTHOOKTRACE' => 'Trace Insert Post Via Hooks',
	);

	$section_options = apply_filters( 'seamless_donations_tab_settings_debug_section_data', $section_options );

	$section_desc = 'Enables certain Seamless Donations debugging features. Reduces security. ';
	$section_desc .= 'Displays annoying (but effective) warning message until turned off.';

	$section_options->add_field(
		array(
			'name'        => 'Debug Options',
			'id'          => 'seamless_donations_admin_settings_section_debug',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$log_detail = "<BR><i><span style='color:red'>Please leave off unless change requested by the developer.</span></i>";

	$section_options->add_field(
		array(
			'name'        => __( 'Debug Mode', 'seamless-donations' ),
			'id'          => 'dgx_donate_debug_mode',
			'type'        => 'select',
			'default'     => 'OFF',
			'options'     => $mode_options,
			'after_field' => $log_detail,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_debug_mode', $handler_function );

	// build the log settings values - this is an array because there will probably be more settings
	$obscurify = get_option( 'dgx_donate_log_obscure_name' );
	if ( $obscurify == false ) {
		// value never been set, default to true
		$obscurify = '1';
	}

	$section_options->add_field(
		array(
			'name'    => __( 'Log Settings', 'seamless-donations' ),
			'id'      => 'dgx_donate_log_obscure_name',
			'type'    => 'checkbox',
			'default' => $obscurify,
			'after'   => __( 'Obscurify donor names in log', 'seamless-donations' ),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_log_obscure_name', $handler_function );

	$legacy_addon_desc = __( 'No longer check for pre-5.0 legacy add-ons', 'seamless-donations' ) .
	                     seamless_donations_display_label() . '<BR><i>';
	$legacy_addon_desc .= "<span style='color:red'>Use this if updated add-ons are not recognized as such. You probably ";
	$legacy_addon_desc .= "shouldn't run this unless requested to by the developer.</span></i>";

	$section_options->add_field(
		array(
			'name'  => __( 'Legacy Add-on Check', 'seamless-donations' ),
			'id'    => 'dgx_donate_legacy_addon_check',
			'type'  => 'checkbox',
			'after' => $legacy_addon_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_legacy_addon_check', $handler_function );

	if ( $payment_processor == 'STRIPE' ) {
		$legacy_addon_desc = __( 'Do not validate Stripe key fields', 'seamless-donations' ) .
		                     seamless_donations_display_label() . '<BR><i>';
		$legacy_addon_desc .= "<span style='color:red'>This will accept the form no matter what is in the Stripe key field. You probably ";
		$legacy_addon_desc .= "shouldn't run this unless requested to by the developer.</span></i>";

		$section_options->add_field(
			array(
				'name'  => __( 'Ignore Stripe Key Validation', 'seamless-donations' ),
				'id'    => 'dgx_donate_no_stripe_key_check',
				'type'  => 'checkbox',
				'after' => $legacy_addon_desc,
			)
		);
		seamless_donations_preload_cmb2_field_filter( 'dgx_donate_no_stripe_key_check', $handler_function );
	}

	$xref_name_desc = __( 'Rebuild Donations, Donors, and Funds cross-reference indexes (name priority)', 'seamless-donations' ) .
	                  seamless_donations_display_label() . '<BR><i>';
	$xref_name_desc .= "<span style='color:red'>This runs once when you click Save Debug Options. You probably ";
	$xref_name_desc .= "shouldn't run this unless requested to by the developer. This feature is still under development.</span></i>";

	$section_options->add_field(
		array(
			'name'  => __( 'Rebuild Indexes', 'seamless-donations' ),
			'id'    => 'dgx_donate_rebuild_xref_by_name',
			'type'  => 'checkbox',
			'after' => $xref_name_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_rebuild_xref_by_name', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_settings_debug_options',
			'button_text'        => 'Save Debug Options',
			'button_success_msg' => __( 'Debug options updated.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_settings_debug_section_data_options', $section_options );
}

// SETTINGS OPTIONS - PRELOAD DATA
function seamless_donations_admin5_settings_preload( $data, $object_id, $args, $field ) {
	// preload function to ensure compatibility with pre-5.0 settings data

	// find out what field we're setting
	$field_id = $args['field_id'];

	// Pull from existing Seamless Donations data formats
	switch ( $field_id ) {
		// defaults
		case 'dgx_donate_notify_emails':
			return ( get_option( 'dgx_donate_notify_emails' ) );
		case 'dgx_donate_payment_processor_choice':
			return ( get_option( 'dgx_donate_payment_processor_choice' ) );
		case 'dgx_donate_paypal_email_live':
			return ( get_option( 'dgx_donate_paypal_email_live' ) );
		case 'dgx_donate_paypal_email_sandbox':
			return ( get_option( 'dgx_donate_paypal_email_sandbox' ) );
		case 'dgx_donate_paypal_server':
			return ( get_option( 'dgx_donate_paypal_server' ) );
		// From previous PayPal attempt, might be able to remove
		case 'dgx_donate_live_paypal_client_id':
			return ( get_option( 'dgx_donate_live_paypal_client_id' ) );
		case 'dgx_donate_live_paypal_secret_key':
			return ( get_option( 'dgx_donate_live_paypal_secret_key' ) );
		case 'dgx_donate_sandbox_paypal_client_id':
			return ( get_option( 'dgx_donate_sandbox_paypal_client_id' ) );
		case 'dgx_donate_sandbox_paypal_secret_key':
			return ( get_option( 'dgx_donate_sandbox_paypal_secret_key' ) );
		// for PayPal 2022
		case 'dgx_donate_paypal_merchant_live':
			return ( get_option( 'dgx_donate_paypal_merchant_live' ) );
		case 'dgx_donate_paypal_client_live':
			return ( get_option( 'dgx_donate_paypal_client_live' ) );
		case 'dgx_donate_paypal_secret_live':
			return ( get_option( 'dgx_donate_paypal_secret_live' ) );
		case 'dgx_donate_paypal_merchant_sandbox':
			return ( get_option( 'dgx_donate_paypal_merchant_sandbox' ) );
		case 'dgx_donate_paypal_client_sandbox':
			return ( get_option( 'dgx_donate_paypal_client_sandbox' ) );
		case 'dgx_donate_paypal_secret_sandbox':
			return ( get_option( 'dgx_donate_paypal_secret_sandbox' ) );
		//
		case 'dgx_donate_obsolete_legacy_ssl_mode':
			if ( get_option( 'dgx_donate_obsolete_legacy_ssl_mode' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_organization_name':
			return ( get_option( 'dgx_donate_organization_name' ) );
		case 'dgx_donate_stripe_server':
			return ( get_option( 'dgx_donate_stripe_server' ) );
		case 'dgx_donate_live_stripe_api_key':
			return ( get_option( 'dgx_donate_live_stripe_api_key' ) );
		case 'dgx_donate_live_stripe_secret_key':
			return ( get_option( 'dgx_donate_live_stripe_secret_key' ) );
		case 'dgx_donate_test_stripe_api_key':
			return ( get_option( 'dgx_donate_test_stripe_api_key' ) );
		case 'dgx_donate_test_stripe_secret_key':
			return ( get_option( 'dgx_donate_test_stripe_secret_key' ) );
		case 'dgx_donate_stripe_billing_address':
			return ( get_option( 'dgx_donate_stripe_billing_address' ) );
		case 'dgx_donate_form_via_action':
			if ( get_option( 'dgx_donate_form_via_action' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_ignore_form_nonce':
			if ( get_option( 'dgx_donate_ignore_form_nonce' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_browser_uuid':
			if ( get_option( 'dgx_donate_browser_uuid' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_compact_menus':
			if ( get_option( 'dgx_donate_compact_menus' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_debug_mode':
			$mode = get_option( 'dgx_donate_debug_mode' );
			if ( $mode == '1' ) {
				// legacy conversion
				update_option( 'dgx_donate_debug_mode', 'VERBOSE' );

				return 'VERBOSE';
			} else {
				return $mode;
			}
		case 'dgx_donate_log_obscure_name':
			if ( get_option( 'dgx_donate_log_obscure_name' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_legacy_addon_check':
			return ( get_option( 'dgx_donate_legacy_addon_check' ) );
		case 'dgx_donate_no_stripe_key_check':
			return ( get_option( 'dgx_donate_no_stripe_key_check' ) );
		case 'dgx_donate_ignore_paypal_ipn':
			return ( get_option( 'dgx_donate_ignore_paypal_ipn' ) );
		case 'dgx_donate_rebuild_xref_by_name':
			if ( get_option( 'dgx_donate_rebuild_xref_by_name' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
	}

	return ''; // shouldn't ever be reached, but IDE likes it
}

// FORM OPTIONS - PROCESS FORM SUBMISSIONS
function seamless_donations_tab_settings_process_buttons() {
	// convert to legacy Seamless Donations 4.0 data format for continuity
	// This is a callback that has to be passed the full array for consideration
	// phpcs:ignore WordPress.Security.NonceVerification
	$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_settings', $_POST );

	// Process Save changes button
	if ( isset( $_POST['dgx_donate_button_settings_basics'], $_POST['dgx_donate_button_settings_basics_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_basics_nonce'], 'dgx_donate_button_settings_basics' ) ) {
			wp_die( 'Security violation detected [A010]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$email_list = '';
		if ( isset( $_POST['dgx_donate_notify_emails'] ) ) {
			$email_list = sanitize_text_field( $_POST['dgx_donate_notify_emails'] );
		}
		$email_array       = explode( ',', $email_list );
		$clean_email_array = array();
		foreach ( $email_array as $email ) {
			$email = trim( $email );
			$email = sanitize_email( $email );
			array_push( $clean_email_array, $email );
			if ( ! is_email( $email ) ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_basics' );

				return;
			}
		}
		$email_list = implode( ',', $clean_email_array );
		update_option( 'dgx_donate_notify_emails', $email_list );
		if ( isset( $_POST['dgx_donate_organization_name'] ) ) {
			$organization_name = sanitize_text_field( trim( $_POST['dgx_donate_organization_name'] ) );
			update_option( 'dgx_donate_organization_name', $organization_name );
		}
		if ( isset( $_POST['dgx_donate_payment_processor_choice'] ) ) {
			$payment_processor = sanitize_text_field( trim( $_POST['dgx_donate_payment_processor_choice'] ) );
			update_option( 'dgx_donate_payment_processor_choice', $payment_processor );
		}
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_basics' );
	}
	if ( isset( $_POST['dgx_donate_button_settings_paypal_settings'], $_POST['dgx_donate_button_settings_paypal_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_paypal_settings_nonce'], 'dgx_donate_button_settings_paypal_settings' ) ) {
			wp_die( 'Security violation detected [A011]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_paypal_server'] ) ) {
			update_option( 'dgx_donate_paypal_server', sanitize_text_field( $_POST['dgx_donate_paypal_server'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_email_live'] ) ) {
			$email = sanitize_email( $_POST['dgx_donate_paypal_email_live'] );
			if ( ! is_email( $email ) ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal_settings' );

				return;
			}
			update_option( 'dgx_donate_paypal_email_live', $email );
		}

		if ( isset( $_POST['dgx_donate_paypal_email_sandbox'] ) ) {
			$email = sanitize_email( $_POST['dgx_donate_paypal_email_sandbox'] );
			if ( ! is_email( $email ) ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal_settings' );

				return;
			}
			update_option( 'dgx_donate_paypal_email_sandbox', $email );
		}

		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_obsolete_legacy_ssl_mode'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_obsolete_legacy_ssl_mode'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_obsolete_legacy_ssl_mode', $checkbox_value );

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_paypal_settings' );
	}

	if ( isset( $_POST['dgx_donate_button_settings_paypal2022_settings'], $_POST['dgx_donate_button_settings_paypal2022_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_paypal2022_settings_nonce'], 'dgx_donate_button_settings_paypal2022_settings' ) ) {
			wp_die( 'Security violation detected [A017]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$server_type = '';
		if ( isset( $_POST['dgx_donate_paypal_server'] ) ) {
			$server_type = $_POST['dgx_donate_paypal_server'];
			update_option( 'dgx_donate_paypal_server', sanitize_text_field( $_POST['dgx_donate_paypal_server'] ) );
		}

		if ( $server_type == 'LIVE' ) {
			if ( ! isset( $_POST['dgx_donate_paypal_merchant_live'] ) or ! isset( $_POST['dgx_donate_paypal_client_live'] ) or ! isset( $_POST['dgx_donate_paypal_secret_live'] ) ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
			if ( $_POST['dgx_donate_paypal_merchant_live'] == '' or $_POST['dgx_donate_paypal_client_live'] == '' or $_POST['dgx_donate_paypal_secret_live'] == '' ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
			$client_id     = sanitize_text_field( $_POST['dgx_donate_paypal_client_live'] );
			$client_secret = sanitize_text_field( $_POST['dgx_donate_paypal_secret_live'] );
			$test          = seamless_donations_paypal2022_rest_get_access_token( $client_id, $client_secret );
			if ( $test === false ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
		}
		if ( $server_type == 'SANDBOX' ) {
			if ( ! isset( $_POST['dgx_donate_paypal_merchant_sandbox'] ) or ! isset( $_POST['dgx_donate_paypal_client_sandbox'] ) or ! isset( $_POST['dgx_donate_paypal_secret_sandbox'] ) ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
			if ( $_POST['dgx_donate_paypal_merchant_sandbox'] == '' or $_POST['dgx_donate_paypal_client_sandbox'] == '' or $_POST['dgx_donate_paypal_secret_sandbox'] == '' ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
			$client_id     = sanitize_text_field( $_POST['dgx_donate_paypal_client_sandbox'] );
			$client_secret = sanitize_text_field( $_POST['dgx_donate_paypal_secret_sandbox'] );
			$test          = seamless_donations_paypal2022_rest_get_access_token( $client_id, $client_secret );
			if ( $test === false ) {
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_settings_paypal2022_settings' );

				return;
			}
		}

		if ( isset( $_POST['dgx_donate_paypal_merchant_live'] ) ) {
			update_option( 'dgx_donate_paypal_merchant_live', sanitize_text_field( $_POST['dgx_donate_paypal_merchant_live'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_client_live'] ) ) {
			update_option( 'dgx_donate_paypal_client_live', sanitize_text_field( $_POST['dgx_donate_paypal_client_live'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_secret_live'] ) ) {
			update_option( 'dgx_donate_paypal_secret_live', sanitize_text_field( $_POST['dgx_donate_paypal_secret_live'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_merchant_sandbox'] ) ) {
			update_option( 'dgx_donate_paypal_merchant_sandbox', sanitize_text_field( $_POST['dgx_donate_paypal_merchant_sandbox'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_client_sandbox'] ) ) {
			update_option( 'dgx_donate_paypal_client_sandbox', sanitize_text_field( $_POST['dgx_donate_paypal_client_sandbox'] ) );
		}

		if ( isset( $_POST['dgx_donate_paypal_secret_sandbox'] ) ) {
			update_option( 'dgx_donate_paypal_secret_sandbox', sanitize_text_field( $_POST['dgx_donate_paypal_secret_sandbox'] ) );
		}

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_paypal2022_settings' );
	}

	if ( isset( $_POST['dgx_donate_button_stripe_settings'], $_POST['dgx_donate_button_stripe_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_stripe_settings_nonce'], 'dgx_donate_button_stripe_settings' ) ) {
			wp_die( 'Security violation detected [A012]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_live_stripe_api_key'] ) ) {
			$stripe_live_api_key = sanitize_text_field( trim( $_POST['dgx_donate_live_stripe_api_key'] ) );
			//$stripe_live_api_key = sanitize_key( trim( $_POST['dgx_donate_live_stripe_api_key'] ) );
		}
		if ( isset( $_POST['dgx_donate_live_stripe_secret_key'] ) ) {
			$stripe_live_secret_key = sanitize_text_field( trim( $_POST['dgx_donate_live_stripe_secret_key'] ) );
			//$stripe_live_secret_key = sanitize_key( trim( $_POST['dgx_donate_live_stripe_secret_key'] ) );
		}
		if ( isset( $_POST['dgx_donate_test_stripe_api_key'] ) ) {
			$stripe_test_api_key = sanitize_text_field( trim( $_POST['dgx_donate_test_stripe_api_key'] ) );
			//$stripe_test_api_key = sanitize_key( trim( $_POST['dgx_donate_test_stripe_api_key'] ) );
		}
		if ( isset( $_POST['dgx_donate_test_stripe_secret_key'] ) ) {
			$stripe_test_secret_key = sanitize_text_field( trim( $_POST['dgx_donate_test_stripe_secret_key'] ) );
			//$stripe_test_secret_key = sanitize_key( trim( $_POST['dgx_donate_test_stripe_secret_key'] ) );
		}

		$no_stripe_key_check = get_option( 'dgx_donate_no_stripe_key_check' );
		if ( $no_stripe_key_check != 'on' ) {
			$key_validate = substr_compare( $stripe_live_api_key, 'pk_live_', 0, 8, true );
			if ( $key_validate != 0 ) {
				dgx_donate_debug_log( "Stripe key error: expecting 'pk_live_', got '" . substr( $stripe_live_api_key, 0, 8 ) . "'" );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			$key_validate = substr_compare( $stripe_live_secret_key, 'sk_live_', 0, 8, true );
			if ( $key_validate != 0 ) {
				dgx_donate_debug_log( "Stripe key error: expecting 'sk_live_', got '" . substr( $stripe_live_secret_key, 0, 8 ) . "'" );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			$key_validate = substr_compare( $stripe_test_api_key, 'pk_test_', 0, 8, true );
			if ( $key_validate != 0 ) {
				dgx_donate_debug_log( "Stripe key error: expecting 'pk_test_', got '" . substr( $stripe_test_api_key, 0, 8 ) . "'" );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			$key_validate = substr_compare( $stripe_test_secret_key, 'sk_test_', 0, 8, true );
			if ( $key_validate != 0 ) {
				dgx_donate_debug_log( "Stripe key error: expecting 'sk_test_', got '" . substr( $stripe_test_secret_key, 0, 8 ) . "'" );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
		}
		if ( isset( $_POST['dgx_donate_stripe_server'] ) ) {
			update_option( 'dgx_donate_stripe_server', sanitize_text_field( $_POST['dgx_donate_stripe_server'] ) );
		}
		if ( isset( $_POST['dgx_donate_stripe_billing_address'] ) ) {
			update_option( 'dgx_donate_stripe_billing_address', sanitize_textarea_field( $_POST['dgx_donate_stripe_billing_address'] ) );
		}

		if ( $no_stripe_key_check != 'on' ) {
			if ( $stripe_live_api_key != '' ) {
				update_option( 'dgx_donate_live_stripe_api_key', $stripe_live_api_key );
			} else {
				dgx_donate_debug_log( "Stripe key error: expecting 'pk_live_' key, got empty field." );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			if ( $stripe_live_secret_key != '' ) {
				update_option( 'dgx_donate_live_stripe_secret_key', $stripe_live_secret_key );
			} else {
				dgx_donate_debug_log( "Stripe key error: expecting 'sk_live_' key, got empty field." );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			if ( $stripe_test_api_key != '' ) {
				update_option( 'dgx_donate_test_stripe_api_key', $stripe_test_api_key );
			} else {
				dgx_donate_debug_log( "Stripe key error: expecting 'pk_test_' key, got empty field." );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
			if ( $stripe_test_secret_key != '' ) {
				update_option( 'dgx_donate_test_stripe_secret_key', $stripe_test_secret_key );
			} else {
				dgx_donate_debug_log( "Stripe key error: expecting 'sk_test_' key, got empty field." );
				seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_stripe_settings' );

				return;
			}
		}

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_stripe_settings' );
	}
	if ( isset( $_POST['dgx_donate_button_settings_host_options'], $_POST['dgx_donate_button_settings_host_options_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_host_options_nonce'], 'dgx_donate_button_settings_host_options' ) ) {
			wp_die( 'Security violation detected [A013]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_form_via_action'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_form_via_action'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_form_via_action', $checkbox_value );
		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_ignore_form_nonce'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_ignore_form_nonce'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_ignore_form_nonce', $checkbox_value );
		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_browser_uuid'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_browser_uuid'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_browser_uuid', $checkbox_value );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_host_options' );
	}
	if ( isset( $_POST['dgx_donate_button_settings_tweaks'], $_POST['dgx_donate_button_settings_tweaks_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_tweaks_nonce'], 'dgx_donate_button_settings_tweaks' ) ) {
			wp_die( 'Security violation detected [A014]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_compact_menus'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_compact_menus'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_compact_menus', $checkbox_value );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_tweaks' );
	}
	if ( isset( $_POST['dgx_donate_button_settings_debug_options'], $_POST['dgx_donate_button_settings_debug_options_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_settings_debug_options_nonce'], 'dgx_donate_button_settings_debug_options' ) ) {
			wp_die( 'Security violation detected [A015]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_debug_mode'] ) ) {
			update_option( 'dgx_donate_debug_mode', sanitize_text_field( $_POST['dgx_donate_debug_mode'] ) );
			if ( sanitize_text_field( $_POST['dgx_donate_debug_mode'] ) == 'PENDING' ) {
				seamless_donations_get_pending_audit_transactions( 14 );
			}
		}
		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_log_obscure_name'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_log_obscure_name'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		} else {
			$checkbox_value = '';
		}

		$checkbox_value = '';
		if ( isset( $_POST['dgx_donate_rebuild_xref_by_name'] ) ) {
			if ( sanitize_key( strtolower( $_POST['dgx_donate_rebuild_xref_by_name'] ) ) == 'on' ) {
				$checkbox_value = '1';
			}
		}
		update_option( 'dgx_donate_rebuild_xref_by_name', $checkbox_value );
		if ( isset( $_POST['dgx_donate_legacy_addon_check'] ) ) {
			update_option( 'dgx_donate_legacy_addon_check', sanitize_text_field( $_POST['dgx_donate_legacy_addon_check'] ) );
		}
		if ( isset( $_POST['dgx_donate_no_stripe_key_check'] ) ) {
			update_option( 'dgx_donate_no_stripe_key_check', sanitize_text_field( $_POST['dgx_donate_no_stripe_key_check'] ) );
		} else {
			update_option( 'dgx_donate_no_stripe_key_check', '' );
		}
		if ( isset( $_POST['dgx_donate_ignore_paypal_ipn'] ) ) {
			update_option( 'dgx_donate_ignore_paypal_ipn', sanitize_text_field( $_POST['dgx_donate_ignore_paypal_ipn'] ) );
		}

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_settings_debug_options' );
	}
}

