<?php
/*
Seamless Donations by David Gewirtz, adopted from Allen Snook

Lab Notes: http://zatzlabs.com/lab-notes/
Plugin Page: http://zatzlabs.com/seamless-donations/
Contact: http://zatzlabs.com/contact-us/

Copyright (c) 2015-2022 by David Gewirtz
*/

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// for 5.0 conversion

function seamless_donations_addon_legacy_addons_still_loaded() {
	$bwp = 'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php';
	$dlm = 'seamless-donations-delete-monster/seamless-donations-delete-monster.php';
	$glm = 'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php';
	$tye = 'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php';

	// this could be an array and a loop, but it's not
	$plugins = get_plugins();
	if ( isset( $plugins[ $bwp ] ) ) {
		if ( substr( $plugins[ $bwp ]['Version'], 0, 1 ) == '1' ) {
			return true;
		}
	}
	if ( isset( $plugins[ $dlm ] ) ) {
		if ( substr( $plugins[ $dlm ]['Version'], 0, 1 ) == '1' ) {
			return true;
		}
	}
	if ( isset( $plugins[ $glm ] ) ) {
		if ( substr( $plugins[ $glm ]['Version'], 0, 1 ) == '1' ) {
			return true;
		}
	}
	if ( isset( $plugins[ $tye ] ) ) {
		if ( substr( $plugins[ $tye ]['Version'], 0, 1 ) == '1' ) {
			return true;
		}
	}

	return false;
}

function seamless_donations_sd4_plugin_load_check() {
	$skip_addon_check = get_option( 'dgx_donate_legacy_addon_check' );
	if ( $skip_addon_check != 'on' ) {
		// deactivate legacy plugins on site load
		$bwp = 'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php';
		$dlm = 'seamless-donations-delete-monster/seamless-donations-delete-monster.php';
		$glm = 'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php';
		$tye = 'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php';

		// this could be an array and a loop, but it's not
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		if ( isset( $plugins[ $bwp ] ) ) {
			if ( substr( $plugins[ $bwp ]['Version'], 0, 1 ) == '1' ) {
				deactivate_plugins( $bwp );
				flush_rewrite_rules();
				remove_filter(
					'seamless_donations_admin_licenses_section_registration_options',
					'seamless_donations_bwp_admin_licenses_section_registration_options'
				);
			}
		}
		if ( isset( $plugins[ $dlm ] ) ) {
			if ( substr( $plugins[ $dlm ]['Version'], 0, 1 ) == '1' ) {
				deactivate_plugins( $dlm );
				flush_rewrite_rules();
				remove_filter(
					'seamless_donations_admin_licenses_section_registration_options',
					'seamless_donations_dm_admin_licenses_section_registration_options'
				);
			}
		}
		if ( isset( $plugins[ $glm ] ) ) {
			if ( substr( $plugins[ $glm ]['Version'], 0, 1 ) == '1' ) {
				deactivate_plugins( $glm );
				flush_rewrite_rules();
				remove_filter(
					'seamless_donations_admin_licenses_section_registration_options',
					'seamless_donations_glm_admin_licenses_section_registration_options'
				);
			}
		}
		if ( isset( $plugins[ $tye ] ) ) {
			if ( substr( $plugins[ $tye ]['Version'], 0, 1 ) == '1' ) {
				deactivate_plugins( $tye );
				flush_rewrite_rules();
				remove_filter(
					'seamless_donations_admin_licenses_section_registration_options',
					'seamless_donations_tye_admin_licenses_section_registration_options'
				);
			}
		}
	}
}

