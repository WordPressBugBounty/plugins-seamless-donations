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
 * @platform WordPress
 */

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function seamless_donations_generate_donation_form( $returnHTML = true ) {
	global $wp;
	$process_form_via = get_option( 'dgx_donate_form_via_action' );
	$form_style       = get_option( 'dgx_donate_form_style' );
	// if the option isn't defined, returns false, if defined = '1'
	// this option exists for host compatibility, where some hosts won't send a formhow h
	// to another .php file for processing

	$form_action             = get_permalink();
	$process_form_via_status = 'initiating page';

	$browser_based_ids = get_option( 'dgx_donate_browser_uuid' );
	if ( $browser_based_ids == '1' ) {
		$session_id = 'browser-uuid'; // generate UUID in JavaScript
		$id_type    = 'browser UUID';
	} else {
		$session_id = seamless_donations_get_guid( 'sd' ); // UUID on server
		$id_type    = 'server UUID';
	}

	dgx_donate_debug_log( '----------------------------------------' );
	dgx_donate_debug_log( 'PREPARING DONATION FORM' );
	dgx_donate_debug_log( 'Seamless Donations Version: ' . dgx_donate_get_version() );
	$dev_build = seamless_donations_get_development_build();
	if ( $dev_build != '' ) {
		dgx_donate_debug_log( 'Build: ' . $dev_build );
	}
	dgx_donate_debug_log( 'User browser: ' . seamless_donations_get_browser_name() );
	dgx_donate_debug_log( "Assigning hidden field session ID to $session_id" );
	dgx_donate_debug_log( "Session IDs determined by $id_type." );
	dgx_donate_debug_log( "Form action via: $process_form_via_status" );
	dgx_donate_debug_log( "Form action: $form_action" );
	dgx_donate_debug_log( "Form style: $form_style" );

	$form = array(
		'id'       => 'seamless-donations-form',
		'name'     => 'seamless-donations-form',
		'action'   => $form_action,
		'method'   => 'post',
		'elements' => array(
			'session_id_element'   => array(
				'type'  => 'hidden',
				// Save the session ID as a hidden input
				'group' => '_dgx_donate_session_id',
				'value' => $session_id,
			),
			'redirect_url_element' => array(
				'type'  => 'hidden',
				// Save the form action as a hidden input
				'group' => '_dgx_donate_redirect_url',
				'value' => $form_action,
			),
			'success_url_element'  => array(
				'type'  => 'hidden',
				// Save the PayPal redirect URL as a hidden input
				'group' => '_dgx_donate_success_url',
				'value' => dgx_donate_paypalstd_get_current_url(),
			),
			'process_via'          => array(
				'type'  => 'hidden',
				// Save the form processing method as a hidden input
				'group' => '_dgx_donate_form_via',
				'value' => $process_form_via,
			),
		),
	);

	// Start the outermost container
	$form['outermost_container'] = array(
		'id' => 'dgx-donate-container',
	);

	// Pick and choose the built in sections this gateway supports

	$warning_section = seamless_donations_donation_form_warning_section();
	if ( is_array( $warning_section ) ) {
		$form['outermost_container']['warning_section'] = $warning_section;
	}

	if ( $form_style != 'classic' && $form_style != 'none' ) {
		$form['outermost_container']['header_section'] = seamless_donations_get_header_section();
	}
	$form['outermost_container']['donation_section'] = seamless_donations_get_donation_section();
	$form['outermost_container']['tribute_section']  = seamless_donations_get_tribute_section();
	$form['outermost_container']['donor_section']    = seamless_donations_get_donor_section();
	$form['outermost_container']['billing_section']  = seamless_donations_get_billing_section();
	$form['outermost_container']['paypal_section']   = seamless_donations_get_paypal_section();
	$form['outermost_container']['submit_section']   = seamless_donations_get_submit_section();
	if ( $form_style != 'classic' && $form_style != 'none' ) {
		$form['outermost_container']['footer_section'] = seamless_donations_get_footer_section();
	}

	// debug mode for payment testing, saves lots of typing while coding
	$debug_mode = get_option( 'dgx_donate_debug_mode' );
	if ( $debug_mode == 'PREFILL' ) {
		$form['outermost_container']['donor_section']['elements']['_dgx_donate_donor_first_name']['value'] = 'First';
		$form['outermost_container']['donor_section']['elements']['_dgx_donate_donor_last_name']['value']  = 'Last';
		$form['outermost_container']['donor_section']['elements']['_dgx_donate_donor_email']['value']      = 'test@test.com';
		if ( isset( $form['outermost_container']['donation_section']['elements']['dgx_donate_giving_level_1000']['select'] ) ) {
			unset( $form['outermost_container']['donation_section']['elements']['dgx_donate_giving_level_1000']['select'] );
		}
		$form['outermost_container']['donation_section']['elements']['dgx_donate_giving_level_5']['select'] = 'true';
	}

	$form = apply_filters( 'seamless_donations_form_section_order', $form );

	// save the form url
	$current_url = home_url( add_query_arg( array(), $wp->request ) );
	update_option( 'dgx_donate_form_url', $current_url );

	if ( $returnHTML ) {
		// build and display the form
		$html = seamless_donations_forms_engine( $form );
		return $html;
	} else {
		return $form;
	}
}

