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

// From CMB2 Snippet Library
// https://github.com/CMB2/CMB2-Snippet-Library/edit/master/options-and-settings-pages/options-pages-with-tabs-and-submenus.php
/**
 * A CMB2 options-page display callback override which adds tab navigation among
 * CMB2 options pages which share this same display callback.
 *
 * @param CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
 */
function seamless_donations_cmb2_options_display_with_tabs( $cmb_options ) {
	$tabs = seamless_donations_cmb2_options_page_tabs( $cmb_options );
	?>
    <div class="wrap cmb2-options-page option-<?php echo esc_attr( $cmb_options->option_key ); ?>">
		<?php if ( get_admin_page_title() ) : ?>
            <h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
		<?php endif; ?>
        <h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
                <a class="nav-tab
				<?php
				// we don't need nonce verification here because all we're doing is checking to see
				// if we're on the page we expected to be on.
				// phpcs:ignore WordPress.Security.NonceVerification
				if ( isset( $_GET['page'] ) && $option_key == sanitize_key( $_GET['page'] ) ) :
					?>
					 nav-tab-active<?php endif; ?>"
                   href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
			<?php endforeach; ?>
        </h2>
        <form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
              id="<?php echo esc_attr( $cmb_options->cmb->cmb_id ); ?>" enctype="multipart/form-data"
              encoding="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
			<?php $cmb_options->options_page_metabox(); ?>
			<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
        </form>
    </div>
	<?php
}

/**
 * Gets navigation tabs array for CMB2 options pages which share the given
 * display_cb param.
 *
 * @param CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
 *
 * @return array Array of tab information.
 */
function seamless_donations_cmb2_options_page_tabs( $cmb_options ) {
	$tab_group = $cmb_options->cmb->prop( 'tab_group' );
	$tabs      = array();

	foreach ( CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
		if ( $tab_group == $cmb->prop( 'tab_group' ) ) {
			$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
				? $cmb->prop( 'tab_title' )
				: $cmb->prop( 'title' );
		}
	}

	return $tabs;
}

// set up filter to pre-load field values from Seamless Donations database
// from: https://github.com/CMB2/CMB2/wiki/Tips-&-Tricks#override-the-data-storage-location-for-a-cmb2-box
function seamless_donations_preload_cmb2_field_filter( $field_id, $handler_function_name ) {
	add_filter(
		'cmb2_override_' . $field_id . '_meta_value', // the filter
		$handler_function_name,
		10,
		4
	);
}

function seamless_donations_display_cmb2_submit_button( $section_options, $button_options ) {
	$section_options->add_field(
		array(
			'name'           => $button_options['button_text'],
			'id'             => $button_options['button_id'],
			'type'           => 'text',
			'attributes'     => array(
				'readonly' => 'readonly',
			),
			'button_options' => $button_options,
			'render_row_cb'  => 'seamless_donations_display_cmb2_submit_button_callback',
		)
	);
}