function seamless_donations_sd4_plugin_filter_remove() {
	// deactivate legacy plugins on site load
	$bwp = 'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php';
	$dlm = 'seamless-donations-delete-monster/seamless-donations-delete-monster.php';
	$glm = 'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php';
	$tye = 'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php';

	// this could be an array and a loop, but it's not
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugins = get_plugins();
	if ( isset( $plugins[ $bwp ] ) ) {
		if ( substr( $plugins[ $bwp ]['Version'], 0, 1 ) == '1' ) {
			remove_filter(
				'seamless_donations_admin_licenses_section_registration_options',
				'seamless_donations_bwp_admin_licenses_section_registration_options'
			);
		}
	}
	if ( isset( $plugins[ $dlm ] ) ) {
		if ( substr( $plugins[ $dlm ]['Version'], 0, 1 ) == '1' ) {
			remove_filter(
				'seamless_donations_admin_licenses_section_registration_options',
				'seamless_donations_dm_admin_licenses_section_registration_options'
			);
		}
	}
	if ( isset( $plugins[ $glm ] ) ) {
		if ( substr( $plugins[ $glm ]['Version'], 0, 1 ) == '1' ) {
			remove_filter(
				'seamless_donations_admin_licenses_section_registration_options',
				'seamless_donations_glm_admin_licenses_section_registration_options'
			);
		}
	}
	if ( isset( $plugins[ $tye ] ) ) {
		if ( substr( $plugins[ $tye ]['Version'], 0, 1 ) == '1' ) {
			remove_filter(
				'seamless_donations_admin_licenses_section_registration_options',
				'seamless_donations_tye_admin_licenses_section_registration_options'
			);
			remove_filter(
				'seamless_donations_admin_thanks_section_note',
				'seamless_donations_tye_admin_thanks_section_note'
			);
		}
	}
}

function seamless_donations_sd4_plugin_reactivate_check() {
	// exit legacy plugins when user attempts to reactivate
	$bwp = 'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php';
	$dlm = 'seamless-donations-delete-monster/seamless-donations-delete-monster.php';
	$glm = 'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php';
	$tye = 'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php';

	$bwp_msg = 'The Seamless Donations Basic Widget Pack add-on';
	$dlm_msg = 'The Seamless Donations Delete Monster add-on';
	$glm_msg = 'The Seamless Donations Giving Level Manager add-on';
	$tye_msg = 'The Seamless Donations Thank You Enhanced add-on';

	$ood_msg  = ' is incompatible with the new 5.0 version of Seamless Donations.';
	$ood_msg .= " If you're getting this message, please delete the add-on's folder";
	$ood_msg .= ' from the wp-content/plugins folder on the server. You can then';
	$ood_msg .= " use WordPress's Add New plugin feature to upload a compatible version.<br><br>";
	$ood_msg .= 'If you need to download the latest version of the add-on, go to ';
	$ood_msg .= '<A HREF="https://zatzlabs.com/account/">your account page</A>,';
	$ood_msg .= ' click on View Details and Downloads from your Purchase History,';
	$ood_msg .= " and at the bottom of the page, you'll see a link to the 2.0 version";
	$ood_msg .= ' of the add-on. Download that and install it on your site.<br><br>';
	$ood_msg .= 'If you run into any snags at all, <A HREF="https://zatzlabs.com/submit-ticket/">open a ticket</A>.';

	// todo look for activation by hand of legacy plugin and stop it
	// this could be an array and a loop, but it's not
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugins = get_plugins();
	if ( isset( $plugins[ $bwp ] ) ) {
		if ( substr( $plugins[ $bwp ]['Version'], 1, 1 ) == '1' ) {
			exit( wp_kses_post( $bwp_msg . $ood_msg) );
		}
	}
	if ( isset( $plugins[ $dlm ] ) ) {
		if ( substr( $plugins[ $dlm ]['Version'], 0, 1 ) == '1' ) {
			exit( wp_kses_post( $bwp_msg . $ood_msg ));
		}
	}
	if ( isset( $plugins[ $glm ] ) ) {
		if ( substr( $plugins[ $glm ]['Version'], 0, 1 ) == '1' ) {
			exit( wp_kses_post($bwp_msg . $ood_msg ));
		}
	}
	if ( isset( $plugins[ $tye ] ) ) {
		if ( substr( $plugins[ $tye ]['Version'], 0, 1 ) == '1' ) {
			exit( wp_kses_post($bwp_msg . $ood_msg) );
		}
	}
}