function seamless_donations_donation_form_warning_section() {
	// Display any setup warnings we need to display here (e.g. running in test mode)
	$form_style   = get_option( 'dgx_donate_form_style' );
	$gateway_mode = 'LIVE';
	$gateway      = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $gateway == 'STRIPE' ) {
		$gateway_mode = get_option( 'dgx_donate_stripe_server' );
		if ( strcasecmp( $gateway_mode, 'SANDBOX' ) == 0 ) {
			$gateway_mode = 'SANDBOX';
		}
	} else {
		$payPalServer = get_option( 'dgx_donate_paypal_server' );
		if ( strcasecmp( $payPalServer, 'SANDBOX' ) == 0 ) {
			$gateway_mode = 'SANDBOX';
		}
	}

	if ( $gateway_mode == 'SANDBOX' ) {
		$warning = array(
			'id'       => 'dgx-donate-form-sandbox-warning',
			'class'    => 'dgx-donate-form-section',
			'elements' => array(
				array(
					'type'   => 'static',
					'before' => '<p>',
					'after'  => '</p>',
					'value'  => esc_html__(
						'Warning - Seamless Donations is currently configured to use the sandbox test server',
						'seamless-donations'
					),
				),
			),
		);

		return $warning;
	} else {
		return false;
	}
}

function seamless_donations_get_header_section() {
	$header = array(
		'id'       => 'dgx-donate-form-header-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'dgx-donate-form-header-div' => array(
				'type' => 'static',
			),
		),
	);

	return $header;
}

