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

// Exit if .php file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cmb2_admin_init', 'seamless_donations_admin_thanks_menu' );

// THANK YOU - MENU ////
function seamless_donations_admin_thanks_menu() {
	$args = array(
		'id'           => 'seamless_donations_tab_thanks_page',
		'title'        => 'Seamless Donations - Thank You Page',
		// page title.
		'menu_title'   => 'Thank You Page',
		// title on left sidebar.
		'tab_title'    => 'Thank You Page',
		// title displayed on the tab.
		'object_types' => array( 'options-page' ),
		'option_key'   => 'seamless_donations_tab_thanks',
		'parent_slug'  => 'seamless_donations_tab_main',
		'tab_group'    => 'seamless_donations_tab_set',
		'save_button'  => 'Save Settings',
	);

	// 'tab_group' property is supported in > 2.4.0.
	if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
		$args['display_cb'] = 'seamless_donations_cmb2_options_display_with_tabs';
	}

	do_action( 'seamless_donations_tab_thanks_before', $args );

	// call on button hit for page save.
	add_action( 'admin_post_seamless_donations_tab_thanks', 'seamless_donations_tab_thanks_process_buttons' );

	// clear previous error messages if coming from another page.
	seamless_donations_clear_cmb2_submit_button_messages( $args['option_key'] );

	$args           = apply_filters( 'seamless_donations_tab_thanks_menu', $args );
	$thanks_options = new_cmb2_box( $args );

	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && sanitize_key( $_REQUEST['page'] ) == 'seamless_donations_tab_thanks' ) {
		seamless_donations_admin_thanks_section_data( $thanks_options );
	}

	do_action( 'seamless_donations_tab_forms_after', $thanks_options );
}

// THANK YOU - SECTION - DATA //
function seamless_donations_admin_thanks_section_data( $section_options ) {
	// init values.
	$handler_function = 'seamless_donations_admin5_thanks_preload'; // set up the preload handler function.
	$section_options  = apply_filters( 'seamless_donations_tab_thanks_section_data', $section_options );

	$section_desc = 'On this page you can configure a special thank you message which will appear to your ';
	$section_desc .= 'donors after they complete their donations. This is separate from the thank you email ';
	$section_desc .= 'that gets emailed to your donors.';

	// promo.
	$feature_desc = 'Thank You Enhanced provides landing page redirect and short codes.';
	$feature_url  = 'https://zatzlabs.com/project/seamless-donations-thank-you-enhanced/';
	//$section_desc .= seamless_donations_get_feature_promo( $feature_desc, $feature_url, 'UPGRADE', ' ' );

	$allowed_html = array(
		'a'    => array(
			'href'   => array(),
			'target' => array(),
		),
		'span' => array(
			'style' => array(),
		),
	);
	//$section_desc = wp_kses( $section_desc, $allowed_html );

	seamless_donations_sd4_plugin_filter_remove(); // clean up for sd4 add-on.
	$section_desc = apply_filters( 'seamless_donations_admin_thanks_section_note', $section_desc );

	$section_options = seamless_donations_admin_give_banner($section_options);

	seamless_donations_cmb2_add_static_desc( $section_options, $section_desc, 'thank_you_desc' );

	$section_options->add_field(
		array(
			'name'    => 'Thank You Page Text',
			'id'      => 'dgx_donate_thanks_text',
			'type'    => 'textarea',
			'default' => 'Thank you for donating!  A thank you email with the details of your donation will be sent to the email address you provided.',
			'desc'    => __( 'The text to display to a donor after a donation is completed.', 'seamless_donations' ),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_thanks_text', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_thanks_settings',
			'button_text'        => 'Save Text',
			'button_success_msg' => __( 'Thank you message saved.', 'seamless-donations' ),
			'button_error_msg'   => __( 'Please enter an appropriate thank you message.', 'seamless-donations' ),
		)
	);
	$section_options  = apply_filters( 'seamless_donations_tab_thanks_section_data_options', $section_options );
}

// THANK YOU OPTIONS - PRELOAD DATA ////
function seamless_donations_admin5_thanks_preload( $data, $object_id, $args, $field ) {
	// preload function to ensure compatibility with pre-5.0 settings data.

	// find out what field we're setting.
	$field_id = $args['field_id'];

	// Pull from existing Seamless Donations data formats.
	switch ( $field_id ) {
		// defaults.
		case 'dgx_donate_thanks_text':
			return ( get_option( 'dgx_donate_thanks_text' ) );
	}

	return '';
}

// FORM OPTIONS - PROCESS FORM SUBMISSIONS ////
function seamless_donations_tab_thanks_process_buttons() {
	/* Process Save changes button */

	if ( isset( $_POST['dgx_donate_button_thanks_settings'], $_POST['dgx_donate_button_thanks_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_thanks_settings_nonce'], 'dgx_donate_button_thanks_settings' ) ) {
			wp_die( 'Security violation detected [A018]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_thanks', $_POST );

		$note = '';
		if ( isset( $_POST['dgx_donate_thanks_text'] ) ) {
			$note = sanitize_textarea_field( trim( $_POST['dgx_donate_thanks_text'] ) );
		}
		$note = stripslashes( $note );

		$allowed_html = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'span'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'div'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'b'      => array(),
			'i'      => array(),
			'h1'     => array(),
			'h2'     => array(),
			'h3'     => array(),
		);
		$note         = wp_kses( $note, $allowed_html );

		if ( $note == '' ) { // ain't right.
			seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_thanks_settings' );
		} else {
			update_option( 'dgx_donate_thanks_text', $note );
			seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_thanks_settings' );
		}
	}
	if ( isset( $_POST['dgx_donate_button_url_settings'], $_POST['dgx_donate_button_url_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_url_settings_nonce'], 'dgx_donate_button_url_settings' ) ) {
			wp_die( 'Security violation detected [A019]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_thanks', $_POST );
	}

	return true;
}
