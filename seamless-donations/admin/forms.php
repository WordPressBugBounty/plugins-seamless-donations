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

add_action( 'cmb2_admin_init', 'seamless_donations_admin_forms_menu' ); // this builds the page

// FORM OPTIONS - SETUP ////
function seamless_donations_admin_forms_menu() {
	$args = array(
		'id'           => 'seamless_donations_tab_forms_page',
		'title'        => 'Seamless Donations - Form Options',
		'menu_title'   => 'Form Options',
		'tab_title'    => 'Form Options',
		'object_types' => array( 'options-page' ),
		'option_key'   => 'seamless_donations_tab_forms',
		'parent_slug'  => 'seamless_donations_tab_main',
		'tab_group'    => 'seamless_donations_tab_set',
		'save_button'  => 'Save Tweaks',
	);

	// 'tab_group' property is supported in > 2.4.0.
	if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
		$args['display_cb'] = 'seamless_donations_cmb2_options_display_with_tabs';
	}

	do_action( 'seamless_donations_tab_forms_before', $args );

	// call on button hit for page save
	add_action( 'admin_post_seamless_donations_tab_forms', 'seamless_donations_tab_forms_process_buttons' );

	// clear previous error messages if coming from another page
	seamless_donations_clear_cmb2_submit_button_messages( $args['option_key'] );

	$args          = apply_filters( 'seamless_donations_tab_forms_menu', $args );
	$forms_options = new_cmb2_box( $args );

	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && sanitize_key( $_REQUEST['page'] ) == 'seamless_donations_tab_forms' ) {
		$forms_options = seamless_donations_admin_give_banner($forms_options);
		seamless_donations_admin_forms_levels_section_data( $forms_options );
		do_action( 'seamless_donations_tab_forms_after_levels', $forms_options );

		seamless_donations_admin_forms_styles_section_data( $forms_options );
		seamless_donations_admin_forms_defaults_section_data( $forms_options );
		do_action( 'seamless_donations_tab_forms_after_defaults', $forms_options );

		seamless_donations_admin_forms_fields_section_data( $forms_options );
		do_action( 'seamless_donations_tab_forms_after_form_fields', $forms_options );

		seamless_donations_admin_forms_tweaks_section_data( $forms_options );
		do_action( 'seamless_donations_tab_forms_after', $forms_options );
	}
}