function seamless_donations_sd4_plugin_reactivation_check() {
	$check_dir_bwp = WP_CONTENT_DIR . '/plugins/' . 'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php';
	$check_dir_dlm = WP_CONTENT_DIR . '/plugins/' . 'seamless-donations-delete-monster/seamless-donations-delete-monster.php';
	$check_dir_glm = WP_CONTENT_DIR . '/plugins/' . 'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php';
	$check_dir_tye = WP_CONTENT_DIR . '/plugins/' . 'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php';

	register_deactivation_hook( $check_dir_bwp, 'seamless_donations_sd4_plugin_reactivate_check' );
	register_deactivation_hook( $check_dir_dlm, 'seamless_donations_sd4_plugin_reactivate_check' );
	register_deactivation_hook( $check_dir_glm, 'seamless_donations_sd4_plugin_reactivate_check' );
	register_deactivation_hook( $check_dir_tye, 'seamless_donations_sd4_plugin_reactivate_check' );
}

function seamless_donations_sd5004_debug_mode_update() {
	$mode = get_option( 'dgx_donate_debug_mode' );
	if ( $mode == 1 ) {
		update_option( 'dgx_donate_debug_mode', 'VERBOSE' );
	}
}

// for 5.0.21 clean up stripe invoice data from earlier release
function seamless_donations_sd5021_stripe_invoices() {
	// prior to 5.0.21, didn't properly record Stripe repeating donation transaction ids
	seamless_donations_stripe_sd_5021_fix_uninvoiced_donation_subscriptions();
}

function seamless_donations_5000_check_addons() {
	$skip_addon_check = get_option( 'dgx_donate_legacy_addon_check' );
	if ( $skip_addon_check != 'on' ) {
		// this disables all pre-5.0 addons because they're wildly incompatible with this new build
		// dgx_donate_debug_log("Performing add-on update check");
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// there are four pre-5.0 addons that must be disabled
		$plugin_list = array(
			'seamless-donations-basic-widget-pack/seamless-donations-basic-widget-pack.php',
			'seamless-donations-delete-monster/seamless-donations-delete-monster.php',
			'seamless-donations-giving-level-manager/seamless-donations-giving-level-manager.php',
			'seamless-donations-thankyou-enhanced/seamless-donations-thankyou-enhanced.php',
		);

		$version_constants = array(
			'SEAMLESS_DONATIONS_BWP_CURRENT_VERSION',
			'SEAMLESS_DONATIONS_DM_CURRENT_VERSION',
			'SEAMLESS_DONATIONS_GLM_CURRENT_VERSION',
			'SEAMLESS_DONATIONS_TYE_CURRENT_VERSION',
		);

		$addon_names = array(
			'Basic Widget Pack',
			'Delete Monster',
			'Giving Level Manager',
			'Thank You Enhanced',
		);

		$deactivation_list = '';

		if ( function_exists( 'is_plugin_active' ) ) {
			if ( function_exists( 'deactivate_plugins' ) ) {
				for ( $i = 0; $i < count( $plugin_list ); $i ++ ) {
					$plugin  = $plugin_list[ $i ];
					$version = $version_constants[ $i ];
					$addon   = $addon_names[ $i ];

					if ( is_plugin_active( $plugin ) ) {
						if ( version_compare( constant( $version ), '2.0.0', '<' ) ) {
							// remove incompatible addon and update deactivation list
							deactivate_plugins( $plugin );
							if ( $deactivation_list != '' ) {
								$deactivation_list .= ', ';
							}
							$deactivation_list .= $addon;
							update_option( 'dgx_donate_5000_deactivated_addons', $deactivation_list );
						} else {
							// update deactivation list for any addons now current
							$unset_index        = false;
							$deactivation_list  = get_option( 'dgx_donate_5000_deactivated_addons' );
							$deactivation_array = explode( ',', $deactivation_list );
							for ( $j = 0; $j < count( $deactivation_array ); $j ++ ) {
								$deactivation_test = $deactivation_array[ $j ];
								if ( strcmp( trim( $deactivation_test ), trim( $addon ) ) == 0 ) {
									$unset_index = $j;
								}
							}
							if ( $unset_index !== false ) {
								$deactivation_array = seamless_donations_force_unset_array_by_index( $deactivation_array, $unset_index );
							}
							$deactivation_list = implode( ', ', $deactivation_array );
							update_option( 'dgx_donate_5000_deactivated_addons', $deactivation_list );
						}
					}
				}
			}
		}
	}
}