function seamless_donations_display_cmb2_submit_button_callback( $field_args, $field ) {
	// get button values
	$page_stub          = $field->object_id;
	$button_id          = $field->args['button_options']['button_id'];
	$button_text        = $field->args['button_options']['button_text'];
	$button_success_msg = $field->args['button_options']['button_success_msg'];
	$button_error_msg   = $field->args['button_options']['button_error_msg'];

	// debug code
	$button_list_option_name = 'dgx_donate_seamless_donations_tab_settings_button_list';
	$button_list_option      = get_option( $button_list_option_name );
	if ( $button_list_option == false ) {
		$button_list_array = array();
	} else {
		$button_list_array = unserialize( $button_list_option );
	}
	$foo = serialize( $button_list_array );

	// show error if option set
	$error_msg = seamless_donations_get_cmb2_submit_button_error_message( $button_id );
	if ( $error_msg != '' ) {
		echo '<div id="' . esc_attr( $button_id ) . '" class="notice notice-error">';
		echo esc_attr( $error_msg );
		echo '</div>';
	}

	// show message if option set
	$button_msg = seamless_donations_get_cmb2_submit_button_success_message( $button_id );
	if ( $button_msg != '' ) {
		echo '<div id="' . esc_attr( $button_id ) . '" class="notice notice-message">';
		echo esc_attr( $button_msg );
		echo '</div>';
	}

	// set up the fresh button message array for this admin page load
	$button_list_option_name = 'dgx_donate_' . $page_stub . '_button_list';
	$button_list_option      = get_option( $button_list_option_name );

	if ( $button_list_option == false ) {
		$button_list_array = array();
	} else {
		$button_list_array = unserialize( $button_list_option );
	}
	$button_id_success = $button_id . '_success';
	$button_id_error   = $button_id . '_error';

	// if ( count( $button_list_array ) == 0 ) {
	$button_list_array[ $button_id_success ] = $button_success_msg;
	$button_list_array[ $button_id_error ]   = $button_error_msg;
	// } else {
	// $button_list_array->$button_id_success = $button_success_msg;
	// $button_list_array->$button_id_error   = $button_error_msg;
	// }
	$button_list_option = serialize( $button_list_array );
	update_option( $button_list_option_name, $button_list_option );

	// create nonce code based on the ID of the button

	$nonce      = wp_create_nonce( $button_id );
	$nonce_name = $button_id . '_nonce';

	// display the button
	?>
    <div class="cmb-action-button-row">
        <p class="submit">
            <input type="hidden" id="<?php echo esc_attr( $nonce_name ); ?>"
                   name="<?php echo esc_attr( $nonce_name ); ?>"
                   value="<?php echo esc_attr( $nonce ); ?>"/>
            <input type="submit" name="<?php echo esc_attr( $button_id ); ?>"
                   id="<?php echo esc_attr( $button_id ); ?>"
                   class="button button-primary"
                   value="<?php echo esc_attr( $button_text ); ?>"></p>
    </div>
	<?php
}

function seamless_donations_flag_cmb2_submit_button_success( $button_id, $msg = '' ) {
	seamless_donations_set_cmb2_submit_button_flag( $button_id, '_success', $msg );
}

function seamless_donations_flag_cmb2_submit_button_error( $button_id, $msg = '' ) {
	seamless_donations_set_cmb2_submit_button_flag( $button_id, '_error', $msg );
}

function seamless_donations_set_cmb2_submit_button_flag( $button_id, $what_to_set, $msg = '' ) {
	// uses the default param so we can have two different functions with essentially the same code
	// it will be easier to read on the form settings pages
	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	$page_stub     = sanitize_key( $_POST['action'] );
	$button_to_set = $button_id . $what_to_set;

	$button_list_option_name = 'dgx_donate_' . $page_stub . '_button_list';
	$button_list_option      = get_option( $button_list_option_name );
	if ( $button_list_option != false ) {
		$button_list_array = unserialize( $button_list_option );

		if ( $msg == '' ) {
			if ( isset( $button_list_array[ $button_to_set ] ) ) {
				$message_to_set = $button_list_array[ $button_to_set ];
			} else {
				$message_to_set = '';
			}
		} else {
			$message_to_set = $msg;
		}

		unset( $button_list_array );
		$button_list_array                   = array();
		$button_list_array[ $button_to_set ] = $message_to_set;

		$button_list_option = serialize( $button_list_array );
		update_option( $button_list_option_name, $button_list_option );
	}
}

function seamless_donations_get_cmb2_submit_button_success_message( $button_id ) {
	return seamless_donations_get_cmb2_submit_button_message( $button_id, '_success' );
}

function seamless_donations_get_cmb2_submit_button_error_message( $button_id ) {
	return seamless_donations_get_cmb2_submit_button_message( $button_id, '_error' );
}

