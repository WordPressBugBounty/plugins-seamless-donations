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

function seamless_donations_init_audit() {
	// this checks to see if the audit table exists and builds it, if not
	global $wpdb;

	$table_name = $wpdb->prefix . 'seamless_donations_audit';

	if ( ( $wpdb->get_var( "SHOW TABLES LIKE '" . $table_name . "'" ) != $table_name ) or
	     get_option( 'dgx_donate_db_version' ) != '1.0.1'
	) {
		// table doesn't exist, add it
		$charset_collate = $wpdb->get_charset_collate();

		$sql
			= "CREATE TABLE $table_name (
  			option_id bigint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
  			option_name varchar(64) NOT NULL DEFAULT '',
			option_value longtext NOT NULL,
  			autoload varchar(20) NOT NULL DEFAULT 'yes',
  			donor_email varchar(128) NOT NULL DEFAULT '',
  			created_on TIMESTAMP DEFAULT 0,
  			changed_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (option_id),
			KEY donor_email (donor_email),
  			UNIQUE KEY option_name (option_name)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'dgx_donate_db_version', '1.0.1' );
	} else {
		// in future releases, might want to check dgx_donate_db_version and update database if desired
	}
}

function seamless_donations_add_audit_string( $option_name, $option_value ) {
	global $wpdb;

	$table_name  = $wpdb->prefix . 'seamless_donations_audit';
	$option_name = trim( $option_name );

	if ( empty( $option_name ) ) {
		return false;
	}

	// http://codex.wordpress.org/Class_Reference/wpdb#REPLACE_row
	$replace_result = $wpdb->replace(
		$table_name,
		array(
			'option_name'  => $option_name,
			'option_value' => $option_value,
		)
	);

	return $replace_result;
}

function seamless_donations_update_audit_option( $option_name, $option_value, $ignore_currency = false ) {
	global $wpdb;

	$table_name  = $wpdb->prefix . 'seamless_donations_audit';
	$option_name = trim( $option_name );

	if ( empty( $option_name ) ) {
		return false;
	}

	if ( ! $ignore_currency ) {
		if ( ! isset( $option_value['CURRENCY'] ) ) {
			$currency                 = get_option( 'dgx_donate_currency' );
			$option_value['CURRENCY'] = $currency;
		}
	}

	// http://codex.wordpress.org/Class_Reference/wpdb#REPLACE_row
	$replace_result = $wpdb->replace(
		$table_name,
		array(
			'option_name'  => $option_name,
			'option_value' => maybe_serialize( $option_value ),
		)
	);

	return $replace_result;
}

function seamless_donations_get_audit_option( $option_name ) {
	global $wpdb;

	$table_name  = $wpdb->prefix . 'seamless_donations_audit';
	$option_name = trim( $option_name );

	$query = "SELECT * FROM $table_name WHERE option_name='" . $option_name . "'";

	$option_object = $wpdb->get_row( $query, ARRAY_A );

	if ( $option_object != NULL ) {
		// do something with the link
		return maybe_unserialize( $option_object['option_value'] );
	} else {
		// no matching option record found
		return false;
	}
}

function seamless_donations_get_audit_email( $email ) {
	$email = strtolower( $email );
	$email = seamless_donations_get_audit_option( 'EMAIL-AUDIT-' . sanitize_email( $email ) );

	return $email; // false if no email data is found
}

function seamless_donations_update_audit_email( $email, $sessionID ) {
	$email  = strtolower( $email );
	$result = seamless_donations_get_audit_email( $email );
	if ( $result == false ) {
		seamless_donations_update_audit_option( 'EMAIL-AUDIT-' . sanitize_email( $email ), $sessionID, true );
	} else {
		if ( is_array( $result ) ) {
			foreach ( $result as $id ) {
				if ( $id == $sessionID ) {
					return;
				}
			}
			array_push( $result, $sessionID );
			seamless_donations_update_audit_option( 'EMAIL-AUDIT-' . sanitize_email( $email ), $result, true );
		} else {
			if ( $sessionID != $result ) {
				$array[] = $result;
				array_push( $array, $sessionID );
				seamless_donations_update_audit_option( 'EMAIL-AUDIT-' . sanitize_email( $email ), $array, true );
			}
		}
	}
}

function seamless_donations_get_recent_audit_data_entries( $days ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'seamless_donations_audit';

	$query = "SELECT * FROM $table_name WHERE (changed_on > now() - INTERVAL $days day) AND (option_name like 'SDS01-%')";

	$audit_entries = $wpdb->get_results( $query, ARRAY_A );

	$audit_entry_array = array();
	foreach ( $audit_entries as $audit_entry ) {
		$name = $audit_entry['option_name'];
		if ( strpos( $name, 'SDS' ) == 0 ) {
			// $value = maybe_unserialize($audit_entry["option_value"]);
			array_push( $audit_entry_array, $name );
		}
	}

	return $audit_entry_array;
}

function seamless_donations_get_changed_date_of_audit_entry( $audit_entry ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'seamless_donations_audit';

	$query   = "SELECT changed_on FROM $table_name WHERE option_name = '" . $audit_entry . "'";
	$results = $wpdb->get_results( $query, ARRAY_A );
	if ( isset( $results[0]['changed_on'] ) ) {
		$changed_date = $results[0]['changed_on'];

		return $changed_date;
	} else {
		return false;
	}
}