// FORM OPTIONS - SECTION - GIVING LEVELS ////
function seamless_donations_admin_forms_levels_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_forms_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_forms_levels_section_data', $section_options );

	// string setup
	$section_desc = 'Select one or more suggested giving levels for your donors to choose from.';
	$feature_desc = 'Giving Level Manager customizes donation levels, assigns labels for each level.';
	$feature_url  = 'http://zatzlabs.com/project/seamless-donations-giving-level-manager/';
	$section_desc .= seamless_donations_get_feature_promo( $feature_desc, $feature_url );

	$section_options->add_field(
		array(
			'name'        => 'Giving Levels',
			'id'          => 'seamless_donations_admin_forms_section_levels',
			'type'        => 'title',
			//'after_field' => $section_desc,
		)
	);

	$giving_levels       = dgx_donate_get_giving_levels();
	$giving_level_labels = array();
	$giving_level_count  = count( $giving_levels );
	for ( $i = 0; $i < $giving_level_count; ++ $i ) {
		$value                         = $giving_levels[ $i ];
		$giving_level_labels[ $value ] = $value;
	}

	$section_options->add_field(
		array(
			'name'              => 'Display Levels',
			'id'                => 'dgx_donate_giving_levels',
			'type'              => 'multicheck',
			'select_all_button' => true,
			'options'           => $giving_level_labels,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_giving_levels', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_forms_giving_levels',
			'button_text'        => 'Save Giving Levels',
			'button_success_msg' => __( 'Giving levels saved.', 'seamless-donations' ),
			'button_error_msg'   => __( 'At least one giving level is required.', 'seamless-donations' ),
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_forms_levels_section_data_options', $section_options );
}

// FORM OPTIONS - SECTION - STYLES ////
function seamless_donations_admin_forms_styles_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_forms_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_forms_styles_section_data', $section_options );

	$section_desc = 'Select the form style you want to present to your donors. ';
	$section_desc .= 'If you choose \'None\', no styles will be added. This is for ';
	$section_desc .= 'those who want to load their own styles or inherit from a theme.';

	$section_options->add_field(
		array(
			'name'        => 'Form Styles',
			'id'          => 'seamless_donations_admin_forms_section_styles',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$style_options = array(
		'classic' => 'Classic Style',
		'modern'  => 'Modern Style',
		'none'    => 'None',
	);

	$section_options->add_field(
		array(
			'name'              => 'Style',
			'id'                => 'dgx_donate_form_style',
			'type'              => 'select',
			'show_option_none'  => 'Please choose a form style',
			'select_all_button' => true,
			'options'           => $style_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_form_style', $handler_function );

	$beautiful_designs_list = array(
		'healthcare',
		'worship',
		'cathedral',
		'sandwich',
		'americana',
		'pets',
		'outdoors',
		'food',
		'lunch',
		'election',
		'peace',
		'flag',
		'kids',
		'city',
		'fitness',
	);

	$feature_desc  = 'Beautiful Donation Forms gives you 15 additional form designs with customizable images.';
	$feature_url   = 'https://zatzlabs.com/project/seamless-donations-beautiful-donation-forms/';
	$section_promo = seamless_donations_get_feature_promo( $feature_desc, $feature_url );

	// accordion-1st-open is a hack from the jQuery UI accordion element. See modified code in
	// js/accordion.js
	$first_look       = get_option( 'dgx_donate_form_pack_first_look' );
	$plugin_directory = plugin_dir_url( __DIR__ );
	if ( $first_look == false ) {
		$accordion_title = 'Designs included in the Beautiful Donation Forms pack';
		$before_code     = '<div class="accordion-1st-open"><H1>' . $accordion_title . '</H1>';
		$after_code      = '</div>';
		$after_comment   = '<i>This preview will default to minimized once you save any value on this page.</i>';
	} else {
		$accordion_title = 'Designs included in the Beautiful Donations Forms pack (click to expand)';
		$before_code     = '<div class="accordion"><H1>' . $accordion_title . '</H1>';
		$after_code      = '</div>';
		$after_comment   = '';
	}

	//$before_code = $section_promo . $before_code;

	foreach ( $beautiful_designs_list as $design ) {
		$beautiful_options[ $design ] = $design . ' style';
		$beautiful_images[ $design ]  = 'images/bdf/' . $design . '.jpg';
	}

	// this is from a modified radio_image type from CMB2. See
	// library/cmb2-addons/cmb2-radio-image.php for my hacks
	$section_options->add_field(
		array(
			'name'         => __( 'Beautiful Forms', 'seamless-donations' ),
			// 'desc'             => __( 'field description (optional)', 'seamless-donations' ),
			'id'           => 'dgx_donate_static_bdf_display',
			'before_field' => $before_code,
			'after_field'  => $after_code,
			'after'        => $after_comment,
			'type'         => 'radio_static',
			'options'      => $beautiful_options,
			'images_path'  => $plugin_directory,
			'images'       => $beautiful_images,
		)
	);

	$colorful_designs_list = array(
		'blue',
		'orange',
		'red',
		'green',
		'purple',
		'meadow',
		'undersea',
		'sunset',
		'peppermint',
		'iceberg',
		'dusk',
		'tropical',
		'khaki',
		'dockside',
		'latte',
		'leather',
		'baby',
		'neon',
		'champagne',
		'corporate',
	);

	$feature_desc  = 'Colorful Donation Forms gives you 20 additional form designs with customizable images.';
	$feature_url   = 'https://zatzlabs.com/project/seamless-donations-colorful-donation-forms/';
	$section_promo = seamless_donations_get_feature_promo( $feature_desc, $feature_url );

	// accordion-1st-open is a hack from the jQuery UI accordion element. See modified code in
	// js/accordion.js
	$first_look       = get_option( 'dgx_donate_form_pack_first_look' );
	$plugin_directory = plugin_dir_url( __DIR__ );
	if ( $first_look == false ) {
		$accordion_title = 'Designs included in the Colorful Donation Forms pack';
		$before_code     = '<div class="accordion-1st-open"><H1>' . $accordion_title . '</H1>';
		// $before_code     = '<div id="accordion"><H1>' . $accordion_title . '</H1>';
		$after_code    = '</div>';
		$after_comment = '<i>This preview will default to minimized once you save any value on this page.</i>';
	} else {
		$accordion_title = 'Designs included in the Colorful Donations Forms pack (click to expand)';
		$before_code     = '<div class="accordion"><H1>' . $accordion_title . '</H1>';
		$after_code      = '</div>';
		$after_comment   = '';
	}

	//$before_code = $section_promo . $before_code;

	foreach ( $colorful_designs_list as $design ) {
		$colorful_options[ $design ] = $design . ' style';
		$colorful_images[ $design ]  = 'images/cdf/' . $design . '.jpg';
	}

	// this is from a modified radio_image type from CMB2. See
	// library/cmb2-addons/cmb2-radio-image.php for my hacks
	$section_options->add_field(
		array(
			'name'         => __( 'Colorful Forms', 'seamless-donations' ),
			// 'desc'             => __( 'field description (optional)', 'seamless-donations' ),
			'id'           => 'dgx_donate_static_cdf_display',
			'before_field' => $before_code,
			'after_field'  => $after_code,
			'after'        => $after_comment,
			'type'         => 'radio_static',
			'options'      => $colorful_options,
			'images_path'  => $plugin_directory,
			'images'       => $colorful_images,
		)
	);

	seamless_donations_cmb2_add_action_button( $section_options, 'Save Style', 'dgx_donate_button_forms_style' );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_forms_style',
			'button_text'        => 'Save Style',
			'button_success_msg' => __( 'Style saved.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_forms_styles_section_data_options', $section_options );
}

// FORM OPTIONS - SECTION - DEFAULTS ////
function seamless_donations_admin_forms_defaults_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_forms_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_forms_defaults_section_data', $section_options );

	$section_desc
		= 'Select the currency you would like to receive donations in and the default country for the donation form.';

	$section_options->add_field(
		array(
			'name'        => 'Defaults',
			'id'          => 'seamless_donations_admin_forms_section_defaults',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$after           = '';
	$payment_gateway = get_option( 'dgx_donate_payment_processor_choice' );
	if ( $payment_gateway == 'STRIPE' ) {
		$currency = get_option( 'dgx_donate_currency' );
		if ( seamless_donations_stripe_is_zero_decimal_currency( $currency ) ) {
			$after = '<br><span style="color: #0071A1"><i>';
			$after .= 'Note: This is a zero-decimal currency. Do not set giving levels to anything under ';
			$after .= 'the US dollar equivalent of $0.50 or your users will get "Donation was cancelled." errors.';
			$after .= '</i></span>';
		}
	}

	$section_options->add_field(
		array(
			'name'              => 'Currency',
			'id'                => 'dgx_donate_currency',
			'type'              => 'select',
			'show_option_none'  => 'Please choose a default currency',
			'select_all_button' => true,
			'after'             => $after,
			'options'           => dgx_donate_get_currency_list(),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_currency', $handler_function );

	$form_extend_currency_desc = __( 'Expand Currency Options Presented Above', 'seamless-donations' ) . seamless_donations_display_label();
	$form_extend_currency_desc .= '<BR><I>';
	$form_extend_currency_desc .= 'This expands the available currency options for Stripe from 26 to 134. ';
	$form_extend_currency_desc .= "<br><span style='color:red'>Even if you're using PayPal, please test since this feature modifies ";
	$form_extend_currency_desc .= "code you'll be using as well. Check and then press Save Defaults for extended currencies ";
	$form_extend_currency_desc .= 'to be made available to Stripe users.</span>';
	$form_extend_currency_desc .= '</I>';

	$section_options->add_field(
		array(
			'name'  => __( 'More Currencies', 'seamless-donations' ),
			'id'    => 'dgx_donate_currency_extend',
			'type'  => 'checkbox',
			'after' => $form_extend_currency_desc,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_currency_extend', $handler_function );

	$section_options->add_field(
		array(
			'name'              => 'Country',
			'id'                => 'dgx_donate_default_country',
			'type'              => 'select',
			'show_option_none'  => 'Please choose a default country',
			'select_all_button' => true,
			'options'           => dgx_donate_get_countries(),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_default_country', $handler_function );

	// https://github.com/awran5/CMB2-conditional-logic
	$section_options->add_field(
		array(
			'name'              => 'State',
			'id'                => 'dgx_donate_default_state',
			'type'              => 'select',
			'show_option_none'  => 'Please choose a default state',
			'select_all_button' => true,
			'options'           => dgx_donate_get_states(),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_default_state', $handler_function );

	seamless_donations_cmb2_add_action_button( $section_options, 'Save Defaults', 'dgx_donate_button_forms_defaults' );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_forms_defaults',
			'button_text'        => 'Save Defaults',
			'button_success_msg' => __( 'Defaults saved.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_forms_defaults_section_data_options', $section_options );
}

// FORM OPTIONS - SECTION - FIELDS ////
function seamless_donations_admin_forms_fields_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_forms_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_forms_fields_section_data', $section_options );
	$payment_gateway  = get_option( 'dgx_donate_payment_processor_choice' );

	// string setup
	$section_desc
		= 'Choose which form fields and sections you would like to hide, show or require.';
	$feature_desc = 'Add a Note lets donors include a note with their donation.';
	$feature_url  = 'http://zatzlabs.com/project/seamless-donations-add-a-note/';
	$section_desc .= seamless_donations_get_feature_promo( $feature_desc, $feature_url );

	$section_options->add_field(
		array(
			'name'        => 'Form Fields and Sections',
			'id'          => 'seamless_donations_admin_forms_section_fields',
			'type'        => 'title',
			//'after_field' => $section_desc,
		)
	);

	$form_display_options              = array(
		'true'  => 'Show',
		'false' => 'Don\'t Show',
	);
	$form_display_options_with_require = array(
		'true'     => 'Show',
		'false'    => 'Don\'t Show',
		'required' => 'Require',
	);

	$section_options->add_field(
		array(
			'name'    => 'Designated Funds Checkbox and Section',
			'id'      => 'dgx_donate_show_designated_funds_section',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_designated_funds_section', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Repeating Donation Checkbox',
			'id'      => 'dgx_donate_show_repeating_option',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_repeating_option', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Tribute Gift Checkbox and Section',
			'id'      => 'dgx_donate_show_tribute_section',
			'type'    => 'select',
			'default' => 'true',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_tribute_section', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Employer Match Section',
			'id'      => 'dgx_donate_show_employer_section',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_employer_section', $handler_function );

	$after = '';
	if ( $payment_gateway == 'STRIPE' ) {
		$after = '<br><span style="color: #0071A1"><i>';
		$after .= 'Note: Stripe may require donors to re-enter their phone numbers on the checkout form.';
		$after .= '</i></span>';
	}

	$section_options->add_field(
		array(
			'name'    => 'Donor Telephone Field',
			'id'      => 'dgx_donate_show_donor_telephone_field',
			'type'    => 'select',
			'default' => 'true',
			'options' => $form_display_options_with_require,
			'after'   => $after,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_donor_telephone_field', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Donor Employer Field',
			'id'      => 'dgx_donate_show_donor_employer_field',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options_with_require,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_donor_employer_field', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Donor Occupation Field',
			'id'      => 'dgx_donate_show_donor_occupation_field',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options_with_require,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_donor_occupation_field', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Mailing List Checkbox',
			'id'      => 'dgx_donate_show_mailing_list_option',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_mailing_list_option', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Anonymous Donation Checkbox',
			'id'      => 'dgx_donate_show_anonymous_option',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_anonymous_option', $handler_function );

	$after = '';
	if ( $payment_gateway == 'STRIPE' ) {
		$after = '<br><span style="color: #0071A1"><i>';
		$after .= 'Note: Stripe may require donors to re-enter their addresses on the checkout form.';
		$after .= '</i></span>';
	}

	$section_options->add_field(
		array(
			'name'    => 'Donor Address Section',
			'id'      => 'dgx_donate_show_donor_address_fields',
			'type'    => 'select',
			'default' => 'false',
			'options' => $form_display_options,
			'after'   => $after,
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_show_donor_address_fields', $handler_function );

	// post-process adding stripe messages

	// $section_options->meta_box["fields"]["dgx_donate_show_donor_telephone_field"]["name"]
	// $section_options->meta_box["fields"]["dgx_donate_show_donor_address_fields"]["name"]

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_forms_fields',
			'button_text'        => 'Save Fields',
			'button_success_msg' => __( 'Fields saved.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_forms_fields_section_data_options', $section_options );
}

// FORM OPTIONS - SECTION - GIVING TWEAKS ////
function seamless_donations_admin_forms_tweaks_section_data( $section_options ) {
	// init values
	$handler_function = 'seamless_donations_admin5_forms_preload'; // setup the preload handler function
	$section_options  = apply_filters( 'seamless_donations_tab_forms_tweaks_section_data', $section_options );

	$section_desc = 'Options that can tweak your form.';

	$section_options->add_field(
		array(
			'name'        => 'Form Tweaks',
			'id'          => 'seamless_donations_admin_forms_section_tweaks',
			'type'        => 'title',
			'after_field' => $section_desc,
		)
	);

	$section_options->add_field(
		array(
			'name'    => 'Label Tag',
			'id'      => 'dgx_donate_labels_for_input',
			'type'    => 'checkbox',
			'default' => false,
			'desc'    => __( 'Add label tag to input form. May improve form layout for some themes and is helpful for CSS styling.', 'seamless-donations' ),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_labels_for_input', $handler_function );

	$section_options->add_field(
		array(
			'name'    => 'Stylesheet Priority',
			'id'      => 'dgx_donate_stylesheet_priority',
			'type'    => 'checkbox',
			'default' => false,
			'desc'    => __( 'Force stylesheet to load after themes. Try this if your theme messes up your form.', 'seamless-donations' ),
		)
	);
	seamless_donations_preload_cmb2_field_filter( 'dgx_donate_stylesheet_priority', $handler_function );

	seamless_donations_display_cmb2_submit_button(
		$section_options,
		array(
			'button_id'          => 'dgx_donate_button_forms_tweaks',
			'button_text'        => 'Save Tweaks',
			'button_success_msg' => __( 'Tweaks saved.', 'seamless-donations' ),
			'button_error_msg'   => '',
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_forms_tweaks_section_data_options', $section_options );
}

// FORM OPTIONS - PRELOAD DATA
function seamless_donations_admin5_forms_preload( $data, $object_id, $args, $field ) {
	// preload function to ensure compatibility with pre-5.0 settings data

	// find out what field we're setting
	$field_id = $args['field_id'];

	// Pull from existing Seamless Donations data formats
	switch ( $field_id ) {
		// giving levels
		case 'dgx_donate_giving_levels':
			$giving_levels       = dgx_donate_get_giving_levels();
			$giving_level_values = array();
			$giving_level_count  = count( $giving_levels );
			for ( $i = 0; $i < $giving_level_count; ++ $i ) {
				$value = $giving_levels[ $i ];
				if ( dgx_donate_is_giving_level_enabled( $value ) ) {
					$giving_level_values[] = $value;
				}
			}
			// multicheck UI values are saved as an indexed array
			// containing a sparse set of checked item names
			return $giving_level_values;

		// defaults
		case 'dgx_donate_form_style':
			return ( get_option( 'dgx_donate_form_style' ) );
		case 'dgx_donate_currency':
			return ( get_option( 'dgx_donate_currency' ) );
		case 'dgx_donate_currency_extend':
			return ( get_option( 'dgx_donate_currency_extend' ) );
		case 'dgx_donate_default_country':
			return ( get_option( 'dgx_donate_default_country' ) );
		case 'dgx_donate_default_state':
			return ( get_option( 'dgx_donate_default_state' ) );

		// fields
		case 'dgx_donate_show_designated_funds_section':
			return ( get_option( 'dgx_donate_show_designated_funds_section' ) );
		case 'dgx_donate_show_repeating_option':
			return ( get_option( 'dgx_donate_show_repeating_option' ) );
		case 'dgx_donate_show_tribute_section':
			return ( get_option( 'dgx_donate_show_tribute_section' ) );
		case 'dgx_donate_show_employer_section':
			return ( get_option( 'dgx_donate_show_employer_section' ) );
		case 'dgx_donate_show_donor_telephone_field':
			return ( get_option( 'dgx_donate_show_donor_telephone_field' ) );
		case 'dgx_donate_show_donor_employer_field':
			return ( get_option( 'dgx_donate_show_donor_employer_field' ) );
		case 'dgx_donate_show_donor_occupation_field':
			return ( get_option( 'dgx_donate_show_donor_occupation_field' ) );
		case 'dgx_donate_show_mailing_list_option':
			return ( get_option( 'dgx_donate_show_mailing_list_option' ) );
		case 'dgx_donate_show_anonymous_option':
			return ( get_option( 'dgx_donate_show_anonymous_option' ) );
		case 'dgx_donate_show_donor_address_fields':
			return ( get_option( 'dgx_donate_show_donor_address_fields' ) );

		// tweaks
		case 'dgx_donate_labels_for_input':
			if ( get_option( 'dgx_donate_labels_for_input' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
		case 'dgx_donate_stylesheet_priority':
			if ( get_option( 'dgx_donate_stylesheet_priority' ) == '1' ) {
				return 'on';
			} else {
				return '';
			}
	}
}

// FORM OPTIONS - PROCESS FORM SUBMISSIONS
function seamless_donations_tab_forms_process_buttons() {
	// This is a callback that has to be passed the full array for consideration
	// phpcs:ignore WordPress.Security.NonceVerification
	$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_forms', $_POST );
	update_option( 'dgx_donate_form_pack_first_look', 'viewed' );

	// Process Save Giving Levels button
	if ( isset( $_POST['dgx_donate_button_forms_giving_levels'], $_POST['dgx_donate_button_forms_giving_levels_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_forms_giving_levels_nonce'], 'dgx_donate_button_forms_giving_levels' ) ) {
			wp_die( 'Security violation detected [A001]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}

		$none_enabled = true;

		if ( isset( $_POST['dgx_donate_giving_levels'] ) ) {
			$giving_levels_sanitized = seamless_donations_sanitize_key_array( $_POST['dgx_donate_giving_levels'] );
			// clear existing giving level settings
			$giving_levels = dgx_donate_get_giving_levels();
			foreach ( $giving_levels as $giving_level ) {
				dgx_donate_disable_giving_level( $giving_level );
			}

			// $giving_levels = dgx_donate_get_giving_levels();
			foreach ( $giving_levels_sanitized as $chosen_level ) {
				dgx_donate_enable_giving_level( $chosen_level );
				$none_enabled = false;
			}
		}
		// display error message
		if ( $none_enabled ) {
			seamless_donations_flag_cmb2_submit_button_error( 'dgx_donate_button_forms_giving_levels' );
		} else {
			seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_forms_giving_levels' );
		}
	}

	// Process Save Styles button
	if ( isset( $_POST['dgx_donate_button_forms_style'], $_POST['dgx_donate_button_forms_style_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_forms_style_nonce'], 'dgx_donate_button_forms_style' ) ) {
			wp_die( 'Security violation detected [A002]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_form_style'] ) ) {
			update_option( 'dgx_donate_form_style', sanitize_key( $_POST['dgx_donate_form_style'] ) );
			if ( $_POST['dgx_donate_form_style'] == 'classic' ) {
				// force form to generate label tag appropriately
				update_option( 'dgx_donate_labels_for_input', 1 );
				update_option( 'dgx_donate_stylesheet_priority', 1 );
			}
		}
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_forms_style' );
	}

	// Process Save Defaults button
	if ( isset( $_POST['dgx_donate_button_forms_defaults'], $_POST['dgx_donate_button_forms_defaults_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_forms_defaults_nonce'], 'dgx_donate_button_forms_defaults' ) ) {
			wp_die( 'Security violation detected [A003]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_currency'] ) ) {
			update_option( 'dgx_donate_currency', sanitize_text_field( $_POST['dgx_donate_currency'] ) );
		}
		$extend_currency = '';
		if ( isset( $_POST['dgx_donate_currency_extend'] ) ) {
			if ( sanitize_text_field( $_POST['dgx_donate_currency_extend'] ) == 'on' ) {
				$extend_currency = 'on';
			}
		}
		update_option( 'dgx_donate_currency_extend', $extend_currency );
		if ( isset( $_POST['dgx_donate_default_country'] ) ) {
			update_option( 'dgx_donate_default_country', sanitize_text_field( $_POST['dgx_donate_default_country'] ) );
		}
		if ( isset( $_POST['dgx_donate_default_state'] ) ) {
			update_option( 'dgx_donate_default_state', sanitize_text_field( $_POST['dgx_donate_default_state'] ) );
		}

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_forms_defaults' );
	}

	// Process Save Fields button
	if ( isset( $_POST['dgx_donate_button_forms_fields'], $_POST['dgx_donate_button_forms_fields_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_forms_fields_nonce'], 'dgx_donate_button_forms_fields' ) ) {
			wp_die( 'Security violation detected [A004]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		if ( isset( $_POST['dgx_donate_show_designated_funds_section'] ) ) {
			update_option( 'dgx_donate_show_designated_funds_section', sanitize_text_field( $_POST['dgx_donate_show_designated_funds_section'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_repeating_option'] ) ) {
			update_option( 'dgx_donate_show_repeating_option', sanitize_text_field( $_POST['dgx_donate_show_repeating_option'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_tribute_section'] ) ) {
			update_option( 'dgx_donate_show_tribute_section', sanitize_text_field( $_POST['dgx_donate_show_tribute_section'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_employer_section'] ) ) {
			update_option( 'dgx_donate_show_employer_section', sanitize_text_field( $_POST['dgx_donate_show_employer_section'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_donor_telephone_field'] ) ) {
			update_option( 'dgx_donate_show_donor_telephone_field', sanitize_text_field( $_POST['dgx_donate_show_donor_telephone_field'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_donor_employer_field'] ) ) {
			update_option( 'dgx_donate_show_donor_employer_field', sanitize_text_field( $_POST['dgx_donate_show_donor_employer_field'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_donor_occupation_field'] ) ) {
			update_option( 'dgx_donate_show_donor_occupation_field', sanitize_text_field( $_POST['dgx_donate_show_donor_occupation_field'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_mailing_list_option'] ) ) {
			update_option( 'dgx_donate_show_mailing_list_option', sanitize_text_field( $_POST['dgx_donate_show_mailing_list_option'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_anonymous_option'] ) ) {
			update_option( 'dgx_donate_show_anonymous_option', sanitize_text_field( $_POST['dgx_donate_show_anonymous_option'] ) );
		}
		if ( isset( $_POST['dgx_donate_show_donor_address_fields'] ) ) {
			update_option( 'dgx_donate_show_donor_address_fields', sanitize_text_field( $_POST['dgx_donate_show_donor_address_fields'] ) );
		}

		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_forms_fields' );
	}

	// Process Save Tweaks button
	if ( isset( $_POST['dgx_donate_button_forms_tweaks'], $_POST['dgx_donate_button_forms_tweaks_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['dgx_donate_button_forms_tweaks_nonce'], 'dgx_donate_button_forms_tweaks' ) ) {
			wp_die( 'Security violation detected [A005]. Access denied.', 'Security violation', array( 'response' => 403 ) );
		}
		// convert to legacy Seamless Donations 4.0 data format for continuity
		$labels_for_input    = '';
		$stylesheet_priority = '';
		if ( isset( $_POST['dgx_donate_labels_for_input'] ) ) {
			if ( strtolower( sanitize_text_field( $_POST['dgx_donate_labels_for_input'] ) ) == 'on' ) {
				$labels_for_input = '1';
			}
		}
		update_option( 'dgx_donate_labels_for_input', $labels_for_input );
		if ( isset( $_POST['dgx_donate_stylesheet_priority'] ) ) {
			if ( strtolower( sanitize_text_field( $_POST['dgx_donate_stylesheet_priority'] ) ) == 'on' ) {
				$stylesheet_priority = '1';
			}
		}
		update_option( 'dgx_donate_stylesheet_priority', $stylesheet_priority );
		seamless_donations_flag_cmb2_submit_button_success( 'dgx_donate_button_forms_tweaks' );
	}
}
