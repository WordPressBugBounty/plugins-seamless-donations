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

function dgx_donate_send_thank_you_email( $donationID, $testAddress = '' ) {
	if ( ! empty( $testAddress ) ) {
		// Fill in dummy data
		$toEmail = $testAddress;
		// firstname
		$firstName = 'Test';
		// lastname
		$lastName = 'McTester';
		// amount
		$formatted_amount = '$100.00';
		// fundname
		$fund = 'Seamless Donations Test Fund (placeholder)';
		// repeating y/n
		$repeating = 'TRUE';
		// designated y/n
		$designated = 'TRUE';
		// anonymous y/n
		$anonymous = 'TRUE';
		// mailinglistjoin y/n
		$mailingListJoin = 'TRUE';
		// tribute y/n
		$tribute = 'TRUE';
		// employer match
		$employer_name = 'Worldwide Test';
	} else {
		// Get data from donationID
		$toEmail = get_post_meta( $donationID, '_dgx_donate_donor_email', true );
		// firstname
		$firstName = get_post_meta( $donationID, '_dgx_donate_donor_first_name', true );
		// lastname
		$lastName = get_post_meta( $donationID, '_dgx_donate_donor_last_name', true );
		// amount
		$amount           = get_post_meta( $donationID, '_dgx_donate_amount', true );
		$currency_code    = dgx_donate_get_donation_currency_code( $donationID );
		$formatted_amount = dgx_donate_get_plain_formatted_amount( $amount, 2, $currency_code, true );
		// fundname
		$fund = get_post_meta( $donationID, '_dgx_donate_designated_fund', true );
		// recurring y/n
		$repeating = get_post_meta( $donationID, '_dgx_donate_repeating', true );
		// designated y/n
		$designated = get_post_meta( $donationID, '_dgx_donate_designated', true );
		// anonymous y/n
		$anonymous = get_post_meta( $donationID, '_dgx_donate_anonymous', true );
		// mailinglistjoin y/n
		$mailingListJoin = get_post_meta( $donationID, '_dgx_donate_add_to_mailing_list', true );
		// tribute y/n
		$tribute = get_post_meta( $donationID, '_dgx_donate_tribute_gift', true );
		// employer match
		$employer_name = get_post_meta( $donationID, '_dgx_doname_employer_name', true );
	}

	$subject = get_option( 'dgx_donate_email_subj' );
	$subject = stripslashes( $subject );

	$body       = get_option( 'dgx_donate_email_body' );
	$body       = str_replace( '[firstname]', $firstName, $body );
	$body       = str_replace( '[lastname]', $lastName, $body );
	$body       = str_replace( '[amount]', $formatted_amount, $body );
	$body       = str_replace( '[fund]', $fund, $body );
	$body       = stripslashes( $body );
	$emailBody  = $body;
	$emailBody .= "\n\n";

	if ( ! empty( $repeating ) ) {
		$text       = get_option( 'dgx_donate_email_recur' );
		$text       = str_replace( '[amount]', $formatted_amount, $text );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	if ( ! empty( $designated ) ) {
		$text       = get_option( 'dgx_donate_email_desig' );
		$text       = str_replace( '[fund]', $fund, $text );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	if ( ! empty( $anonymous ) ) {
		$text       = get_option( 'dgx_donate_email_anon' );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	if ( ! empty( $mailingListJoin ) ) {
		$text       = get_option( 'dgx_donate_email_list' );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	if ( ! empty( $tribute ) ) {
		$text       = get_option( 'dgx_donate_email_trib' );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	if ( ! empty( $employer_name ) ) {
		$text       = get_option( 'dgx_donate_email_empl' );
		$text       = stripslashes( $text );
		$emailBody .= $text;
		$emailBody .= "\n\n";
	}

	$text       = get_option( 'dgx_donate_email_close' );
	$text       = stripslashes( $text );
	$emailBody .= $text;
	$emailBody .= "\n\n";

	$text       = get_option( 'dgx_donate_email_sig' );
	$text       = stripslashes( $text );
	$emailBody .= $text;
	$emailBody .= "\n";

	$header             = 'From: ';
	$from_email_name    = get_option( 'dgx_donate_email_name' );
	$from_email_address = get_option( 'dgx_donate_email_reply' );
	if ( empty( $from_email_name ) ) {
		$header .= $from_email_address;
	} else {
		$header .= '"' . $from_email_name . '" <' . $from_email_address . ">\r\n";
	}

	// left active for backwards compatibility
	$emailBody = apply_filters( 'dgx_donate_thank_you_email_body', $emailBody );
	// new recommended filter
	$emailBody = apply_filters( 'dgx_donate_thank_you_email_body_with_id', $emailBody, $donationID );

	$mail_sent = wp_mail( $toEmail, $subject, $emailBody, $header );

	if ( ! $mail_sent ) {
		dgx_donate_debug_log( 'Error: Could NOT send mail.' );
		dgx_donate_debug_log( "Subject: $subject" );
		dgx_donate_debug_log( "To Email: $toEmail" );
	} else {
		dgx_donate_debug_log( 'Success: Could send mail.' );
		dgx_donate_debug_log( "Subject: $subject" );
		dgx_donate_debug_log( "To Email: $toEmail" );
	}
}

function dgx_donate_send_donation_notification( $donationID ) {
	$fromEmail = get_option( 'dgx_donate_reply_email' );

	$body
		= __(
			   'ALERT - Seamless Donations is no longer being updated. For more information, visit ',
			   'seamless-donations'
		   ) . "\n";
	$body
		.= __(
			  'https://zatzlabs.com/seamless-donations-must-read/',
			  'seamless-donations'
		  ) . "\n\n";
	$body
      .= __(
		'A donation has been received. Here are some details about the donation.',
		'seamless-donations'
	) . "\n";
	$body     .= "\n";

	$body      .= "Donor:\n";
	$firstName  = get_post_meta( $donationID, '_dgx_donate_donor_first_name', true );
	$lastName   = get_post_meta( $donationID, '_dgx_donate_donor_last_name', true );
	$address    = get_post_meta( $donationID, '_dgx_donate_donor_address', true );
	$city       = get_post_meta( $donationID, '_dgx_donate_donor_city', true );
	$state      = get_post_meta( $donationID, '_dgx_donate_donor_state', true );
	$zip        = get_post_meta( $donationID, '_dgx_donate_donor_zip', true );
	$donorEmail = get_post_meta( $donationID, '_dgx_donate_donor_email', true );
	$body      .= "$firstName $lastName\n";
	$body      .= "$address\n";
	$body      .= "$city $state $zip\n";
	$body      .= "$donorEmail\n";
	$body      .= "\n";

	$body = apply_filters('dgx_donate_send_donation_notification_intro', $body, $donationID);

	$tributeGift = get_post_meta( $donationID, '_dgx_donate_tribute_gift', true );
	if ( ! empty( $tributeGift ) ) {
		$body .= __(
			'Tribute Donation: The donor is making this donation In Honor Of/In Memory Of someone.',
			'seamless-donations'
		) .
				 ' ';
		$body .= __(
			'Please see the donation details (using the link below) for more information.',
			'seamless-donations'
		) .  "\n";
		$body .= "\n";
	}

	$employer_match = get_post_meta( $donationID, '_dgx_donate_employer_match', true );
	if ( ! empty( $employer_match ) ) {
		$body .= __( 'Employer Match: The donor is making this donation with an employer match.', 'seamless-donations' ) . ' ';
		$body .= __(
			'Please see the donation details (using the link below) for more information.',
			'seamless-donations'
		) .
				 "\n";
		$body .= "\n";
	}

	$amount                  = get_post_meta( $donationID, '_dgx_donate_amount', true );
	$currency_code           = dgx_donate_get_donation_currency_code( $donationID );
	$formattedDonationAmount = dgx_donate_get_plain_formatted_amount( $amount, 2, $currency_code, true );
	$body                   .= __( 'Donation:', 'seamless-donations' ) . "\n";
	$body                   .= __( 'Amount:', 'seamless-donations' ) . " $formattedDonationAmount\n";

	$body .= "\n";
	$body .= __( 'Click on the following link to view all details for this donation:', 'seamless-donations' ) . "\n";

	$secureDonateLink = seamless_donations_get_donation_detail_link( $donationID );

	$donateLink = str_replace( 'https:', 'http:', $secureDonateLink );
	$body      .= $donateLink;
	$body      .= "\n";

	// add filters
//	$subject   = '[SEAMLESS DONATIONS ALERT] The sunsetting of Seamless Donations';
//	$subject = apply_filters( 'seamless_donations_email_subject', $subject, $donationID );
//	$body    = apply_filters( 'seamless_donations_email_complete_body', $body, $donationID );

	// Loop on addresses
	$notifyEmails  = get_option( 'dgx_donate_notify_emails' );
	$notifyEmailAr = explode( ',', $notifyEmails );

	foreach ( $notifyEmailAr as $notifyEmail ) {
		$notifyEmail = trim( $notifyEmail );
		if ( ! empty( $notifyEmail ) ) {
			$headers = "From: $fromEmail\r\n";

			// sunsetting code
			$subject   = 'SEAMLESS DONATIONS ALERT The sunsetting of Seamless Donations';
			$mail_sent = wp_mail( $notifyEmail, "URGENT SEAMLESS DONATIONS ALERT!", $body, $headers );

			if ( ! $mail_sent ) {
				dgx_donate_debug_log( 'Error: Could NOT send mail.' );
				dgx_donate_debug_log( "Subject: $subject" );
				dgx_donate_debug_log( "To Email: $notifyEmail" );
			} else {
				dgx_donate_debug_log( 'Success: Could send mail.' );
				dgx_donate_debug_log( "Subject: $subject" );
				dgx_donate_debug_log( "To Email: $notifyEmail" );
			}
		}
	}
}
