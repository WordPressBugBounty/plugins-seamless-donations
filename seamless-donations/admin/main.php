<?php
/**
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

add_action( 'cmb2_admin_init', 'seamless_donations_admin_main_menu' );

// MAIN - MENU ////
function seamless_donations_admin_main_menu() {
	$args = array(
		'id'           => 'seamless_donations_tab_main_page',
		'title'        => 'Seamless Donations',
		// page title
		'menu_title'   => 'Seamless Donations',
		// title on left sidebar
		'tab_title'    => 'Seamless Donations',
		// title displayed on the tab
		'object_types' => array( 'options-page' ),
		'option_key'   => 'seamless_donations_tab_main',
		'tab_group'    => 'seamless_donations_tab_set',
		'icon_url'     => 'dashicons-palmtree',
	);

	// 'tab_group' property is supported in > 2.4.0.
	if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
		$args['display_cb'] = 'seamless_donations_cmb2_options_display_with_tabs';
	}

	do_action( 'seamless_donations_tab_main_before', $args );

	// call on button hit for page save
	add_action( 'admin_post_seamless_donations_tab_main', 'seamless_donations_tab_main_process_buttons' );

	$args         = apply_filters( 'seamless_donations_tab_main_menu', $args );
	$main_options = new_cmb2_box( $args );
	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'seamless_donations_tab_main' ) {
		$main_options = seamless_donations_admin_give_banner($main_options);
		seamless_donations_admin_main_section_data( $main_options );

		do_action( 'seamless_donations_tab_main_after', $main_options );
	}
}

// Remove primary Save button
// derived from https://github.com/CMB2/CMB2-Snippet-Library/blob/master/filters-and-actions/custom-css-for-specific-metabox.php
function seamless_donations_delete_welcome_button( $post_id, $cmb ) {
	?>
    <style type="text/css" media="screen">
        input#submit-cmb.button.button-primary {
            display : none;
        }
    </style>
	<?php
}

$object = 'options-page'; // could also be post | term
$cmb_id = 'seamless_donations_tab_main_page';
add_action( "cmb2_after_{$object}_form_{$cmb_id}", 'seamless_donations_delete_welcome_button', 10, 2 );

// MAIN - SECTION - DATA ////
function seamless_donations_admin_main_section_data( $section_options ) {
	$section_options = apply_filters( 'seamless_donations_tab_main_section_data', $section_options );

	$section_options->add_field(
		array(
			'name'          => 'Welcome to Seamless Donations',
			'id'            => 'seamless_donations_welcome_area',
			'type'          => 'text',
			'savetxt'       => '',
			'render_row_cb' => 'seamless_donations_render_main_tab_html',
			// this builds static text as provided
		)
	);
	$section_options = apply_filters( 'seamless_donations_tab_main_section_data_options', $section_options );
}

function seamless_donations_render_main_tab_html( $field_args, $field ) {
	$html_folder = dirname( dirname( __FILE__ ) ) . '/html/';
	$html_file   = $html_folder . 'admin-main.html';
	$html_readme = file_get_contents( $html_file );

	$html_readme = apply_filters( 'seamless_donations_admin_main_section_data_options', $html_readme );

	$allowed_html = array(
		'a'      => array(
			'href'  => array(),
			'title' => array(),
			'class' => array(),
		),
		'div'    => array(
			'id'    => array(),
			'class' => array(),
		),
		'form'   => array(
			'action' => array(),
			'method' => array(),
			'id'     => array(),
			'name'   => array(),
			'class'  => array(),
			'target' => array(),
		),
		'h3'     => array(),
		'h4'     => array(),
		'iframe' => array(
			'width'           => array(),
			'height'          => array(),
			'src'             => array(),
			'frameborder'     => array(),
			'allowfullscreen' => array(),
		),
		'img'    => array(
			'src' => array(),
		),
		'input'  => array(
			'type'     => array(),
			'value'    => array(),
			'name'     => array(),
			'class`'   => array(),
			'id'       => array(),
			'tabindex' => array(),
		),
		'label'  => array(
			'for'   => array(),
			'class' => array(),
		),
        'li'=> array(),
		'link'   => array(
			'href' => array(),
			'rel'  => array(),
			'type' => array(),
		),
		'p'      => array(),
		'script' => array(
			'type' => array(),
			'src'  => array(),
		),
		'span'   => array(
			'class' => array(),
		),
		'strong' => array(),
		'style'  => array(
			'type' => array(),
		),
        'ul'=>array(),
	);
	echo wp_kses( $html_readme, $allowed_html );
}

// ADDONS - PROCESS FORM SUBMISSIONS
function seamless_donations_tab_main_process_buttons() {
	// Process Save changes button
	// This is a callback that has to be passed the full array for consideration
	// phpcs:ignore WordPress.Security.NonceVerification
	$_POST = apply_filters( 'validate_page_slug_seamless_donations_tab_main', $_POST );
}