function seamless_donations_get_donation_section() {
	$form_style = get_option( 'dgx_donate_form_style' );

	$donation_section = array(
		'id'       => 'dgx-donate-form-donation-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'donation_header' => array(
				'type'   => 'static',
				'before' => '<h2>',
				'after'  => '</h2>',
				'value'  => esc_html__( 'Donation Information', 'seamless-donations' ),
			),
			'header_desc'     => array(
				'type'   => 'static',
				'before' => '<p>',
				'after'  => '</p>',
				'value'  => esc_html__( 'I would like to make a donation in the amount of:', 'seamless-donations' ),
			),
		),
	);

	// assemble the radio buttons for the giving levels
	$index         = 0;
	$giving_levels = dgx_donate_get_giving_levels();
	foreach ( $giving_levels as $giving_level ) {
		$giving_level_key    = 'dgx_donate_giving_level_' . $giving_level;
		$giving_level_option = get_option( $giving_level_key );

		if ( $giving_level_option == 'yes' ) {
			$formatted_amount = seamless_donations_get_escaped_formatted_amount( $giving_level, 0 );
			$element          = array(
				'type'    => 'radio',
				'group'   => '_dgx_donate_amount',
				'conceal' => 'other-donation-level',
				'wrapper' => 'span',
				'value'   => $giving_level,
				'prompt'  => $formatted_amount,
				'class'   => 'horiz',
			);
			if ( $index == 0 ) {
				$element['select'] = true;
			}
			// if ($index > 0) {
			// $element['class'] = 'horiz';
			// }
			$donation_section['elements'][ $giving_level_key ] = $element;
			++ $index;
		}
	}

	// add the "other" button and the text zone for "other" entry
	$donation_section['elements']['other_radio_button'] = array(
		'type'    => 'radio',
		'group'   => '_dgx_donate_amount',
		'prompt'  => esc_html__( 'Other', 'seamless-donations' ),
		'reveal'  => 'other-donation-level',
		'wrapper' => 'span',
		'value'   => 'OTHER',
		'class'   => 'horiz',
		'id'      => 'dgx-donate-other-radio',
	);

	$donation_section['elements']['_dgx_donate_user_amount'] = array(
		'type'        => 'text',
		'size'        => 15,
		'class'       => 'aftertext seamless-donations-form-row',
		'class-label' => 'seamless-donations-col-25',
		'class-input' => 'seamless-donations-col-25',
		'validation'  => 'currency',
		'cloak'       => 'other-donation-level',
		'id'          => '_dgx_donate_user_amount',
		'before'      => esc_html__( 'Other: ', 'seamless-donations' ),
	);

	// Designated Funds

	if ( get_option( 'dgx_donate_show_designated_funds_section' ) == 'true' ) {
		// in 4.0+ funds are a custom post type, not an option array

		$query_args  = array(
			'orderby'     => 'title',
			'order'       => 'asc',
			'post_type'   => 'funds',
			'post_status' => 'publish',
			'numberposts' => - 1,
			'meta_query'  => array(
				array(
					'key'   => '_dgx_donate_fund_show',
					'value' => 'Yes',
				),
			),
		);
		$posts_array = get_posts( $query_args );

		$fund_count = count( $posts_array );

		if ( $fund_count > 0 ) {
			// designated fund checkbox
			$donation_section['elements']['_dgx_donate_designated'] = array(
				'type'   => 'checkbox',
				'id'     => 'dgx-donate-designated',
				'reveal' => 'specific-fund',
				'prompt' => esc_html__(
					'I would like this donation to go to a specific fund',
					'seamless-donations'
				),
				'before' => '<p>',
				'after'  => '</p>',
			);

			if ( $form_style == 'classic' ) {
				unset( $donation_section['elements']['_dgx_donate_designated']['before'] );
				unset( $donation_section['elements']['_dgx_donate_designated']['after'] );
			}

			$options_array = array(
				0 => 'No fund specified',
			);
			foreach ( $posts_array as $post ) {
				$title                = $post->post_title;
				$id                   = $post->ID;
				$options_array[ $id ] = $title;
			}

			$funds_section                     = array(
				'elements' => array(
					'_dgx_donate_designated_fund' => array(
						'type'        => 'select',
						'cloak'       => 'specific-fund',
						'class'       => 'seamless-donations-form-row',
						'class-label' => 'seamless-donations-col-25',
						'class-input' => 'seamless-donations-col-75',
						'options'     => $options_array,
						'before'      => esc_html__( 'Fund: ', 'seamless-donations' ),
					),
				),
			);
			$donation_section['funds_section'] = $funds_section;
		}
	}

	// Repeating donations
	if ( get_option( 'dgx_donate_show_repeating_option' ) == 'true' ) {
		$repeating_section                     = array(
			'elements' => array(
				'_dgx_donate_repeating' => array(
					'type'   => 'checkbox',
					'id'     => 'dgx-donate-repeating',
					'prompt' => esc_html__(
						'I would like this donation to repeat each month',
						'seamless-donations'
					),
					'before' => '<p>',
					'after'  => '</p>',
				),
			),
		);
		$donation_section['repeating_section'] = $repeating_section;
	}

	$donation_section = apply_filters(
		'seamless_donations_form_donation_section',
		$donation_section
	);

	return $donation_section;
}