// for 5.1.15 clean up sandbox settings for PayPal

function seamless_donations_sd5107_update() {
	$indexes_updated = get_option( 'dgx_donate_5107_update' );

	// dgx_donate_paypal_email

	if ( ! $indexes_updated ) {
		// split PayPal email addresses into live and sandbox
		$paypal_email = get_option( 'dgx_donate_paypal_email' );
		$paypal_mode  = get_option( 'dgx_donate_paypal_server' );
		if ( $paypal_email == false ) {
			$paypal_email = '';
		}
		if ( $paypal_mode == false ) {
			$paypal_mode = 'SANDBOX';
		}
		if ( $paypal_mode == 'LIVE' ) {
			update_option( 'dgx_donate_paypal_email_live', $paypal_email );
		} else {
			update_option( 'dgx_donate_paypal_email_sandbox', $paypal_email );
		}

		// update the audit table with email cross-references
		global $wpdb;
		$table_name = $wpdb->prefix . 'seamless_donations_audit';

		// build email cross reference index into audit table
		dgx_donate_debug_log( 'Preparing to processing sd5107 version update.' );
		// $query   = "select * from $table_name where option_name like 'SDS01-%' and (option_value like '%testy@test.com%')";
		$query = "select * from $table_name where option_name like 'SDS01-%'";
		$results = $wpdb->get_results( $query );
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $row ) {
				// each column in your row will be accessible like this
				$row_id           = $row->option_id;
				$row_session_id   = $row->option_name;
				$row_session_data = $row->option_value;
				$session_array    = maybe_unserialize( $row_session_data );
				if ( isset( $session_array['EMAIL'] ) ) {
					$donor_email = $session_array['EMAIL'];
					seamless_donations_update_audit_email( $donor_email, $row_session_id );
				}
			}
		}

		// updates are completed
		update_option( 'dgx_donate_5107_update', true );
		dgx_donate_debug_log( 'Completed processing sd5107 version update.' );
	}
}

// manage the legacy import from Allen's original code and any format conversions for new data structures

// for 4.0.12 convert funds to ids
function seamless_donations_4012_update_indexes() {
	// prior to 4.0.12, donation records did not save fund ids
	// (even though it could have been a one-line fix, argh!)

	$indexes_updated = get_option( 'dgx_donate_4012_indexes_updated' );

	if ( ! $indexes_updated ) {
		seamless_donations_rebuild_funds_index();
		seamless_donations_rebuild_donor_index();

		$plugin_version = 'sd4012';
		update_option( 'dgx_donate_4012_indexes_updated', $plugin_version );
	}
}

// for 4.0.13 add anonymous flag to donors
function seamless_donations_4013_update_anon() {
	// prior to 4.0.13, donor records did not save anonymous requests
	// now, if any donation requests anonymity, the donor is marked anon

	$anon_updated = get_option( 'dgx_donate_4013_anon_updated' );

	if ( ! $anon_updated ) {
		seamless_donations_rebuild_donor_anon_flag();

		$plugin_version = 'sd4013';
		update_option( 'dgx_donate_4013_anon_updated', $plugin_version );
	}
}

