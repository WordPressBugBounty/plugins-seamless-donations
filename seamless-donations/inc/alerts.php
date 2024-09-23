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

function seamless_donations_admin_give_banner($section_options) {
	$give_banner = plugins_url('/images/go-to-give.png', dirname(__FILE__));
	$give_notice = '<A HREF="https://zatzlabs.com/seamless-donations-must-read/">';
	$give_notice .= '<IMG SRC="' . $give_banner . '">';
	$give_notice .= '</A>';
	seamless_donations_cmb2_add_static_desc( $section_options, $give_notice, 'give_notice' );
	return $section_options;
}


function seamless_donations_admin_debug_mode_msg() {
	echo '<div class="error">';
	echo '<p>';
	echo esc_html__( 'Warning - Seamless Donations is currently in debug mode (security may be compromised). ' .
	                 'Turn off in Seamless Donations -> Settings -> Debug Mode.' );
	echo '</p>';
	echo '</div>';
}

function my_plugin_notice_dismissed() {
    $user_id = get_current_user_id();
	$var = !empty($_GET['sunset-dismissed'])?$_GET['sunset-dismissed']:'sunset-dismissed';
    if ( isset( $var ) && $var == 'true') {
        //add_user_meta( $user_id, 'sunset-dismissed', 'true', true );
		update_user_meta( $user_id, 'sunset-dismissed', 'true' );
	}
}
add_action( 'admin_init', 'my_plugin_notice_dismissed' );

function seamless_donations_admin_sunset_msg() {
	$user_id = get_current_user_id();
	$dismissed = get_user_meta( $user_id, 'sunset-dismissed', true );
    if ( isset($dismissed) && $dismissed == 'true' ) {

	} else {
	?>
	<style>
		@import url('https://fonts.cdnfonts.com/css/pacifico');
		@import url('https://fonts.cdnfonts.com/css/montserrat');
	</style>
	<div class="" style="position: relative; background-color: white; padding: 2rem; margin: 1rem 3rem 0 0; border-radius: 10px; -webkit-box-shadow: 12px 12px 36px 0 rgba(100,100,100,0.15); box-shadow: 12px 12px 36px 0 rgba(100,100,100,0.15);">
		<h3 style="font-size: 250%;"><span style="font-family: Pacifico, cursive;">Seamless Donations</span> is being sunset. Meet <span style="font-family: Montserrat, sans-serif; font-weight: 700; color: #62B265">GiveWP</span>!</h3>
		<p style="margin: 10px 0; font-size: 150%;">Keep fundraising for free without interruption and add Recurring Donations for $1 for your first year.</p>
		<a href="https://zatzlabs.com/seamless-donations-must-read/" class="button-primary" target="_blank" rel="noopener" or rel="noreferrer" style="padding: 0.6em 1.8em; border-radius: 3em;">Learn More Here</a></p>
		<a href="?sunset-dismissed=true" style="position: absolute; right: 1em; top: 1em; text-decoration: underline;">Dismiss</a>
	</div>
	<?php 
	}
}

function seamless_donations_admin_new_support_msg() {
	$new_support_url = '<A href="http://zatzlabs.com/forums/">Seamless Donations Community Forums</A>. ';
	$ticket_url      = '<A href="http://zatzlabs.com/submit-ticket/">open a ticket</A>.';

	echo '<div class="error">';
	echo '<p>';
	echo esc_html__(
		'Notice - Seamless Donations support is no ' .
		'longer provided on the WordPress.org forums. Please visit the new ' );
	echo esc_url( $new_support_url );
	echo esc_html__( 'If you need a timely reply from the developer, please ', 'seamless-donations' );
	echo esc_url( $ticket_url );
	echo '</p>';
	echo '</div>';
}

function dgx_donate_admin_sandbox_msg() {
	echo '<div class="error">';
	echo '<p>';
	echo esc_html__(
		'Warning - Seamless Donations is currently configured to use the sandbox test server.',
		'seamless-donations'
	);
	echo '</p>';
	echo '</div>';
}

function seamless_donations_5000_disabled_addon_message() {
	$pre_5_licenses = get_option( 'dgx_donate_5000_deactivated_addons' );

	$ood_msg = " If you're getting this message, please delete the add-on's folder";
	$ood_msg .= ' from the wp-content/plugins folder on the server. You can then';
	$ood_msg .= " use WordPress's Add New plugin feature to upload a compatible version.";
	$ood_msg .= ' If you need to download the latest version of the add-on, go to ';
	$ood_msg .= '<A HREF="https://zatzlabs.com/account/">your account page</A>,';
	$ood_msg .= ' click on View Details and Downloads from your Purchase History,';
	$ood_msg .= " and at the bottom of the page, you'll see a link to the 2.0 version";
	$ood_msg .= ' of the add-on. Download that and install it on your site.<br><br>';
	$ood_msg .= 'If you run into any snags at all, <A HREF="https://zatzlabs.com/submit-ticket/">open a ticket</A>.';

	$section_desc = 'Warning - The following Seamless Donations add-ons are incompatible with this version of Seamless Donations and have been disabled: ';
	$section_desc .= $pre_5_licenses;
	$section_desc .= '.<br><br>You will need to upgrade these add-ons before you can use them again.' . $ood_msg;
	echo '<div class="error">';
	echo '<p>';
	echo wp_kses_post( $section_desc );
	echo '</p>';
	echo '</div>';
}

// tell users that there is a new version and that they need to update
function seamless_donations_sd40_update_alert_message() {
	// we don't need nonce verification here because all we're doing is checking to see
	// if we're on the page we expected to be on.
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] != 'dgx_donate_menu_page' ) {
		$url = sanitize_url( get_admin_url() . 'admin.php?page=dgx_donate_menu_page' );
		echo '<div class="notice-alt">';
		echo '<p>';
		echo esc_html__(
			'Alert - Seamless Donations has had a major update. ',
			'seamless-donations'
		);
		echo '<A HREF="' . esc_url($url) . '">Click here</A> ';
		echo esc_html__(
			'to learn about enabling your new features ',
			'seamless-donations'
		);
		echo esc_html__(
			'(they will remain off until you manually enable them).',
			'seamless-donations'
		);
		echo '</p>';
		echo '</div>';
	}
}