function seamless_donations_get_tribute_section() {
	$form_style = get_option( 'dgx_donate_form_style' );
	if ( get_option( 'dgx_donate_show_tribute_section' ) == 'true' ) {
		$default_country = get_option( 'dgx_donate_default_country' );
		$default_state   = get_option( 'dgx_donate_default_state' );
		$countries_array = dgx_donate_get_countries();
		$states_array    = dgx_donate_get_states();
		$provinces_array = dgx_donate_get_provinces();

		$postal_code_array        = dgx_donate_get_countries_requiring_postal_code();
		$postal_code_reveal_array = array();
		$postal_code_array_count  = count( $postal_code_array );
		for ( $postal_code_index = 0; $postal_code_index < $postal_code_array_count; ++ $postal_code_index ) {
			$postal_code_country = $postal_code_array[ $postal_code_index ];
			switch ( $postal_code_country ) {
				case 'US':
					$postal_code_reveal_array['US'] = 'conceal-state conceal-postcode';
					break;
				case 'CA':
					$postal_code_reveal_array['CA'] = 'conceal-province conceal-postcode';
					break;
				default:
					$postal_code_reveal_array[ $postal_code_country ] = 'conceal-postcode';
			}
		}

		$tribute_section = array(
			'id'       => 'dgx-donate-form-tribute-section',
			'class'    => 'dgx-donate-form-section',
			'elements' => array(
				'donation_header'                => array(
					'type'   => 'static',
					'before' => '<h2>',
					'after'  => '</h2>',
					'value'  => esc_html__( 'Tribute Gift', 'seamless-donations' ),
				),
				'_dgx_donate_tribute_gift'       => array(
					'type'    => 'checkbox',
					'id'      => 'dgx-donate-tribute',
					'reveal'  => 'in-honor',
					'check'   => '_dgx_donate_honor_by_email',
					'conceal' => 'postal-acknowledgement conceal-state conceal-postcode conceal-province',
					'prompt'  => esc_html__(
						'Check here to donate in honor or memory of someone',
						'seamless-donations'
					),
					'before'  => '<p>',
					'after'   => '</p>',
				),
				'_dgx_donate_memorial_gift'      => array(
					'type'   => 'checkbox',
					'cloak'  => 'in-honor',
					'prompt' => esc_html__(
						'Check here if this is a memorial gift',
						'seamless-donations'
					),
					'before' => '<P>',
					'after'  => '</P>',
				),
				'_dgx_donate_honoree_name'       => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'in-honor',
					'size'        => 20,
					'before'      => esc_html__( 'Honoree: ', 'seamless-donations' ),
				),
				'_dgx_donate_honor_by_email'     => array(
					'type'    => 'radio',
					'cloak'   => 'in-honor',
					'select'  => true,
					'group'   => '_dgx_donate_honor_by_email',
					'value'   => 'TRUE',
					'reveal'  => 'email-acknowledgement',
					'conceal' => 'postal-acknowledgement conceal-state conceal-postcode conceal-province',
					'prompt'  => esc_html__(
						'Send acknowledgement via email',
						'seamless-donations'
					),
					'before'  => '<p>',
					'after'   => '</p>',
				),
				'_dgx_donate_honor_by_post_mail' => array(
					'type'    => 'radio',
					'cloak'   => 'in-honor',
					'group'   => '_dgx_donate_honor_by_email',
					'value'   => 'FALSE',
					'reveal'  => 'postal-acknowledgement',
					'conceal' => 'email-acknowledgement',
					'prompt'  => esc_html__(
						'Send acknowledgement via postal mail',
						'seamless-donations'
					),
					'before'  => '<p>',
					'after'   => '</p>',
				),
				'_dgx_donate_honoree_email_name' => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'in-honor email-acknowledgement',
					'size'        => 20,
					'before'      => esc_html__( 'Email Name: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_email'      => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'in-honor email-acknowledgement',
					'validation'  => 'email',
					'size'        => 20,
					'before'      => esc_html__( 'Email: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_post_name'  => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'postal-acknowledgement',
					'size'        => 20,
					'before'      => esc_html__( 'Name: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_address'    => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'postal-acknowledgement',
					'size'        => 20,
					'before'      => esc_html__( 'Address: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_city'       => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-75',
					'cloak'       => 'postal-acknowledgement',
					'size'        => 20,
					'before'      => esc_html__( 'City: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_state'      => array(
					'type'    => 'select',
					'cloak'   => 'conceal-state',
					'size'    => 1,
					'options' => $states_array,
					'value'   => $default_state,
					'before'  => esc_html__( 'State : ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_province'   => array(
					'type'    => 'select',
					'cloak'   => 'conceal-province',
					'size'    => 1,
					'options' => $provinces_array,
					'before'  => esc_html__( 'Province: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_country'    => array(
					'type'    => 'select',
					'cloak'   => 'postal-acknowledgement',
					'conceal' => 'conceal-state conceal-postcode conceal-province',
					'reveal'  => $postal_code_reveal_array,
					'options' => $countries_array,
					'value'   => $default_country,
					'size'    => 1,
					'before'  => esc_html__( 'Country: ', 'seamless-donations' ),
				),
				'_dgx_donate_honoree_zip'        => array(
					'type'        => 'text',
					'class'       => 'seamless-donations-form-row',
					'class-label' => 'seamless-donations-col-25',
					'class-input' => 'seamless-donations-col-25',
					'cloak'       => 'conceal-postcode',
					'size'        => 10,
					'before'      => esc_html__( 'Post Code: ', 'seamless-donations' ),
				),

			),
		);
		if ( $form_style == 'classic' ) {
			unset( $tribute_section['elements']['_dgx_donate_tribute_gift']['before'] );
			unset( $tribute_section['elements']['_dgx_donate_tribute_gift']['after'] );
			unset( $tribute_section['elements']['_dgx_donate_memorial_gift']['before'] );
			unset( $tribute_section['elements']['_dgx_donate_memorial_gift']['after'] );
			unset( $tribute_section['elements']['_dgx_donate_honor_by_email']['before'] );
			unset( $tribute_section['elements']['_dgx_donate_honor_by_email']['after'] );
			unset( $tribute_section['elements']['_dgx_donate_honor_by_post_mail']['before'] );
			unset( $tribute_section['elements']['_dgx_donate_honor_by_post_mail']['after'] );
		}
	} else {
		$tribute_section = array();
	}

	$tribute_section = apply_filters(
		'seamless_donations_form_tribute_section',
		$tribute_section
	);

	return $tribute_section;
}

function seamless_donations_get_donor_section() {
	$form_style    = get_option( 'dgx_donate_form_style' );
	$donor_section = array(
		'id'       => 'dgx-donate-form-donor-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'donation_header'              => array(
				'type'   => 'static',
				'before' => '<h2>',
				'after'  => '</h2>',
				'value'  => esc_html__( 'Donor Information', 'seamless-donations' ),
			),
			'_dgx_donate_donor_first_name' => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-25',
				'size'        => 20,
				'validation'  => 'required',
				'before'      => esc_html__( 'First Name:', 'seamless-donations' ),
			),
			'_dgx_donate_donor_last_name'  => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-25',
				'size'        => 20,
				'validation'  => 'required',
				'before'      => esc_html__( 'Last Name:', 'seamless-donations' ),
			),
			'_dgx_donate_donor_email'      => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 20,
				'validation'  => 'required,email',
				'before'      => esc_html__( 'Email:', 'seamless-donations' ),
			),
		),
	);

	$donor_anonymous = get_option( 'dgx_donate_show_anonymous_option' );
	if ( $donor_anonymous == 'true' ) {
		$donor_section['elements']['_dgx_donate_anonymous'] = array(
			'type'   => 'checkbox',
			'prompt' => esc_html__(
				'Please do not display my name.',
				'seamless-donations'
			),
			'before' => '<P>',
			'after'  => '</P>',
		);
	}
	if ( $form_style == 'classic' ) {
		unset( $donor_section['elements']['_dgx_donate_anonymous']['before'] );
		unset( $donor_section['elements']['_dgx_donate_anonymous']['after'] );
	}

	$show_list_subscribe = get_option( 'dgx_donate_show_mailing_list_option' );
	if ( $show_list_subscribe == 'true' ) {
		$donor_section['elements']['_dgx_donate_add_to_mailing_list'] = array(
			'type'   => 'checkbox',
			'prompt' => esc_html__(
				'Add me to your mailing list',
				'seamless-donations'
			),
			'before' => '<P>',
			'after'  => '</P>',
		);
	}
	if ( $form_style == 'classic' ) {
		unset( $donor_section['elements']['_dgx_donate_anonymous']['before'] );
		unset( $donor_section['elements']['_dgx_donate_anonymous']['after'] );
	}

	$show_donor_telephone_field = get_option( 'dgx_donate_show_donor_telephone_field' );
	if ( $show_donor_telephone_field != 'false' ) {
		$donor_section['elements']['_dgx_donate_donor_phone'] = array(
			'type'        => 'text',
			'class'       => 'seamless-donations-form-row',
			'class-label' => 'seamless-donations-col-25',
			'class-input' => 'seamless-donations-col-25',
			'size'        => 20,
			'before'      => esc_html__( 'Phone:', 'seamless-donations' ),
		);
		if ( $show_donor_telephone_field == 'required' ) {
			$donor_section['elements']['_dgx_donate_donor_phone']['validation'] = 'required';
		}
	}

	$show_donor_employer_field    = get_option( 'dgx_donate_show_donor_employer_field' );
	$show_employer_match_checkbox = get_option( 'dgx_donate_show_employer_section' );
	$show_donor_occupation_field  = get_option( 'dgx_donate_show_donor_occupation_field' );

	if ( $show_donor_employer_field != 'false' ) {
		$donor_section['elements']['_dgx_donate_employer_name'] = array(
			'type'        => 'text',
			'class'       => 'seamless-donations-form-row',
			'class-label' => 'seamless-donations-col-25',
			'class-input' => 'seamless-donations-col-75',
			'size'        => 20,
			'before'      => esc_html__( 'Employer:', 'seamless-donations' ),
		);
		if ( $show_donor_employer_field == 'required' ) {
			$donor_section['elements']['_dgx_donate_employer_name']['validation'] = 'required';
		}
		if ( $show_employer_match_checkbox != 'false' ) {
			$donor_section['elements']['_dgx_donate_employer_match'] = array(
				'type'   => 'checkbox',
				'prompt' => esc_html__(
					'My employer matches donations',
					'seamless-donations'
				),
				'before' => '<P>',
				'after'  => '</P>',
			);
		}
	}
	if ( $form_style == 'classic' ) {
		unset( $donor_section['elements']['_dgx_donate_employer_match']['before'] );
		unset( $donor_section['elements']['_dgx_donate_employer_match']['after'] );
	}

	if ( $show_donor_occupation_field != 'false' ) {
		$donor_section['elements']['_dgx_donate_occupation'] = array(
			'type'        => 'text',
			'class'       => 'seamless-donations-form-row',
			'class-label' => 'seamless-donations-col-25',
			'class-input' => 'seamless-donations-col-75',
			'size'        => 20,
			'before'      => esc_html__( 'Occupation:', 'seamless-donations' ),
		);
		if ( $show_donor_occupation_field == 'required' ) {
			$donor_section['elements']['_dgx_donate_occupation']['validation'] = 'required';
		}
	}

	$donor_section = apply_filters(
		'seamless_donations_form_donor_section',
		$donor_section
	);

	return $donor_section;
}

function seamless_donations_get_billing_section() {
	$form_style   = get_option( 'dgx_donate_form_style' );
	$show_address = get_option( 'dgx_donate_show_donor_address_fields' );

	if ( $show_address != 'true' ) {
		return array();
	}

	$default_country = get_option( 'dgx_donate_default_country' );
	$default_state   = get_option( 'dgx_donate_default_state' );
	$countries_array = dgx_donate_get_countries();
	$states_array    = dgx_donate_get_states();
	$provinces_array = dgx_donate_get_provinces();

	$postal_code_array        = dgx_donate_get_countries_requiring_postal_code();
	$postal_code_reveal_array = array();
	$postal_code_array_count  = count( $postal_code_array );
	for ( $postal_code_index = 0; $postal_code_index < $postal_code_array_count; ++ $postal_code_index ) {
		$postal_code_country = $postal_code_array[ $postal_code_index ];
		switch ( $postal_code_country ) {
			case 'US':
				$postal_code_reveal_array['US'] = 'conceal-donor-state conceal-donor-postcode';
				break;
			case 'CA':
				$postal_code_reveal_array['CA'] = 'conceal-donor-province conceal-donor-postcode';
				break;
			case 'GB':
				$postal_code_reveal_array['GB'] = 'gift-aid conceal-donor-postcode';
				break;
			default:
				$postal_code_reveal_array[ $postal_code_country ] = 'conceal-donor-postcode';
		}
	}

	$billing_section = array(
		'id'       => 'dgx-donate-form-billing-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'donation_header'            => array(
				'type'   => 'static',
				'before' => '<h2>',
				'after'  => '</h2>',
				'value'  => esc_html__( 'Donor Address', 'seamless-donations' ),
			),
			'_dgx_donate_donor_address'  => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 20,
				'validation'  => 'required',
				'before'      => esc_html__( 'Address: ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_address2' => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 20,
				'before'      => esc_html__( 'Address 2: ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_city'     => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 20,
				'validation'  => 'required',
				'before'      => esc_html__( 'City: ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_state'    => array(
				'type'        => 'select',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 1,
				'options'     => $states_array,
				'value'       => $default_state,
				'before'      => esc_html__( 'State : ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_province' => array(
				'type'        => 'select',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'size'        => 1,
				'options'     => $provinces_array,
				'before'      => esc_html__( 'Province: ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_country'  => array(
				'type'        => 'select',
				'conceal'     => 'conceal-donor-state conceal-donor-postcode conceal-donor-province gift-aid',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-75',
				'reveal'      => $postal_code_reveal_array,
				'options'     => $countries_array,
				'value'       => $default_country,
				'size'        => 1,
				'before'      => esc_html__( 'Country: ', 'seamless-donations' ),
			),
			'_dgx_donate_donor_zip'      => array(
				'type'        => 'text',
				'class'       => 'seamless-donations-form-row',
				'class-label' => 'seamless-donations-col-25',
				'class-input' => 'seamless-donations-col-25',
				'size'        => 10,
				'before'      => esc_html__( 'Post Code: ', 'seamless-donations' ),
			),
			'_dgx_donate_uk_gift_aid'    => array(
				'type'   => 'checkbox',
				'prompt' => esc_html__(
					'I am a UK taxpayer and my gift qualifies for Gift Aid.',
					'seamless-donations'
				),
			),
		),
	);

	// since these fields are visible on page load, we need to make certain fields visible based on country
	// therefore, we don't want them cloaked, but we want them still interactive, so we'll put them in
	// class instead.
	if ( isset( $billing_section['elements']['_dgx_donate_donor_state']['class'] ) ) {
		if ( $billing_section['elements']['_dgx_donate_donor_state']['class'] != '' ) {
			$billing_section['elements']['_dgx_donate_donor_state']['class'] .= ' ';
		}
	} else {
		$billing_section['elements']['_dgx_donate_donor_state']['class'] = '';
	}
	if ( isset( $billing_section['elements']['_dgx_donate_donor_zip']['class'] ) ) {
		if ( $billing_section['elements']['_dgx_donate_donor_zip']['class'] != '' ) {
			$billing_section['elements']['_dgx_donate_donor_zip']['class'] .= ' ';
		}
	} else {
		$billing_section['elements']['_dgx_donate_donor_zip']['class'] = '';
	}

	switch ( $default_country ) {
		case 'US':
			$billing_section['elements']['_dgx_donate_donor_state']['class']    .= 'conceal-donor-state';
			$billing_section['elements']['_dgx_donate_donor_province']['cloak'] = 'conceal-donor-province';
			$billing_section['elements']['_dgx_donate_donor_zip']['class']      .= 'conceal-donor-postcode';
			$billing_section['elements']['_dgx_donate_uk_gift_aid']['cloak']    = 'gift-aid';
			break;
		case 'CA':
			$billing_section['elements']['_dgx_donate_donor_state']['cloak']    = 'conceal-donor-state';
			$billing_section['elements']['_dgx_donate_donor_province']['class'] .= 'conceal-donor-province';
			$billing_section['elements']['_dgx_donate_donor_zip']['class']      .= 'conceal-donor-postcode';
			$billing_section['elements']['_dgx_donate_uk_gift_aid']['cloak']    = 'gift-aid';
			break;
		case 'GB':
			$billing_section['elements']['_dgx_donate_donor_state']['cloak']    = 'conceal-donor-state';
			$billing_section['elements']['_dgx_donate_donor_province']['cloak'] = 'conceal-donor-province';
			$billing_section['elements']['_dgx_donate_donor_zip']['class']      .= 'conceal-donor-postcode';
			$billing_section['elements']['_dgx_donate_uk_gift_aid']['class']    .= 'gift-aid';
			break;
		default:
			$billing_section['elements']['_dgx_donate_donor_state']['cloak']    = 'conceal-donor-state';
			$billing_section['elements']['_dgx_donate_donor_province']['cloak'] = 'conceal-donor-province';
			if ( dgx_donate_country_requires_postal_code( $default_country ) ) {
				$billing_section['elements']['_dgx_donate_donor_zip']['class'] .= 'conceal-donor-postcode';
			} else {
				$billing_section['elements']['_dgx_donate_donor_zip']['cloak'] = 'conceal-donor-postcode';
			}
			$billing_section['elements']['_dgx_donate_uk_gift_aid']['cloak'] = 'gift-aid';
	}

	$billing_section = apply_filters(
		'seamless_donations_form_billing_section',
		$billing_section
	);

	return $billing_section;
}

function seamless_donations_get_paypal_section() {
	$form_style  = get_option( 'dgx_donate_form_style' );
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

	$notify_url = seamless_donations_get_paypal_notification_url();
	// $session_id = session_id ();

	// set up success URL
	$success_url = dgx_donate_paypalstd_get_current_url();
	// if( strpos ( $success_url, "?" ) == false ) {
	// $success_url .= "?";
	// } else {
	// $success_url .= "&";
	// }
	// $success_url .= "thanks=1&sessionid=";
	// $success_url .= "$session_id";

	// not used in core code, but users might be including this somewhere
	$item_name = apply_filters( 'dgx_donate_item_name', __( 'Donation', 'seamless-donations' ) );

	$paypal_hidden_section = array(
		'id'       => 'dgx-donate-form-paypal-hidden-section',
		'class'    => 'dgx-donate-form-section',
		'style'    => 'display:none',
		// we want to hide this section from the form
		'elements' => array(
			'nonce'         => array(
				'type'  => 'hidden',
				'value' => wp_create_nonce( 'dgx-donate-nonce' ),
			),
			'cmd'           => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'business'      => array(
				'type'  => 'hidden',
				'value' => esc_attr( $paypal_email ),
			),
			'return'        => array(
				'type'  => 'hidden',
				'value' => '',
				// set later in payment function, not really needed here
			),
			'first_name'    => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'last_name'     => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'address1'      => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'address2'      => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'city'          => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'state'         => array(
				'type'  => 'hidden',
				// removed if country not US or Canada
				'value' => '',
			),
			'zip'           => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'country'       => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'email'         => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'custom'        => array(
				'type'  => 'hidden',
				'value' => '',
			),
			'notify_url'    => array(
				'type'  => 'hidden',
				'value' => esc_attr( $notify_url ),
			),
			'item_name'     => array(
				'type'  => 'hidden',
				'value' => esc_attr( $item_name ),
			),
			'amount'        => array(
				'type'  => 'hidden',
				'value' => '1.00',
			),
			'quantity'      => array(
				'type'  => 'hidden',
				'value' => '1',
			),
			'currency_code' => array(
				'type'  => 'hidden',
				'value' => esc_attr( $currency_code ),
			),
			'no_note'       => array(
				'type'  => 'hidden',
				'value' => '1',
			),
			'src'           => array(
				'type'  => 'hidden',
				// removed when not repeating
				'value' => '1',
			),
			'p3'            => array(
				'type'  => 'hidden',
				// removed when not repeating
				'value' => '',
			),
			't3'            => array(
				'type'  => 'hidden',
				// removed when not repeating
				'value' => '',
			),
			'a3'            => array(
				'type'  => 'hidden',
				// removed when not repeating
				'value' => '',
			),
		),
	);

	$paypal_hidden_section = apply_filters(
		'seamless_donations_form_paypal_section',
		$paypal_hidden_section
	);

	return $paypal_hidden_section;
}

function seamless_donations_get_submit_section() {
	$form_style                = get_option( 'dgx_donate_form_style' );
	$processing_image_url      = plugins_url( '/images/ajax-loader.gif', __FILE__ );
	$button_image_url          = plugins_url( '/images/paypal_btn_donate_lg.gif', __FILE__ );
	$disabled_button_image_url = plugins_url( '/images/paypal_btn_donate_lg_disabled.gif', __FILE__ );

	// nonce is already created, so we're good
	// $nonce = wp_create_nonce( 'dgx_donate_donation_form_nonce' );

	$submit_section = array(
		'id'       => 'dgx-donate-form-payment-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'dgx-donate-pay-enabled'  => array(
				'type'  => 'submit',
				'class' => 'dgx-donate-pay-enabled',
				'value' => esc_html__( 'Donate Now', 'seamless-donations' ),
			),
			'dgx-donate-pay-disabled' => array(
				'type'  => 'submit',
				'class' => 'dgx-donate-pay-disabled',
			),
			'dgx-donate-busy'         => array(
				'type'  => 'submit',
				'class' => 'dgx-donate-busy',
			),
			// 'dgx-donate-pay-nonce'    => array(
			// 'type'  => 'hidden',
			// 'id'    => 'dgx_donate_donation_form_nonce',
			// 'name'  => 'dgx_donate_donation_form_nonce',
			// 'value' => esc_attr( $nonce ),
			// ),
		),
	);

	// not entirely sure why dgx-donate-pay-disabled and dgx-donate-busy exist. Could be vestiges of
	// pre-4.0 code. But for now, I'm uncomfortable fully removing them, so I'm clearing them from
	// form styles where form style is None.
	if ( $form_style == 'none' ) {
		unset( $submit_section['elements']['dgx-donate-pay-disabled'] );
		unset( $submit_section['elements']['dgx-donate-busy'] );
	}

	if ( $form_style == 'classic' ) {
		$submit_section['elements']['dgx-donate-pay-enabled']['type']    = 'image';
		$submit_section['elements']['dgx-donate-pay-enabled']['source']  = esc_url( $button_image_url );
		$submit_section['elements']['dgx-donate-pay-disabled']['type']   = 'image';
		$submit_section['elements']['dgx-donate-pay-disabled']['source'] = esc_url( $disabled_button_image_url );
		$submit_section['elements']['dgx-donate-pay-disabled']['type']   = 'image';
		$submit_section['elements']['dgx-donate-pay-disabled']['source'] = esc_url( $processing_image_url );
	}

	$submit_section = apply_filters(
		'seamless_donations_form_submit_section',
		$submit_section
	);

	return $submit_section;
}

function seamless_donations_get_footer_section() {
	$footer = array(
		'id'       => 'dgx-donate-form-footer-section',
		'class'    => 'dgx-donate-form-section',
		'elements' => array(
			'dgx-donate-form-footer-div' => array(
				'type' => 'static',
			),
		),
	);

	return $footer;
}