function seamless_donations_sd40_upgrade_form() {
	$url = get_admin_url() . 'admin.php?page=seamless_donations_admin_main';
	?>
	<div class="error below-h2">
		<h1><strong>Welcome to Seamless Donations 4.0</strong></h1>

		<h2>This update modifies your data and donation form layout. It has the potential to cause breakage.</h2>

		<h2>Be sure to <span style="background: red; color: white;">backup and test on a staging server</span>
			before updating your live server.</h2>

		<P>In March 2015, Seamless Donations was adopted by David Gewirtz. Since then, David has been working hard
			to
			bring you great new features and prepare Seamless Donations for a future with lots of new capabilities
			that
			will help you raise funds and make the world a better place. If you'd like to learn more about the new
			features or dive deep into the development process, feel free to read David's <A HREF="">Seamless
				Donations
				Lab Notes</A>.</P>

		<H3><STRONG>Here are some of the new features you'll find in 4.0:</STRONG></H3>
		<UL>
			<LI><B>Updated, modern admin UI:</B> The admin interface has been updated to a modern tabbed-look.</LI>
			<LI><B>Custom post types:</B> Funds and donors have now been implemented as custom post types. This
				gives
				you the ability to use all of WordPress' post management and display tools with donors and funds.
				The
				donation data has always been a custom post type, but it is now also available to manipulate using
				plugins and themes outside of Seamless Donations.
			</LI>
			<LI><B>Designed for extensibility:</B> The primary design goal of 4.0 was to add hooks in the form of
				filters and actions that web designers can use to modify the behavior of Seamless Donations to fit
				individual needs. The plugin was re-architected to allow for loads of extensibility.
			</LI>
			<LI><B>Forms engine designed for extensibility:</B> Rather than just basic form code, Seamless Donations
				4.0
				now has a brand-new array-driven forms engine, which will give web site builders the ability to
				modify
				and access
				every part of the form before it is displayed to donors.
			</LI>
			<LI><B>Admin UI designed for extensibility:</B> Yep, like everything else, the admin interface has been
				designed to allow for extensibility.
			</LI>
			<LI><B>Translation-ready:</B> Seamless Donations 4.0 has had numerous tweaks to allow it to be
				translated
				into other languages.
			</LI>
		</UL>
		<h3><strong>Be sure to implement these changes and test</strong></h3>
		<UL>
			<LI><B>Change the form shortcode:</B> The [dgx-donate] shortcode is deprecated and will issue an update
				warning once you update. The new shortcode is [seamless-donations].
			</LI>
			<LI><B>Check your CSS:</B> Most of the CSS should remain the same, but because the form interaction has
				been
				updated, your CSS may change.
			</LI>
			<LI><B>Check your data:</B> Great pains have been taken to be sure the data migrates correctly, but
				please,
				please, PLEASE double-check it.
			</LI>
		</UL>
		<h3><strong>Please watch this "what to look for" video before you begin testing the beta release</strong>
		</h3>
		<iframe width="640" height="360" src="https://www.youtube.com/embed/SWm6GivlJi0?rel=0" frameborder="0"
				allowfullscreen></iframe>
		<br><br>

		<form method="post" action="<?php echo esc_attr($url); ?>">
			<?php wp_nonce_field( 'upgrade_seamless_donations_sd40' ); ?>
			<input type="hidden" name="upgrade" value="sd40"/>
			<input type="submit" class="button button-primary"
				   value="I have made a backup. Let's do this upgrade!"/>
		</form>
		<p></p>
	</div>
	<?php
}

// ********************** FUNDS LEGACY DATA MANAGEMENT *******************************

function seamless_donations_funds_was_legacy_imported() {
	$funds_imported = get_option( 'dgx_donate_designated_funds_legacy_import' );

	if ( ! $funds_imported ) {
		return false;
	} else {
		return true;
	}
}