function seamless_donations_get_cmb2_submit_button_message( $button_id, $what_to_get ) {
	// uses the default param so we can have two different functions with essentially the same code
	// it will be easier to read on the form settings pages
	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_GET['page'] ) ) {
		$page_stub = sanitize_key( $_GET['page'] );

		$button_list_option_name = 'dgx_donate_' . $page_stub . '_button_list';
		$button_list_option      = get_option( $button_list_option_name );
		if ( $button_list_option == false ) {
			return '';
		} else {
			$button_list_array = unserialize( $button_list_option );
		}
		if ( isset( $button_list_array[ $button_id . $what_to_get ] ) ) {
			return sanitize_text_field( $button_list_array[ $button_id . $what_to_get ] );
		} else {
			return '';
		}
	} else {
		return '';
	}
}

function seamless_donations_clear_cmb2_submit_button_messages( $page_stub ) {
	if ( ! seamless_donations_is_referred_by_page( $page_stub ) ) {
		// clear previous error messages if coming from another page
		$button_list_option_name = 'dgx_donate_' . $page_stub . '_button_list';
		$button_list_array       = array();
		$button_list_option      = serialize( $button_list_array );
		update_option( $button_list_option_name, $button_list_option );
	}
}

// Adds custom action button to form
// $name is the the text displayed in the button
// $id is the unique id of the action button
function seamless_donations_cmb2_add_action_button( $section_options, $name, $id ) {
	$section_options->add_field(
		array(
			'name'          => esc_attr( $name ),
			'id'            => esc_attr( $id ),
			'type'          => 'text',
			'attributes'    => array(
				'readonly' => 'readonly',
			),
			'render_row_cb' => 'seamless_donations_cmb2_row_callback_for_action_button',
		)
	);
}

function seamless_donations_cmb2_row_callback_for_action_button( $field_args, $field ) {
	$button_name     = $field->args['name'];
	$button_id       = $field->args['id'];
	$button_error_id = $button_id . '_error';
	$button_msg_id   = $button_id . '_msg';

	// show error if option set
	$error_msg = get_option( $button_error_id );
	if ( $error_msg != false ) {
		if ( $error_msg != '' ) {
			echo '<div id="' . esc_attr( $button_error_id ) . '" class="notice notice-error">';
			echo esc_attr( $error_msg );
			echo '</div>';
		}
	}
	// show message if option set
	$button_msg = get_option( $button_msg_id );
	if ( $button_msg != false ) {
		if ( $button_msg != '' ) {
			echo '<div id="' . esc_attr( $button_msg_id ) . '" class="notice notice-message">';
			echo esc_attr( $error_msg );
			echo '</div>';
		}
	}

	// display the button
	?>
    <div class="cmb-action-button-row">
        <p class="submit">
            <input type="submit" name="<?php echo esc_attr( $button_id ); ?>"
                   id="<?php echo esc_attr( $button_id ); ?>"
                   class="button button-primary"
                   value="<?php echo esc_attr( $button_name ); ?>"></p>
    </div>
	<?php
}

// Adds a static description line to the form
// $name is the the text displayed in the button
// 'desc' passed as an argument is the static text description displayed
// $id is the unique id of the action button
function seamless_donations_cmb2_add_static_desc( $section_options, $desc, $id ) {
	$section_options->add_field(
		array(
			'desc'          => $desc,
			'id'            => esc_attr( $id ),
			'type'          => 'text',
			'attributes'    => array(
				'readonly' => 'readonly',
			),
			'render_row_cb' => 'seamless_donations_cmb2_row_callback_for_static_desc',
		)
	);
}

function seamless_donations_cmb2_row_callback_for_static_desc( $field_args, $field ) {
	$desc = $field->args['desc'];
	$id   = $field->args['id'];

	?>

    <div class="cmb-static-desc-row" id="<?php echo esc_attr( $id ); ?>">
        <div class="cmb-td">
            <p class="cmb2-metabox-description"><?php

				$allowed_html = array(
					'a'      => array(
						'href'   => array(),
						'title'  => array(),
						'class'  => array(),
						'id'     => array(),
						'style'  => array(),
						'target' => array(),
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
					'img'     => array(
						'src' => array(),
                    ),
				);
				$desc         = wp_kses( $desc, $allowed_html );

				echo $desc; ?></p>
        </div>
    </div>

	<?php
}