function seamless_donations_get_pending_audit_transactions( $days ) {
	$audit_data = seamless_donations_get_recent_audit_data_entries( $days );
	dgx_donate_debug_log( 'Pending transactions:' );

	$transaction_list = '';

	foreach ( $audit_data as $audit_entry ) {
		$found_array = seamless_donations_get_donations_by_meta( '_dgx_donate_session_id', $audit_entry, 1 );
		if ( sizeof( $found_array ) == 0 ) {
			// entry is pending
			$entry_data = seamless_donations_get_audit_option( $audit_entry );

			$first      = $entry_data['FIRSTNAME'];
			$last       = $entry_data['LASTNAME'];
			$amount     = $entry_data['AMOUNT'];
			$currency   = $entry_data['CURRENCY'];
			$entry_time = seamless_donations_get_changed_date_of_audit_entry( $audit_entry );
			if ( $transaction_list != '' ) {
				$transaction_list .= '\n';
			}
			$log              = $entry_time . ' ' . $audit_entry . ' ' . $first . ' ' . $last . ' ' . $amount . ' ' . $currency;
			$transaction_list .= $log;
		}
	}
	if ( $transaction_list == '' ) {
		return 'No transactions were found.';
	} else {
		return $log;
	}
}

function seamless_donations_get_donation_array_from_session_id( $session_id ) {
	// Ensure that the global $wpdb object is available
	global $wpdb;

	// Prepare the query to get all post IDs with the given session_id in the postmeta table
	$query = $wpdb->prepare( "
        SELECT post_id 
        FROM $wpdb->postmeta 
        WHERE meta_key = '_dgx_donate_session_id' 
        AND meta_value = %s
    ", $session_id );

	// Execute the query
	$post_ids = $wpdb->get_col( $query );

	// Check if post_ids were found
	if ( ! empty( $post_ids ) ) {
		return $post_ids;
	} else {
		// Return an empty array if no matching records were found
		return array();
	}
}

function seamless_donations_debug_display_audit_data_reconcilliation_in_log() {
	$sds01_found   = 0;
	$sds02_found   = 0;
	$sds01_missing = 0;
	$sds02_missing = 0;
	$debug_log_option = get_option( 'dgx_donate_debug_mode' );

	dgx_donate_audit_log( "Beginning reconcilliation audit..." );

	delete_option( 'dgx_donate_audit_log' );

	global $wpdb;
	$table_name  = $wpdb->prefix . 'seamless_donations_audit';
	$audit_count = 0;
	$audit_array = array();

	dgx_donate_audit_log( "Table name: " . $table_name );

	$bad_records = seamless_donations_get_unreferenced_donation_records();
	if ( $bad_records == '' ) {
		$count_recs = '0';
	} else {
		$count_recs = count( explode( ',', $bad_records ) );
	}
	$count_message = 'Number of empty donation records found: ' . $count_recs;
	$count_message .= ' of ' . seamless_donations_get_donation_record_count();
	dgx_donate_audit_log($count_message );
	if ( $debug_log_option == 'AUDITRECONCILEVERBOSE' ) {
		dgx_donate_audit_log( 'Empty donation records found: ' . $bad_records);
	}

	// Check if the table exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
		// Query to select all records from the table
		$results = $wpdb->get_results( "SELECT * FROM {$table_name}", OBJECT );

		// Loop through each record
		foreach ( $results as $row ) {
			// Check if the option_name begins with 'SDS0'
			if ( strpos( $row->option_name, 'SDS0' ) === 0 ) {
				$session_id = $row->option_name;
				$results    = seamless_donations_get_donation_array_from_session_id( $session_id );

				$option_value = $row->option_value;

				//Check if the content is serialized
				if ( is_serialized( $option_value ) ) {
					// Unserialize into an array
					$audit_array[] = unserialize( $option_value );
					$audit_count ++;
				}

				$sdversion = '';
				if(isset($audit_array[0]["SDVERSION"])) {
					$sdversion = ' [' . $audit_array[0]["SDVERSION"] . ']';
				}
				// $debug_log_option == 'AUDITRECONCILE' or $debug_log_option == 'AUDITRECONCILEVERBOSE'
				if ( empty( $results ) ) {
					if ( $debug_log_option == 'AUDITRECONCILEVERBOSE' ) {
						dgx_donate_audit_log( $session_id . ': No matching donation found' . $sdversion );
					}
					if ( strpos( $session_id, 'SDS01' ) === 0 ) {
						++$sds01_missing;
					}
					if ( strpos( $session_id, 'SDS02' ) === 0 ) {
						++$sds02_missing;
					}
				} else {
					if ( $debug_log_option == 'AUDITRECONCILEVERBOSE' ) {
						dgx_donate_audit_log( $session_id . ': Donation found' . $sdversion);
					}
					if ( strpos( $session_id, 'SDS01' ) === 0 ) {
						++$sds01_found;
					}
					if ( strpos( $session_id, 'SDS02' ) === 0 ) {
						++$sds02_found;
					}
				}
			}
		}
	}
	dgx_donate_audit_log( 'Total audit records: ' . strval($sds01_missing + $sds01_found + $sds02_missing + $sds02_found));
	dgx_donate_audit_log( '  Total SDS01 records: ' .  strval($sds01_missing + $sds01_found));
	dgx_donate_audit_log( '    Total SDS01 reconcilliation record matches: ' .  strval($sds01_found));
	dgx_donate_audit_log( '    Total SDS01 reconcilliation record without matches: ' .  strval($sds01_missing));
	dgx_donate_audit_log( '  Total SDS02 records: ' .  strval($sds02_missing + $sds02_found));
	dgx_donate_audit_log( '    Total SDS02 reconcilliation record matches: ' .  strval($sds02_found));
	dgx_donate_audit_log( '    Total SDS02 reconcilliation record without matches: ' .  strval($sds02_missing));
	dgx_donate_audit_log( "Reconcilliation audit complete." );
}