function seamless_donations_funds_legacy_import() {
	$funds_imported = get_option( 'dgx_donate_designated_funds_legacy_import' );

	if ( ! $funds_imported ) {
		$fund_array = get_option( 'dgx_donate_designated_funds' );
		while ( $fund_option = current( $fund_array ) ) {
			// initialize import of legacy data
			if ( $fund_option == 'SHOW' ) {
				$meta_value = 'Yes';
			} else {
				$meta_value = 'No';
			}
			$fund_name = key( $fund_array );
			$fund_name = sanitize_text_field( $fund_name );

			// create the new custom fund post
			$post_array = array(
				'post_title'   => $fund_name,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'funds',
			);

			$post_id = wp_insert_post( $post_array, true );

			// update the option
			if ( $post_id != 0 ) {
				update_post_meta( $post_id, '_dgx_donate_fund_show', $meta_value );
			}

			next( $fund_array );
		}
		// write out the legacy import completed flag
		$plugin_version = 'sd40';
		update_option( 'dgx_donate_designated_funds_legacy_import', $plugin_version );
	}
}

// ********************** DONATIONS LEGACY DATA MANAGEMENT *******************************

function seamless_donations_donations_was_legacy_imported() {
	$donations_imported = get_option( 'dgx_donate_donations_legacy_import' );

	if ( ! $donations_imported ) {
		return false;
	} else {
		return true;
	}
}

function seamless_donations_donations_legacy_import() {
	$donations_imported = get_option( 'dgx_donate_donations_legacy_import' );

	if ( ! $donations_imported ) {
		// we need to convert the posts of type dgx-donation to type donation (the hyphen doesn't
		// work for some of the UI elements

		$args = array(
			'post_type'      => 'dgx-donation',
			'posts_per_page' => - 1,
		);

		$donation_array = get_posts( $args );

		while ( $donation_option = current( $donation_array ) ) {
			set_post_type( $donation_option->ID, 'donation' );

			next( $donation_array );
		}

		// write out the legacy import completed flag
		$plugin_version = 'sd40';
		update_option( '_dgx_donate_donations_legacy_import', $plugin_version );
	}
}

// ********************** DONORS LEGACY DATA MANAGEMENT *******************************

function seamless_donations_donors_was_legacy_imported() {
	$donors_imported = get_option( 'dgx_donate_donors_legacy_import' );

	if ( ! $donors_imported ) {
		return false;
	} else {
		return true;
	}
}

function seamless_donations_donors_legacy_import() {
	$donors_imported = get_option( 'dgx_donate_donors_legacy_import' );

	if ( ! $donors_imported ) {
		// we need to scan the donations list (after converting to type seamless_donation)
		// and build up a list of donors from the donation list
		// *** DO NOT RUN UNTIL AFTER THE DONATION IMPORT ***

		$donor_array = array();

		// gather data from donations custom post type into $donor_array
		$args = array(
			'post_type'      => 'donation',
			'posts_per_page' => - 1,
		);

		$donation_array = get_posts( $args );
		while ( $donation_option = current( $donation_array ) ) {
			$donation_id = $donation_option->ID;

			$first      = get_post_meta( $donation_id, '_dgx_donate_donor_first_name', true );
			$last       = get_post_meta( $donation_id, '_dgx_donate_donor_last_name', true );
			$email      = get_post_meta( $donation_id, '_dgx_donate_donor_email', true );
			$employer   = get_post_meta( $donation_id, '_dgx_donate_employer_name', true );
			$occupation = get_post_meta( $donation_id, '_dgx_donate_occupation', true );
			$currency   = get_post_meta( $donation_id, '_dgx_donate_donation_currency', true );

			$phone    = get_post_meta( $donation_id, '_dgx_donate_donor_phone', true );
			$address  = get_post_meta( $donation_id, '_dgx_donate_donor_address', true );
			$address2 = get_post_meta( $donation_id, '_dgx_donate_donor_address2', true );
			$city     = get_post_meta( $donation_id, '_dgx_donate_donor_city', true );
			$state    = get_post_meta( $donation_id, '_dgx_donate_donor_state', true );
			$province = get_post_meta( $donation_id, '_dgx_donate_donor_province', true );
			$country  = get_post_meta( $donation_id, '_dgx_donate_donor_country', true );
			$zip      = get_post_meta( $donation_id, '_dgx_donate_donor_zip', true );

			$name                               = $first . ' ' . $last;
			$donor_array[ $name ]['first']      = $first;
			$donor_array[ $name ]['last']       = $last;
			$donor_array[ $name ]['email']      = $email;
			$donor_array[ $name ]['employer']   = $employer;
			$donor_array[ $name ]['occupation'] = $occupation;
			$donor_array[ $name ]['currency']   = $currency;

			$donor_array[ $name ]['phone']    = $phone;
			$donor_array[ $name ]['address']  = $address;
			$donor_array[ $name ]['address2'] = $address2;
			$donor_array[ $name ]['city']     = $city;
			$donor_array[ $name ]['state']    = $state;
			$donor_array[ $name ]['province'] = $province;
			$donor_array[ $name ]['country']  = $country;
			$donor_array[ $name ]['zip']      = $zip;

			// create comma-separated list of donation IDs
			if ( ! isset( $donor_array[ $name ]['donations'] ) ) {
				$donor_array[ $name ]['donations'] = $donation_id;
			} else {
				$donor_array[ $name ]['donations'] .= ',' . $donation_id;
			}

			next( $donation_array );
		}

		// now move that data into a donor post type
		reset( $donor_array );

		while ( $donor = current( $donor_array ) ) {
			$donor_name = key( $donor_array );
			$donor_name = sanitize_text_field( $donor_name );

			// create the new custom fund post
			$post_array = array(
				'post_title'   => $donor_name,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'donor',
			);

			$post_id = wp_insert_post( $post_array, true );

			// update the options
			if ( $post_id != 0 ) {
				update_post_meta( $post_id, '_dgx_donate_donor_first_name', $donor['first'] );
				update_post_meta( $post_id, '_dgx_donate_donor_last_name', $donor['last'] );
				update_post_meta( $post_id, '_dgx_donate_donor_email', $donor['email'] );
				update_post_meta( $post_id, '_dgx_donate_donor_employer', $donor['employer'] );
				update_post_meta( $post_id, '_dgx_donate_donor_occupation', $donor['occupation'] );
				update_post_meta( $post_id, '_dgx_donate_donor_donations', $donor['donations'] );
				update_post_meta( $post_id, '_dgx_donate_donor_currency', $donor['currency'] );
				update_post_meta( $post_id, '_dgx_donate_donor_phone', $donor['phone'] );
				update_post_meta( $post_id, '_dgx_donate_donor_address', $donor['address'] );
				update_post_meta( $post_id, '_dgx_donate_donor_address2', $donor['address2'] );
				update_post_meta( $post_id, '_dgx_donate_donor_city', $donor['city'] );
				update_post_meta( $post_id, '_dgx_donate_donor_state', $donor['state'] );
				update_post_meta( $post_id, '_dgx_donate_donor_province', $donor['province'] );
				update_post_meta( $post_id, '_dgx_donate_donor_country', $donor['country'] );
				update_post_meta( $post_id, '_dgx_donate_donor_zip', $donor['zip'] );

				// update the donations to point to this donor id
				$my_donations = explode( ',', $donor['donations'] );

				if ( count( $my_donations ) > 0 ) {
					foreach ( (array) $my_donations as $donation_id ) {
						update_post_meta( $donation_id, '_dgx_donate_donor_id', $post_id );
					}
				}
			}

			next( $donor_array );
		}

		// write out the legacy import completed flag
		$plugin_version = 'sd40';
		update_option( 'dgx_donate_donors_legacy_import', $plugin_version );
	}
}

